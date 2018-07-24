<?php
/**
 *
 * Main Childify-Me Class File
 *
 * @author Rocco Aliberti
 * @package Childify-Me
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * TODO:
 * - handle errors on wp_filesystem->put_contents() ?
 */
if ( ! class_exists( 'Childify_Me' ) ) :
	/**
	 * Main Childify_Me Class
	 *
	 * @since 1.0.0
	 */
	class Childify_Me {

		/**
		 * The plugin version, used as query var when enqueuing assets
		 *
		 * @static
		 * @var string
		 */
		private static $plug_version = '1.2.0';

		/**
		 * Themes which do not need the style.css importing.
		 *
		 * @var array
		 */
		private $special_themes = array(
			'customizr'     => array(),
			'customizr-pro' => array(),
			'hueman'        => array( 'version' => '3.0' ),
			'hueman-pro'    => array(),
		);


		/**
		 * Main Childify_Me Instance.
		 * Please load it only one time.
		 *
		 * Insures that only one instance of Childify_Me exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.2.0
		 *
		 * @static object $instance
		 * @see cm_childifyme()
		 *
		 * @return ChildifyMe|null The Childify-Me instance.
		 */
		public static function instance() {
			if ( ! is_admin() ) {
				return;
			}

			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Childify_Me();

				// USEFUL CONSTANTS.
				if ( ! defined( 'CM_DIR_NAME' ) ) {
					define( 'CM_DIR_NAME', basename( dirname( __FILE__ ) ) );
				}
				if ( ! defined( 'CM_BASE_URL' ) ) {
					define( 'CM_BASE_URL', plugins_url( CM_DIR_NAME ) );
				}
				if ( ! defined( 'CM_CACTION' ) ) {
					define( 'CM_CACTION', 'cm_create' );
				}

				// adds plugin text domain.
				add_action( 'plugins_loaded', array( $instance, 'cm_plugin_lang' ) );
				// setup hooks.
				add_action( 'plugins_loaded', array( $instance, 'cm_plugin_setup_hooks' ) );
			}

			// Always return the instance.
			return $instance;
		}//end instance()


		/**
		 * A dummy constructor to prevent Childify_Me from being loaded more than once.
		 *
		 * @see Childify_Me::instance()
		 * @see cm_childifyme()
		 */
		private function __construct() {
			/* Do nothing here */
		}



		/**
		 * This method adds needed actions and filters
		 *
		 * @since 1.0.0
		 * @hook plugins_loaded
		 */
		public function cm_plugin_setup_hooks() {
			add_action( 'customize_controls_enqueue_scripts',
				array( $this, 'cm_customize_js_css' ),
				100
			);

			add_action( 'customize_controls_print_footer_scripts',
				array( $this, 'cm_print_template' )
			);

			add_action( 'wp_ajax_' . CM_CACTION,
				array( $this, 'cm_create_child_theme' )
			);
		}//end cm_plugin_setup_hooks()



		/**
		 * AJAX Ccllback
		 * Here is where we do our security checks and sanitize teh $_POST data
		 *
		 * @since 1.0.0
		 */
		public function cm_create_child_theme() {
			// ajax callback.
			if ( ! is_user_logged_in() ) {
				wp_die( 0 );
			}

			check_ajax_referer( 'cm-nonce', 'CMnonce' );

			if ( ! current_user_can( 'edit_theme_options' ) ) {
				wp_die( -1 );
			}

			$_parent  = empty( $_POST['parent'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['parent'] ) );
			$_cm_name = empty( $_POST['cm-cname'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['cm-cname'] ) );

			if ( empty( $_parent ) || empty( $_cm_name ) || wp_get_theme( $_parent )->parent() ) {
				wp_die( 0 );
			}

			$this->cm_do_create_child_theme(
				$_parent,
				$_cm_name
			);

			wp_die();
		}//end cm_create_child_theme()


		/**
		 * The actual method which will create the child-theme.
		 *
		 * @since 1.0.0
		 *
		 * @param string $parent_stylesheet The parent theme stylesheet.
		 * @param string $childname The child theme name.
		 */
		private function cm_do_create_child_theme( $parent_stylesheet, $childname ) {
			global $wp_filesystem;
			$creds = request_filesystem_credentials( '', '', false, get_theme_root(), null );
			if ( false === $creds ) {
				return;
			}
			if ( ! WP_Filesystem( $creds, get_theme_root() ) ) {
				request_filesystem_credentials( '', '', true, get_theme_root(), null );
				return;
			}

			if ( ! ( $wp_filesystem instanceof WP_Filesystem_Base ) ) {
				if ( ! WP_Filesystem() ) {
					wp_send_json_error( array(
						'message' => __( 'Error while trying to access the filesystem!', 'childify-me' ),
					));
					return;
				}
			}

			$current_theme          = wp_get_theme( $parent_stylesheet );
			$parent_stylesheet      = $current_theme->get_stylesheet();
			$parent_name            = $current_theme->get( 'Name' );
			$parent_name            = $parent_name ? $parent_name : $parent_stylesheet;
			$child                  = $childname;
			$child_theme_directory  = trailingslashit( get_theme_root() ) . sanitize_file_name( strtolower( $child ) );
			$parent_theme_directory = $current_theme->get_stylesheet_directory();
			$i                      = 2;
			$suffix                 = '';// incremental dirname.

			while ( $wp_filesystem->is_dir( $child_theme_directory . $suffix ) ) {
				$suffix = '_' . $i;
				$i++;
			}

			$child                 .= $suffix;
			$child_theme_directory .= $suffix;
			$current_user           = wp_get_current_user();
			$author                 = strlen( $current_user->user_firstname . $current_user->user_lastname ) > 0 ?
					trim( $current_user->user_firstname . ' ' . $current_user->user_lastname ) :
					__( 'Administrator', 'childify-me' );

			/* Do we need to import the parent style.css? */
			$_has_parent_stylesheet_to_load = true;
			if ( array_key_exists( $parent_stylesheet, $this->special_themes ) ) {
				$special_themes = $this->special_themes;
				if ( ! isset( $special_themes[ $parent_stylesheet ]['version'] ) ||
					( isset( $special_themes[ $parent_stylesheet ]['version'] ) &&
					version_compare( $special_themes[ $parent_stylesheet ]['version'], $current_theme->get( 'Version' ), '<=' ) )
				) {
					$_has_parent_stylesheet_to_load = false;
				}
			}

			$load_parent_stylesheet = $_has_parent_stylesheet_to_load ?
			'*/
@import url("../' . $parent_stylesheet . '/style.css");' : '*/';

			$wp_filesystem->mkdir( $child_theme_directory );
			$child_stylesheet         = trailingslashit( $child_theme_directory ) . 'style.css';
			$child_stylesheet_content = <<<EOF
/*
Theme Name: $child
Version: 1.0
Description: A child theme of $parent_name
Template: $parent_stylesheet
Author: $author
$load_parent_stylesheet
/* Your awesome customization starts here */
EOF;
			$wp_filesystem->put_contents( $child_stylesheet, $child_stylesheet_content );
			$child_functionsphp_content = <<<EOF
<?php
/* Write your awesome functions below */
EOF;

			$child_functionsphp = trailingslashit( $child_theme_directory ) . 'functions.php';
			$wp_filesystem->put_contents( $child_functionsphp, $child_functionsphp_content );

			/* create the child-theme screenshot.(png|jpeg|jpg) */
			foreach ( array( 'png', 'jpg', 'jpeg' ) as $parent_screenshot_extension ) {
				if ( file_exists( $parent_theme_directory . '/screenshot.' . $parent_screenshot_extension ) ) {
					$parent_screenshot = $parent_theme_directory . '/screenshot.' . $parent_screenshot_extension;
					break;
				}
			}

			if ( isset( $parent_screenshot ) ) {
				if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
					$screenshot = $this->cm_screenshot( $parent_screenshot, $parent_screenshot_extension );
					if ( $screenshot ) {
						$wp_filesystem->put_contents( $child_theme_directory . '/screenshot.png', $screenshot );
					}
				} else {
					$wp_filesystem->copy( $parent_theme_directory . '/screenshot.' . $parent_screenshot_extension, $child_theme_directory . '/screenshot.' . $parent_screenshot_extension );
				}
			}

			wp_send_json_success( array( 'stylesheet' => sanitize_file_name( strtolower( $child ) ) ) );
		}//end cm_do_create_child_theme()



		/**
		 * Helper to create the child-theme screenshot starting from the parent one.
		 *
		 * @since 1.0.0
		 *
		 * @param string $parent_screenshot The parent theme screenshot file name.
		 * @param string $parent_screenshot_extension The parent theme screenshot file extension (jpg or png).
		 *
		 * @return image|bool The child-theme screenshot image content, or false.
		 */
		private function cm_screenshot( $parent_screenshot, $parent_screenshot_extension ) {
			// create screenshot image(string): overlay of parent screenshot + childify-me badge.
			$parent_src = 'png' === $parent_screenshot_extension ? imagecreatefrompng( $parent_screenshot ) : imagecreatefromjpeg( $parent_screenshot );

			// parent_size.
			list( $parent_width, $parent_height ) = getimagesize( $parent_screenshot );

			// default size.
			$parent_width  = $parent_width ? $parent_width : 1200;
			$parent_height = $parent_height ? $parent_height : 900;
			$cm_src        = imagecreatefrompng( plugin_dir_path( __FILE__ ) . '/back/assets/img/childify-me-badge.png' );

			if ( ! $parent_src || ! $cm_src ) {
				return false;
			}

			if ( function_exists( 'imagecreatetruecolor' ) ) {
				$dest = imagecreatetruecolor( $parent_width, $parent_height );
				imagecopy( $dest, $parent_src, 0, 0, 0, 0, $parent_width, $parent_height );
			} else {
				$dest = $parent_src;
			}

			imagecopy( $dest, $cm_src, 0, 0, 0, 0, 287, 175 );

			ob_start();
				header( 'Content-Type: image/png' );
				imagepng( $dest );
			$image = ob_get_contents();
			ob_end_clean();

			imagedestroy( $cm_src );
			imagedestroy( $dest );

			if ( $dest !== $parent_src ) {
				imagedestroy( $parent_src );
			}

			return $image;
		}//end cm_screenshot()


		/**
		 * A method to load the plugin textdomain.
		 *
		 * @since 1.0.0
		 *
		 * @hook plugins_loaded
		 */
		public function cm_plugin_lang() {
			load_plugin_textdomain( 'childify-me', false, CM_DIR_NAME . '/lang' );
		}//end cm_plugin_lang()



		/**
		 * A method to enqueue CSS and JS assets.
		 *
		 * @since 1.0.0
		 * @hook customize_controls_enqueue_scripts
		 */
		public function cm_customize_js_css() {
			global $wp_customize;
			$current_stylesheet = $wp_customize->theme()->stylesheet;
			if ( wp_get_theme( $current_stylesheet )->parent() ) {
				return;
			}

			wp_enqueue_style(
				'cm-customizer-style',
				sprintf( '%1$s/back/assets/css/cm-customizer%2$s.css',
					CM_BASE_URL,
					( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min'
				),
				array( 'customize-controls' ),
				( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : self::$plug_version,
				$media = 'all'
			);

			wp_enqueue_script(
				'cm-customizer',
				sprintf('%1$s/back/assets/js/cm-customizer%2$s.js',
					CM_BASE_URL,
					( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min'
				),
				array( 'customize-controls', 'underscore' ),
				( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : self::$plug_version,
				true
			);

			wp_localize_script(
				'cm-customizer',
				'CMAdmin',
				array(
					'AjaxUrl' => admin_url( 'admin-ajax.php' ),
					'CMnonce' => wp_create_nonce( 'cm-nonce' ),
					'Action'  => CM_CACTION,
					'Parent'  => $current_stylesheet,
				)
			);
		}//end cm_customize_js_css()



		/**
		 * A method to print the Childify-Me box template.
		 *
		 * @since 1.0.0
		 * @hook customize_controls_print_footer_scripts
		 */
		public function cm_print_template() {
			// this template will be loaded with underscore in cm-customizr(.min).js .
			?>
			<script type="text/template" id="childify-tpl">
				<div id="childify-container">
				<?php
					printf( '<span id="cm-info" class="cm-notice">%1$s</span>',
						esc_html__( 'Click on the button below to create a child theme', 'childify-me' )
					);
					printf( '<span id="cm-add-new" class="cm-add-new button button-primary" tabindex="0">%1$s
						</span>',
						'Childify Me'
					);
					printf('
						<div id="cm-form-container" style="display:none">
							<form id="cm-form">

								<input placeholder="%1$s" type="text" id="cm-cname" name="cm-cname" value="" tabindex="0">
							</form>
							<div id="cm-actions">
								<span id="cm-create" class="button button-secondary" tabindex="0">%2$s</span><span class="button button-secondary" id="cm-cancel" tabindex="0">%3$s</span>
							</div>
						</div>',
						esc_html__( 'Child theme name here', 'childify-me' ),
						esc_html__( 'Create', 'childify-me' ),
						esc_html__( 'Cancel', 'childify-me' )
					);
					printf('<div id="cm-success" class="updated"><p>%1$s <span id="cm-ctheme"></span> %2$s</p>%3$s</div>',
						esc_html__( 'Child theme', 'childify-me' ),
						esc_html__( 'successfully created!', 'childify-me' ),
						( ! is_multisite() ) ?
							sprintf( '<a id="%3$s" class="button button-primary" href="%1$s" title="%2$s" tabindex="0">%2$s</a>',
								sprintf( '%1$s?theme=', esc_url( admin_url( 'customize.php' ) ) ),
								esc_html__( 'Preview and Activate', 'childify-me' ),
								'cm-preview'
							) :
							sprintf('<a id="%3$s" class="button button-primary" href="%1$s" title="%2$s" tabindex="0">%2$s</a>',
								esc_url( network_admin_url( 'themes.php' ) ),
								esc_html__( 'Go to Network Themes', 'childify-me' ),
								'cm-themes'
							)
					);
				?>
				</div>
			</script>
			<?php
		}//end cm_print_template()
	}//end class

endif;
