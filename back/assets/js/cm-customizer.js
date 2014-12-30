/*! Childify Me plugin by Rocco Aliberti, GPL2+ licensed */
/*
 * TODO: 
 * - better errors handling?
 * - better validation, suppress *?\.. chars ?
**/
jQuery(function ($) {
    var $main_panel = $('#customize-info').parent(),
        childify_template = _.template(
            $('script#childify-tpl').html()
        ),
        action  = "action="+CMAdmin.Action,
        nonce   = "CMnonce="+CMAdmin.CMnonce,
        tparent  = "parent="+CMAdmin.Parent,
        data    = action+"&"+nonce+"&"+tparent,
        ajaxurl = CMAdmin.AjaxUrl;
    
    $main_panel.append( childify_template() );
    
    $('#cm-add-new').click(function(){
        $(this).toggleClass('open');
        $('#cm-form-container').slideToggle("fast");
        if ( ! $('#cm-cname').attr('readonly') )
            $('#cm-cname').focus();
    });
    // reset on cancel button click
    $('#cm-cancel').click(function(){
        if ( $(this).attr('disabled') )
            return;
        $('#cm-cname').removeAttr('readonly');
        $('#cm-create').removeAttr('disabled');
        $('#cm-cname').val("");
        detach_ftp_form();
        set_action_buttons( validate($('#cm-cname')) );
    });
    
    // start child-theme creation on create button click
    $('#cm-create').click(function(){
        if ( $(this).attr('disabled') )
            return;
        if ( ! validate($('#cm-cname'), true) )
            return;
        $(this).attr('disabled', 'disabled');
        $('#cm-cname').attr('readonly', 'readonly');
        post();
    });
    
    // set action buttons state depending on validation
    // of the input child name field (on keypressed and blur)
    $('#cm-cname').on('keyup blur', function(evt){
        if ( $(this).attr('readonly') )
            return;
        if ( evt.keyCode ) {
            if ( ( evt.keyCode > 34 && evt.keyCode < 41 ) || 
                evt.keyCode == 16 )
                return;
            if ( evt.keyCode == 13 ){
                $('#cm-create').trigger('click');
                return;
            }
        }
        set_action_buttons( validate( $(this) ) )
    });
    
    function set_action_buttons(state){
        if ( state ){
            $('#cm-create').removeAttr('disabled');
            $('#cm-cancel').removeAttr('disabled');
        }else{
            $('#cm-create').attr('disabled', 'disabled');
            $('#cm-cancel').attr('disabled', 'disabled');
        }
    }
    
    function validate( $elem, submit){
        if ( submit )
            $elem.val( $.trim( $elem.val() ) );
        else 
            $elem.val( $elem.val().replace(/^\s+/g,'') )
        if ( $elem.val() == '' )
            return false;
        return true;
    }

    function handle_response(response){
        // is json?
        if ( typeof response.success != 'undefined'){
            if ( response.success ) { /* Success!! */
                $('#cm-ctheme').append($('#cm-cname').val());
                $('#cm-preview').attr('href',
                    $('#cm-preview').attr('href')+response.data.stylesheet
                );
            }else{
                prepare_display_error(response.data.message);
            }
            clean_all();
            $('#cm-success').css('display', 'block');
        }else{
            if ( response.indexOf("<form") > -1 ){ /*ftp*/
                detach_ftp_form();
                $('#cm-form-container').append('<div id="ftp-form">'+response+'</div>');
                scroll_to('#ftp-form');
                $('#childify-container input#upgrade').click(function(evt){
                    evt.preventDefault();
                    $(this).attr('disabled', 'disabled');
                    post();        
                });
            }else{/* general error */
                // handle 0 and -1 replies? don't think so
                clean_all();
                prepare_display_error(response);
                $('#cm-success').css('display', 'block');
            }
        }
    }
    
    function prepare_display_error($message){
        $('#cm-success > p').text( $message ? $message : "Error");
        $('#cm-success').removeClass('updated');
        $('#cm-success').addClass('error');
        $('#cm-success #cm-preview').detach();
    }
    
    function post(){
        data += "&"+$('#childify-container form').serialize();
        $.post(ajaxurl, data, function(response){ 
            handle_response(response); 
        });
    }
    
    function clean_all(){
       $('#cm-form-container').detach();
       $('#cm-add-new').detach();
       $('#cm-info').detach();
    }
    
    function detach_ftp_form(){
        if ( $('#ftp-form').length > 0 ){
            $('#ftp-form').detach();
        }
    }
    
    function scroll_to($anchor){
        var $offset = parseInt( $($anchor).offset().top ) - 
            parseInt( $('.wp-full-overlay-sidebar-content').offset().top ) +
            parseInt( $('.wp-full-overlay-sidebar-content').scrollTop() );
        $('.wp-full-overlay-sidebar-content').animate({
            scrollTop: $offset
        }, 700);
    }
    
    $(document).ready(function($){
        set_action_buttons( false );
        $('#childify-container').css('display', 'block');
    });
});
