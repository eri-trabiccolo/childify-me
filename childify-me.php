<?php
/**
 * Plugin Name: Childify Me
 * Plugin URI: https://github.com/eri-trabiccolo/childify-me
 * Description: Create a child theme from the Theme Customizer panel
 * Version: 1.0.13
 * Author: Rocco Aliberti
 * Author URI: https://github.com/eri-trabiccolo
 * License: GPL2+
 */

/**
* Fires the plugin
* @author Rocco Aliberti
* @since 1.0.0
*/
/*
 * TODO: 
 * - handle errors on wp_filesystem->put_contents() ?
 */
if ( ! class_exists( 'Childify_Me' ) ) :
class Childify_Me {
    //Access any method or var of the class with classname::$instance -> var or method():
    static $instance;
    public $plug_name;
    public $plug_version;
    public $plug_lang;

    function __construct () {
        self::$instance =& $this;
        $this -> plug_name     = 'Childify Me';
        $this -> plug_version  = '1.0.13';
        $this -> plug_lang     = 'childify-me';

        //USEFUL CONSTANTS
        if ( ! defined( 'CM_DIR_NAME' ) ) { 
            define( 'CM_DIR_NAME' , basename( dirname( __FILE__ ) ) ); 
        }
        if ( ! defined( 'CM_BASE_URL' ) ) {
            define( 'CM_BASE_URL' , plugins_url( CM_DIR_NAME ) ); 
        }
        if ( ! defined( 'CM_CACTION' ) ) {
            define( 'CM_CACTION' , 'cm_create' ); 
        }

        //adds plugin text domain
        add_action( 'plugins_loaded', array( $this , 'cm_plugin_lang' ) );
        //setup hooks
        add_action( 'plugins_loaded', array( $this , 'cm_plugin_setup_hooks') );
    }//end of construct

    function cm_plugin_setup_hooks() {
                
        add_action ( 'customize_controls_enqueue_scripts', 
            array( $this , 'cm_customize_js_css' ), 
            100
        );
       
        add_action ( 'customize_controls_print_footer_scripts', 
            array( $this, 'print_template' ) 
        ); 

        add_action ('wp_ajax_'.CM_CACTION, 
            array( $this, 'cm_create_child_theme')
        );
    }

    // ajax callback
    function cm_create_child_theme(){
        if ( ! is_user_logged_in() )
            wp_die( 0 );

        check_ajax_referer( 'cm-nonce', 'CMnonce' );

        if ( ! current_user_can( 'edit_theme_options' ) )
            wp_die( -1 );
        if ( isset($_POST['parent']) ){
            if ( wp_get_theme($_POST['parent']) -> parent() )
                wp_die( 0 );
        }else
            wp_die( 0 );

        $this -> create_child_theme(
            $_POST['parent'],
            $_POST['cm-cname']
        );
        wp_die();
    }

    function create_child_theme($parent_stylesheet, $childname){
        global $wp_filesystem;
        if ( ( $creds = request_filesystem_credentials( '', '', false, 
            get_theme_root(), null ) ) === false ) {
                
            return;
		}
		if ( ! WP_Filesystem( $creds, get_theme_root() ) ) {
			request_filesystem_credentials( '', '', true, get_theme_root(), null );
			return;
		}

        if ( ! ( $wp_filesystem instanceof WP_Filesystem_Base ) ) {
			if ( ! WP_Filesystem() ) {
                wp_send_json_error( array(
                    'message' =>__( 'Error while trying to access the filesystem!', 
                    $this -> plug_lang )
                ));
                return;
			}
        }

        $current_theme          = wp_get_theme( $parent_stylesheet );
        $parent_stylesheet      = $current_theme -> get_stylesheet();
        $parent_name            = $current_theme -> Name ? $current_theme -> Name :
                                                           $parent_stylesheet;
        $child                  = $childname;
        $child_theme_directory  = trailingslashit( get_theme_root() ) . 
            sanitize_file_name( strtolower( $child ) ) ;
        $parent_theme_directory = $current_theme -> get_stylesheet_directory();

        $i                      = 2;
        /* incremental dirname */
        $suffix                 = '';
        while ( $wp_filesystem -> is_dir( $child_theme_directory . $suffix ) )
            $suffix = '_' . $i++;
          
        $child                 .= $suffix;
        $child_theme_directory .= $suffix;

        $current_user           = wp_get_current_user();
        $author                 = strlen($current_user -> user_firstname . 
                                    $current_user -> user_lastname) > 0 ? 
                trim( $current_user -> user_firstname . " " . $current_user -> user_lastname ) : 
                __( 'Administrator', $this -> plug_lang );

        $load_parent_stylesheet = ! in_array( $parent_stylesheet, 
            array(
                'customizr',
                'customizr-pro') ) ? '*/
@import url("../'.$parent_stylesheet.'/style.css");' : '*/';
        
        $wp_filesystem -> mkdir( $child_theme_directory );
        $child_stylesheet       = trailingslashit( $child_theme_directory ) . 'style.css';
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
        $wp_filesystem -> put_contents( $child_stylesheet, $child_stylesheet_content );
        $child_functionsphp_content = <<<EOF
<?php
/* Write your awesome functions below */
EOF;

        $child_functionsphp = trailingslashit( $child_theme_directory ) . 'functions.php';
        $wp_filesystem -> put_contents( $child_functionsphp, $child_functionsphp_content );

        /* create the child-theme screenshot.png*/    
        if ( file_exists( $parent_theme_directory . '/screenshot.png' ) ) {
            if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ){
                $screenshot = $this -> childify_screenshot(
                    $parent_theme_directory . '/screenshot.png');
                $wp_filesystem -> put_contents( $child_theme_directory . '/screenshot.png', 
                    $screenshot );
            }else
                $wp_filesystem -> copy( $parent_theme_directory . "/screenshot.png",
                    $child_theme_directory . '/screenshot.png' );
        }

