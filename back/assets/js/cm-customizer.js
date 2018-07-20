/*! Childify Me plugin by Rocco Aliberti, GPL2+ licensed */
/*
 * TODO:
 * - better errors handling?
 * - better validation, suppress *?\.. chars ?
**/
jQuery( function ( $ ) {
    var Selector = {
            MAIN_PANEL         : '#customize-info',
            TEMPLATE           : 'script#childify-tpl',
            CHILDIFY_CONTAINER : '#childify-container',
            ADD_NEW_BUTTON     : '#cm-add-new',
            FORM_CONTAINER     : '#cm-form-container',
            INPUT_CNAME        : '#cm-cname',
            CREATE_BUTTON      : '#cm-create',
            CANCEL_BUTTON      : '#cm-cancel',
            FTP_FORM           : '#ftp-form',
            FTP_UPGRADE_BUTTON : 'input#upgrade',
            PREVIEW_BUTTON     : '#cm-preview',
            SUCCESS_MESS_BOX   : '#cm-success',
            INFO_BOX           : '#cm-info',
            THEME_NAME         : '#cm-ctheme',
            WP_CONTAINER       : '.wp-full-overlay-sidebar-content'
        },
        $main_panel       = $( Selector.MAIN_PANEL ).parent(),
        childify_template = _.template(
            $( Selector.TEMPLATE ).html()
        ),
        action  = "action=" + CMAdmin.Action,
        nonce   = "CMnonce=" + CMAdmin.CMnonce,
        tparent = "parent=" + CMAdmin.Parent,
        data    = action + "&" + nonce + "&" + tparent,
        ajaxurl = CMAdmin.AjaxUrl;

    $main_panel.append( childify_template() );

    //Events binding
    $main_panel
        .on( 'keypress click', Selector.ADD_NEW_BUTTON, function( evt ) {
            if ( ! is_click_pretender( $(this), evt ) ){
                return;
            }

            $(this).toggleClass( 'open' );
            $( Selector.FORM_CONTAINER ).slideToggle( 'fast' );
            var $_cm_cname = $( Selector.INPUT_CNAME );
            if ( ! $_cm_cname.attr( 'readonly' ) ) {
                scroll_to( $_cm_cname.focus() );
            }
        })


        // reset on cancel button click
        .on( 'keypress click', Selector.CANCEL_BUTTON, function(evt){
            if ( ! is_click_pretender( $(this), evt) )
                return;

            $( Selector.INPUT_CNAME ).removeAttr( 'readonly' ).val( '' );
            $( Selector.CREATE_BUTTON ).removeAttr( 'disabled' );

            detach_ftp_form();
            set_action_buttons( validate( $( Selector.INPUT_CNAME ) ) );
        })


        // start child-theme creation on create button click
        .on( 'keypress click', Selector.CREATE_BUTTON, function(evt){
            if ( ! is_click_pretender( $(this), evt ) ) {
                return;
            }
            if ( ! validate( $( Selector.INPUT_CNAME ), true ) )
                return;

            $(this).attr( 'disabled', 'disabled' );
            $( Selector.INPUT_CNAME ).attr( 'readonly', 'readonly' );
            post();
        })

        // set action buttons state depending on validation
        // of the input child name field (on keypressed and blur)
        .on( 'keyup blur', Selector.INPUT_CNAME, function( evt ){
            if ( $(this).attr('readonly') ) {
                return;
            }
            if ( evt.keyCode ) {
                if ( ( evt.keyCode > 34 && evt.keyCode < 41 ) ||
                    evt.keyCode == 16 ) {
                    return;
                }
                if ( evt.keyCode == 13 ){
                    $( Selector.CREATE_BUTTON ).trigger( 'click' );
                    return;
                }
            }
            set_action_buttons( validate( $( this ) ) )
        })

        .on( 'keydown click', Selector.CHILDIFY_CONTAINER + ' ' + Selector.FTP_UPGRADE_BUTTON, function( evt ) {
            evt.preventDefault();
            if ( ! is_click_pretender( $(this), evt ) ) {
                return;
            }

            $( this ).attr( 'disabled', 'disabled' );
            post();
        });


    //AJAX
    function post() {
        var _data = data + "&" + $( Selector.CHILDIFY_CONTAINER + ' form' ).serialize();
        $.post(
            ajaxurl,
            _data,
            function( response ) {
                handle_response( response );
            }
        );
    }


    function handle_response( response ) {
        // is json?
        if ( 'undefined' != typeof response.success ) {
            if ( response.success ) { /* Success!! */
                $( Selector.THEME_NAME ).append( $( Selector.INPUT_CNAME ).val() );
                $( Selector.PREVIEW_BUTTON ).attr( 'href',
                    $( Selector.PREVIEW_BUTTON ).attr( 'href' ) + response.data.stylesheet
                );
            } else {
                prepare_display_error( response.data.message );
            }
            clean_all();
            $( Selector.SUCCESS_MESS_BOX ).css( 'display', 'block' );
        } else {
            if ( response.indexOf( "<form" ) > -1  ) { /*ftp*/
                detach_ftp_form();
                $( Selector.FORM_CONTAINER ).append( '<div id="' + Selector.FTP_FORM.substr(1) + '">' + response + '</div>' );
                scroll_to( Selector.FTP_FORM );
                $( Selector.FTP_FORM + ' input[name="hostname"]' ).focus();
            } else {/* general error */
                // handle 0 and -1 replies? don't think so
                clean_all();
                prepare_display_error( response );
                $( Selector.SUCCESS_MESS_BOX ).css( 'display', 'block' );
            }
        }
    }





    // helper function check if the element is enabled
    // and event is "click"
    // or "enter" keypressed.
    function is_click_pretender( $elem, evt ){
        return ( ! $elem.attr( 'disabled' ) &&
            ( evt.type === 'click' || evt.which === 13 ) );
    }

    function set_action_buttons( state ){
        if ( state ){
            $( Selector.CREATE_BUTTON ).removeAttr( 'disabled' );
            $( Selector.CANCEL_BUTTON ).removeAttr( 'disabled' );
        } else{
            $( Selector.CREATE_BUTTON ).attr( 'disabled', 'disabled' );
            $( Selector.CANCEL_BUTTON ).attr( 'disabled', 'disabled' );
        }
    }

    function validate( $elem, submit ){
        if ( submit ) {
            $elem.val( $.trim( $elem.val() ) );
        }
        if ( '' == $elem.val().replace(/^\s+/,'') ) {
            return false;
        }
        return true;
    }


    function prepare_display_error($message){
        $( Selector.SUCCESS_MESS_BOX + ' > p' ).text( $message ? $message : "Error" );
        $( Selector.SUCCESS_MESS_BOX ).removeClass( 'updated' )
                                      .addClass( 'error' );
        $( Selector.SUCCESS_MESS_BOX + ' ' + Selector.PREVIEW_BUTTON ).detach();
    }


    function clean_all() {
       $( [Selector.FORM_CONTAINER, Selector.ADD_NEW_BUTTON, Selector.INFO_BOX].join(',') ).detach();
    }

    function detach_ftp_form() {
        if ( $( Selector.FTP_FORM ).length > 0 ) {
            $( Selector.FTP_FORM ).detach();
        }
    }

    function scroll_to($anchor){
        var $wp_container = $( Selector.WP_CONTAINER ),
            offset        = parseInt( $($anchor).offset().top ) -
                            parseInt( $wp_container.offset().top ) +
                            parseInt( $wp_container.scrollTop() );
        $wp_container.animate( {
            scrollTop: offset
        }, 700 );
    }

    $( document ).ready( function( $ ) {
        set_action_buttons( false );
        $( Selector.CHILDIFY_CONTAINER ).css( 'display', 'block' );
    });
});