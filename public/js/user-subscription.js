jQuery(document).ready(function ($) {
    jQuery('body').on('submit', '#subscribe-user', function (e) {
        var form = $(this).serialize();
        var user_email = jQuery(this).find('#user_email').val();
        jQuery('#login p.status').show().text('');

        if (user_email !== '') {
            jQuery('#submit_status').removeClass('error').hide().html('');
            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajax_object.ajaxurl,
                data: {
                    'action': 'subscribe_user',
                    'form_data': form,
                },
                success: function (data) {
                    jQuery('body').find('.login_submit_img_loader').hide();
                    jQuery('.subscribe-form').html(data.message);

                }
            });
        } else {
            jQuery('#submit_status').addClass('error').show().html('Please don\'t leave the required fields.');
        }

        return false;
    });
    
    jQuery('body').on('click', '.subscription_form_wrap .close', function() {
        jQuery('.subscription_form_wrap').hide("slow");
    });
    
    jQuery('body').on('click', '.subscribe_button', function() {
        jQuery('.subscription_form_wrap').toggle("slow");
    });

});