        wp_send_json_success( array(
            'stylesheet' => sanitize_file_name( strtolower( $child ) )
        ));
        return;
    }//end of create_child_theme()
    
    //create screenshot image(string): overlay of parent screenshot + childify-me badge
    function childify_screenshot( $screenshot ){
        $parent_src = imagecreatefrompng($screenshot);
        $cm_src = imagecreatefrompng( plugin_dir_path(__FILE__) .
                                        '/back/assets/img/child.png' );
        
        if ( function_exists('imagecreatetruecolor') ){
            $dest = imagecreatetruecolor(880, 660);
            imagecopy( $dest, $parent_src, 0, 0, 0, 0, 880, 660 );
        }else
            $dest = $parent_src;

        imagecopy( $dest, $cm_src, 0, 0, 0, 0, 350, 350 );
        
        ob_start();
            header( 'Content-Type: image/png' );
            imagepng( $dest );
        $image = ob_get_contents();
        ob_end_clean();
        
        imagedestroy( $cm_src );
        imagedestroy( $dest );
        
        if ( $dest !== $parent_src ){
            imagedestroy( $parent_src );
        }

        return $image;
    }
   
    //declares the plugin translation domain
    function cm_plugin_lang() {
        load_plugin_textdomain( $this -> plug_lang , false, CM_DIR_NAME . '/lang' );
    }

    function cm_customize_js_css() {
        global $wp_customize;
        $current_stylesheet = $wp_customize -> theme() -> stylesheet ;
        if ( wp_get_theme( $current_stylesheet ) -> parent() )
            return;

        wp_enqueue_style(
            'cm-customizer-style',
            sprintf('%1$s/back/assets/css/cm-customizer%2$s.css' , 
                CM_BASE_URL,
                ( defined('WP_DEBUG') && true == WP_DEBUG ) ? '' : '.min'
            ),
	        array( 'customize-controls' ),
            $this -> plug_version,
            $media = 'all'
        );
        wp_enqueue_script(
            'cm-customizer' ,
            sprintf('%1$s/back/assets/js/cm-customizer%2$s.js' , 
                CM_BASE_URL,
                ( defined('WP_DEBUG') && true == WP_DEBUG ) ? '' : '.min'
            ),
            array( 'customize-controls', 'underscore' ),
            $this -> plug_version,
            true
        );
        wp_localize_script(
           'cm-customizer',
           'CMAdmin',
            array(
                'AjaxUrl'    => admin_url( 'admin-ajax.php' ),
                'CMnonce'    => wp_create_nonce( 'cm-nonce' ),
                'Action'     => CM_CACTION,
                'Parent'     => $current_stylesheet
            )
        ); 
    }
    
    //this template will be loaded with underscore in cm-customizr(.min).js
    function print_template(){
    ?>
        <script type="text/template" id="childify-tpl"> 
            <div id="childify-container">
              <?php
                printf('<span id="cm-info" class="cm-notice">%1$s</span>',
                    __('Click on the button below to create a child theme', $this -> plug_lang )
                );
                printf('<span id="cm-add-new" class="cm-add-new button button-primary" tabindex="0">%1$s
                    </span>',
                    "Childify Me"
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
                    __( "Child theme name here" , $this -> plug_lang ),
                    __( "Create" , $this -> plug_lang ),
                    __( "Cancel" , $this -> plug_lang )
                );
                printf('<div id="cm-success" class="updated"><p>%1$s <span id="cm-ctheme"></span> %2$s</p>%3$s</div>',
                    __("Child theme", $this -> plug_lang ),
                    __("successfully created!", $this -> plug_lang ),
                    ( ! is_multisite() ) ? 
                        sprintf('<a id="%3$s" class="button button-primary" href="%1$s" title="%2$s" tabindex="0">%2$s</a>',
                            sprintf('%1$s?theme=', admin_url( 'customize.php' ) ),
                            __("Preview and Activate", $this -> plug_lang ),
                            "cm-preview"
                        ) :
                        sprintf('<a id="%3$s" class="button button-primary" href="%1$s" title="%2$s" tabindex="0">%2$s</a>',
                            network_admin_url('themes.php'),
                            __("Go to Network Themes", $this -> plug_lang ),
                            "cm-themes"
                        )
                );
              ?>
          </div>
       </script>
    <?php
    }
}//end of class

//Creates a new instance
new Childify_Me;

endif;
