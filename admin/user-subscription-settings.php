<?php

class User_Subscription_Settings_Page {

    function __construct() {
        add_action('admin_menu', array(&$this, 'user_subscription_admin_menu'));
        add_action('admin_post_save_user_subscription_settings', array(&$this, 'user_subscription_on_save_changes'));
    }

    //extend the admin menu
    function user_subscription_admin_menu() {
        $this->pagehook = add_menu_page( __('Subscription Settings', 'user-subscription'), __('Subscription Settings', 'user-subscription'), 'manage_options', 'user-subscription-settings', array(&$this, 'user_subscription_on_show_page'));
        add_action('load-' . $this->pagehook, array(&$this, 'user_subscription_on_load_page'));
    }

    function user_subscription_on_load_page() {
        // scripts
        wp_enqueue_script(array(
            'jquery',
            'jquery-ui-core',
            'jquery-ui-tabs',
            'thickbox',
        ));
        wp_enqueue_script( 'zd-script', plugins_url( '/js/user-subscription-admin.js', __FILE__ ), array( 'jquery' ) );
        wp_enqueue_style( 'zd-style', plugins_url( '/css/user-subscription-admin.css', __FILE__ ) );
        add_meta_box('user_subscription_settings', __('Subscription Settings', 'user-subscription'), array(&$this, 'user_subscription_settings'), $this->pagehook, 'normal', 'core');
    }

    //executed to show the plugins complete admin page
    function user_subscription_on_show_page() {
        $data = array();
    ?>
        <div id="user-subscription-settings-metaboxes" class="wrap">
            <h1><?php _e('Subscription Settings', 'user-subscription'); ?></h1>
            <form action="admin-post.php" method="post">
                <?php wp_nonce_field('user-subscription-settings-metaboxes'); ?>
                <input type="hidden" name="action" value="save_user_subscription_settings" />
                <div id="poststuff" class="metabox-holder has-right-sidebar">
                    <div id="side-info-column" class="inner-sidebar">
                        <!-- Update -->
                        <div class="postbox">
                            <h3 class="hndle"><span><?php _e('Publish','user-subscription'); ?></span></h3>
                            <div class="inside">
                                <input type="submit" class="button button-primary button-large" value="<?php _e('Save Options','user-subscription'); ?>" />
                            </div>
                        </div>
                        <?php do_meta_boxes($this->pagehook, 'side', $data); ?>
                    </div>
                    <div id="post-body" class="has-sidebar">
                        <div id="post-body-content" class="has-sidebar-content">
                            <?php do_meta_boxes($this->pagehook, 'normal', $data); ?>
                            <?php do_meta_boxes($this->pagehook, 'additional', $data); ?>
                        </div>
                    </div>
                    <br class="clear"/>
                </div>
            </form>
        </div>
        <?php
    }

    //executed if the post arrives initiated by pressing the submit button of form
    function user_subscription_on_save_changes() {
        //user permission check
        if (!current_user_can('manage_options'))
            wp_die(__('Cheatin&#8217; uh?', 'user-subscription'));
        //cross check the given referer
        check_admin_referer('user-subscription-settings-metaboxes');
        $subscription_data = '';
        $subscription_data['enable_floating_button'] = '';
        if(isset($_POST)) {

            if(isset($_POST['enable-floating-button']) && $_POST['enable-floating-button'] == 'yes') {
                $subscription_data['enable_floating_button'] = 'yes';
            }
            
            if(isset($_POST['subscription-categories'])) {
                $subscription_data['subscription_categories'] = $_POST['subscription-categories'];
            }
            
            if(isset($_POST['subscription-button-title'])) {
                $subscription_data['subscription_button_title'] = sanitize_text_field($_POST['subscription-button-title']);
            }
            
            if(isset($_POST['notification-frequency-title'])) {
                $subscription_data['notification_frequency_title'] = sanitize_text_field($_POST['notification-frequency-title']);
            }
            
            if(isset($_POST['select-categories-title'])) {
                $subscription_data['select_categories_title'] = sanitize_text_field($_POST['select-categories-title']);
            }
            
            if(isset($_POST['subscribe-button-text'])) {
                $subscription_data['subscribe_button_text'] = sanitize_text_field($_POST['subscribe-button-text']);
            }
            
            if(isset($_POST['confirmation-email'])) {
                $confirmation_email_data = $_POST['confirmation-email'];
                if(is_array($confirmation_email_data)) {

                    if(isset($confirmation_email_data['email-subject'])) {
                        $confirmation_email_data['email-subject'] = sanitize_text_field( $confirmation_email_data['email-subject'] );
                    }
                    if(isset($confirmation_email_data['sent-from'])) {
                        $confirmation_email_data['sent-from'] = sanitize_text_field( $confirmation_email_data['sent-from'] );
                    }
                    if(isset($confirmation_email_data['reply-email'])) {
                        $confirmation_email_data['reply-email'] = sanitize_text_field( $confirmation_email_data['reply-email'] );
                    }
                    if(isset($confirmation_email_data['email-content'])) {
                        $confirmation_email_data['email-content'] = sanitize_text_field( htmlentities( $confirmation_email_data['email-content'] ) );
                    }
                }
                $subscription_data['confirmation_email_data'] = $confirmation_email_data;
            }
            
            if(isset($_POST['confirmed-email'])) {
                $confirmed_email_data = $_POST['confirmed-email'];
                if(is_array($confirmed_email_data)) {

                    if(isset($confirmed_email_data['email-subject'])) {
                        $confirmed_email_data['email-subject'] = sanitize_text_field( $confirmed_email_data['email-subject'] );
                    }
                    if(isset($confirmed_email_data['email-content'])) {
                        $confirmed_email_data['email-content'] = sanitize_text_field( htmlentities( $confirmed_email_data['email-content'] ) );
                    }
                }
                $subscription_data['confirmed_email_data'] = $confirmed_email_data;
            }
            
            if(isset($_POST['notification-email'])) {
                $notification_email_data = $_POST['notification-email'];
                if(is_array($notification_email_data)) {

                    if(isset($notification_email_data['email-subject'])) {
                        $notification_email_data['email-subject'] = sanitize_text_field( $notification_email_data['email-subject'] );
                    }
                    if(isset($notification_email_data['email-content'])) {
                        $notification_email_data['email-content'] = sanitize_text_field( htmlentities( $notification_email_data['email-content'] ) );
                    }
                }
                $subscription_data['notification_email_data'] = $confirmed_email_data;
            }
            update_option('_subscription_data', $subscription_data);
        }
        wp_redirect($_POST['_wp_http_referer']);
    }
    
    function user_subscription_settings($data) {
        $subscription_data = get_option('_subscription_data');
        $checked = '';
        $confirmation_email_subject = '';
        $sent_from = '';
        $reply_email = '';
        $confirmation_email_content = '';
        $confirmed_email_subject = '';
        $confirmed_email_content = '';
        $notification_email_subject = '';
        $notification_email_content = '';
        $selected_categories = '';
        $subscribe_button_title = '';
        $notification_frequency_title = '';
        $select_categories_title = '';
        $subscribe_button_text = '';
        if(!empty($subscription_data)) {
            
            if(isset($subscription_data['enable_floating_button']) && $subscription_data['enable_floating_button'] == 'yes') {
                $checked = 'checked';
            }
            
            if(isset($subscription_data['subscription_categories'])) {
                $selected_categories = $subscription_data['subscription_categories'];
            }
            
            if(isset($subscription_data['subscription_button_title'])) {
                $subscribe_button_title = $subscription_data['subscription_button_title'];
            }
            
            if(isset($subscription_data['notification_frequency_title'])) {
                $notification_frequency_title = $subscription_data['notification_frequency_title'];
            }
            
            if(isset($subscription_data['select_categories_title'])) {
                $select_categories_title = $subscription_data['select_categories_title'];
            }
            
            if(isset($subscription_data['subscribe_button_text'])) {
                $subscribe_button_text = $subscription_data['subscribe_button_text'];
            }

            if(isset($subscription_data['confirmation_email_data'])) {
                $confirmation_email_data = $subscription_data['confirmation_email_data'];
                if(isset($confirmation_email_data['email-subject'])) {
                    $confirmation_email_subject = $confirmation_email_data['email-subject'];
                }
                if(isset($confirmation_email_data['email-content'])) {
                    $confirmation_email_content = html_entity_decode($confirmation_email_data['email-content']);
                }
            }
            
            if(isset($subscription_data['confirmed_email_data'])) {
                $confirmed_email_data = $subscription_data['confirmed_email_data'];
                if(isset($confirmed_email_data['email-subject'])) {
                    $confirmed_email_subject = $confirmed_email_data['email-subject'];
                }
                if(isset($confirmed_email_data['email-content'])) {
                    $confirmed_email_content = html_entity_decode($confirmed_email_data['email-content']);
                }
            }
            
            if(isset($subscription_data['notification_email_data'])) {
                $notification_email_data = $subscription_data['notification_email_data'];
                if(isset($notification_email_data['email-subject'])) {
                    $notification_email_subject = $notification_email_data['email-subject'];
                }
                if(isset($notification_email_data['email-content'])) {
                    $notification_email_content = html_entity_decode($notification_email_data['email-content']);
                }
            }

        }
        ?>
        <div id="user-subscription-tabs">
            <ul>
            <li><a href="#general-settings"><?php _e('General Settings', 'user-subscription') ?></a></li>
              <li><a href="#confirmation-email"><?php _e('Confirmation Email', 'user-subscription') ?></a></li>
              <li><a href="#confirmed-user"><?php _e('Email Confirmed User', 'user-subscription') ?></a></li>
              <li><a href="#notification-email"><?php _e('Post Notification Email', 'user-subscription') ?></a></li>
            </ul>
            
            <div id="general-settings" class="us-tabs-wrapper">

                <div class="field field_type">
                    <table class="widefat us-input-table row_layout">
                        <tbody>
                            <tr class="row">
                                <td>
                                    <table class="widefat us_input">
                                        <tbody>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="enable-floating-button"><?php _e('Enable Floating Button', 'user-subscription'); ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="" id="enable-floating-button" name="enable-floating-button" type="checkbox" value="yes" <?php echo esc_attr($checked); ?> />
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="button-title"><?php _e('Button Title', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="button-title" name="subscription-button-title" type="text" value=" <?php echo esc_attr($subscribe_button_title); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value Subscribe', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="notification-frequency-title"><?php _e('Notification Frequency Title', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="notification-frequency-title" name="notification-frequency-title" type="text" value=" <?php echo esc_attr($notification_frequency_title); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value Select frequency of emails', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="select-categories-title"><?php _e('Select Category Title', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="select-categories-title" name="select-categories-title" type="text" value=" <?php echo esc_attr($select_categories_title); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value Select Categories', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="subscribe-button-text"><?php _e('Submit Button Text', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="subscribe-button-text" name="subscribe-button-text" type="text" value=" <?php echo esc_attr($subscribe_button_text); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value Subscribe', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="subscription-categories"><?php _e('Subscription Categories', 'user-subscription'); ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <?php $categories=get_categories('hide_empty=0'); ?>
                                                            <select class="widefat" id="subscription-categories" name="subscription-categories[]" multiple="true">
                                                                <option value=""><?php _e('Select Categories'); ?></option>
                                                                <?php
                                                                if(!empty($categories)) {
                                                                    foreach($categories as $category) {
                                                                        $selected = '';
                                                                        if(is_array($selected_categories) && in_array($category->term_id, $selected_categories)) {
                                                                            $selected = 'selected="selected"';
                                                                        }
                                                                ?>
                                                                        <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($category->cat_name); ?></option>
                                                                <?php
                                                                    }
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="confirmation-email" class="us-tabs-wrapper">
        
                <div class="field field_type">
                    <table class="widefat us-input-table row_layout">
                        <tbody>
                            <tr class="row">
                                <td>
                                    <table class="widefat us_input">
                                        <tbody>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="confirmation-email-subject"><?php _e('Email Subject', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="confirmation-email-subject" name="confirmation-email[email-subject]" type="text" value=" <?php echo esc_attr($confirmation_email_subject); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value - Please confirm your subscription to ', 'user-subscription').get_bloginfo( 'name' ); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="confirmation-email-content"><?php _e('Email Content', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <?php
                                                            $settings = array( 'media_buttons' => false, 'textarea_rows' => 8, 'textarea_name' => 'confirmation-email[email-content]' );
                                                            wp_editor($confirmation_email_content, 'confirmation-email-content', $settings );
                                                            ?>
                                                            <p class="description"><?php _e('Use %user_email% and %activate_link% as placeholder for user email and activation link.', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <div id="confirmed-user" class="us-tabs-wrapper">
                <div class="field field_type">
                    <table class="widefat us-input-table row_layout">
                        <tbody>
                            <tr class="row">
                                <td>
                                    <table class="widefat us_input">
                                        <tbody>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="confirmed-email-subject"><?php _e('Email Subject', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="confirmed-email-subject" name="confirmed-email[email-subject]" type="text" value=" <?php echo esc_attr($confirmed_email_subject); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value - Thank-you for subscribing!', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="confirmed-email-content"><?php _e('Email Content', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <?php
                                                            $settings = array( 'media_buttons' => false, 'textarea_rows' => 8, 'textarea_name' => 'confirmed-email[email-content]' );
                                                            wp_editor($confirmed_email_content, 'confirmed-email-content', $settings );
                                                            ?>
                                                            <p class="description"><?php _e('Use %user_email% and %unsubscribe_link% as placeholder for user email and unsusbscribe link.', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="notification-email" class="us-tabs-wrapper">
                <div class="field field_type">
                    <table class="widefat us-input-table row_layout">
                        <tbody>
                            <tr class="row">
                                <td>
                                    <table class="widefat us_input">
                                        <tbody>
                                            
                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="notification-email-subject"><?php _e('Email Subject', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <input class="widefat" id="notification-email-subject" name="notification-email[email-subject]" type="text" value=" <?php echo esc_attr($notification_email_subject); ?>" />
                                                            <p class="description"><?php echo __('Leave empty for default value - Notification from ', 'user-subscription').get_bloginfo( 'name' );; ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr class="field sub_field">
                                                <td class="label">
                                                    <label for="notification-email-content"><?php _e('Email Content', 'user-subscription') ?></label>
                                                    <span class="sub-field-instructions"></span>
                                                </td>
                                                <td>
                                                    <div class="inner">
                                                        <div class="zd-input-wrap">
                                                            <?php
                                                            $settings = array( 'media_buttons' => false, 'textarea_rows' => 8, 'textarea_name' => 'notification-email[email-content]' );
                                                            wp_editor($notification_email_content, 'notification-email-content', $settings );
                                                            ?>
                                                            <p class="description"><?php _e('Use %user_email% and %unsubscribe_link% as placeholder for user email and unsusbscribe link. And use %post_loop% as placeholder to display post title in list format.', 'user-subscription'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
          </div>
       <?php
    }

 }

$user_subscription_settings = new User_Subscription_Settings_Page();