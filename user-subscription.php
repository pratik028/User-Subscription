<?php

/* Plugin Name: User Subscription
   Plugin URI: http://www.wp-develop.com/
   Description: Notifies an email list when new entries are posted.
   Version: 1.0
   Author: pratik028
   Text Domain: user-subscription
   Domain Path: /languages
   License: GPL
   License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
if( is_admin() ) {
    require_once( dirname( __FILE__ ) . '/admin/user-subscription-settings.php' );
    require_once( dirname( __FILE__ ) . '/admin/user-subscription-details.php' );
}

function user_subscription_intervals($schedules) {
	// add a 'weekly' interval
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => __('Once Weekly')
	);
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => __('Once a month')
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'user_subscription_intervals');


function user_subscription_activation_hook() {
    if (! wp_next_scheduled ( 'user_subscription_daily_event' )) {
	wp_schedule_event(time(), 'hourly', 'user_subscription_daily_event');
    }
    if (! wp_next_scheduled ( 'user_subscription_weekly_event' )) {
	wp_schedule_event(time(), 'hourly', 'user_subscription_weekly_event');
    }
    if (! wp_next_scheduled ( 'user_subscription_monthly_event' )) {
	wp_schedule_event(time(), 'hourly', 'user_subscription_monthly_event');
    }
}

register_activation_hook(__FILE__, 'user_subscription_activation_hook');

register_deactivation_hook(__FILE__, 'user_subscription_deactivation_hook');

function user_subscription_deactivation_hook() {
    wp_clear_scheduled_hook('user_subscription_daily_event');
    wp_clear_scheduled_hook('user_subscription_weekly_event');
    wp_clear_scheduled_hook('user_subscription_monthly_event');
}

function user_subscription_daily_event_callback() {
    
    user_subscription_send_email('daily');
}

add_action('user_subscription_daily_event', 'user_subscription_daily_event_callback');

function user_subscription_weekly_event_callback() {
    user_subscription_send_email('weekly');
}

add_action('user_subscription_weekly_event', 'user_subscription_weekly_event_callback');

function user_subscription_monthly_event_callback() {
    user_subscription_send_email('monthly');
}

add_action('user_subscription_monthly_event', 'user_subscription_monthly_event_callback');

function user_subscribers_post_type() {
    register_post_type('user-subscribers',
                       [
                           'labels'      => [
                               'name'          => __('User Subscribers', 'user-subscription'),
                               'singular_name' => __('Subscriber', 'user-subscription'),
                            ],
                           'public'      => false,
                           'has_archive' => false,
                       ]
    );
    
    //Activate Subscriber
    if(isset($_GET['activation_key']) && $_GET['activation_key'] != '') {
        $activation_key = $_GET['activation_key'];
        $args = array( 'post_type' => 'user-subscribers', 'post_status' => 'any', 'meta_key' => '_subscriber_activation_key', 'meta_value' => $activation_key );
        $subscriber = get_posts( $args );
        if(!empty($subscriber)) {
            $subscriber_id = $subscriber[0]->ID;
            update_post_meta($subscriber_id, '_subscriber_activation_key', '');
            if($subscriber_email) {
                user_subscriber_send_email('confirmed-user-mail', $subscriber_id);
            }
        }
    }

    //Unsubscribe User
    if(isset($_GET['unsubscribe']) && $_GET['unsubscribe'] != '') {
        $unsubscribe_id = $_GET['unsubscribe'];
        wp_delete_post( $unsubscribe_id, true );
    }
}
add_action('init', 'user_subscribers_post_type');

function user_subscription_frontend_script() {
    wp_enqueue_style( 'user-subscription-front-style', plugins_url( '/public/css/user-subscription.css', __FILE__ ) );
    wp_enqueue_script( 'user-subscription-front-script', plugins_url( '/public/js/user-subscription.js', __FILE__ ), array( 'jquery' ) );
    wp_localize_script('user-subscription-front-script', 'ajax_object', array('ajaxurl' => admin_url( 'admin-ajax.php' ),));
}

add_action( 'wp_enqueue_scripts', 'user_subscription_frontend_script');

function user_subscription_footer_data() {
    $subscription_data = get_option('_subscription_data');
    $enable_floating_button = '';
    if(!empty($subscription_data)) {
        if(isset($subscription_data['enable_floating_button']) && $subscription_data['enable_floating_button'] == 'yes') {
            $enable_floating_button = 'yes';
        }
    }
    
    if($enable_floating_button == 'yes') {
        user_subscription_structure();
    }
    
}

add_action('wp_footer', 'user_subscription_footer_data');


function user_subscription_structure() {
    $subscription_data = get_option('_subscription_data');
    $selected_categories = '';
    $subscribe_button_title = __('Subscribe', 'user-subscription');
    $notification_frequency_title = __('Select frequency of emails', 'user-subscription');
    $select_categories_title = __('Select Categories', 'user-subscription');
    $subscribe_button_text = __('Subscribe', 'user-subscription');
    if( !empty($subscription_data) ) {
        if(isset($subscription_data['subscription_categories'])) {
            $selected_categories = $subscription_data['subscription_categories'];
        }
        
        if(isset($subscription_data['subscription_button_title']) && $subscription_data['subscription_button_title'] != '') {
            $subscribe_button_title = $subscription_data['subscription_button_title'];
        }

        if(isset($subscription_data['notification_frequency_title']) && $subscription_data['notification_frequency_title'] != '') {
            $notification_frequency_title = $subscription_data['notification_frequency_title'];
        }

        if(isset($subscription_data['select_categories_title']) && $subscription_data['select_categories_title'] != '') {
            $select_categories_title = $subscription_data['select_categories_title'];
        }

        if(isset($subscription_data['subscribe_button_text']) && $subscription_data['subscribe_button_text'] != '') {
            $subscribe_button_text = $subscription_data['subscribe_button_text'];
        }
    }
    ?>
        <div class="subscription_form">
          <div class="subscribe_outter_wrap">
            <a class="subscribe_button"><?php echo esc_html($subscribe_button_title);; ?></a>
            <div class="subscription_form_wrap">
              <!-- form data -->
              <div id="confirm_mail_new_user">
                <button type="button" class="close" ><span aria-hidden="true">&times;</span></button>
                <form id="subscribe-user" method="post" class="form-horizontal subscribe-form" role="form">
                    <div class="login-form-inner-container">
                        <p class="status"></p>
                        <div style="display: none;" id="submit_status"></div>
                        <p id="subscribe-email">
                            <input type="email" id="user_email" name="user_email" placeholder="your e-mail address" />
                        </p>
                        <div class="notifications-wapper">
                            <p class="subscibe-form-notifications">
                                <span class="notifications notifications-title"><?php echo esc_html($notification_frequency_title); ?></span>
                                <div class="radio-outer">
                                    <input type="radio" id="daily" name="notification" value="daily" checked />
                                    <label for="daily"><?php echo __('Daily', 'user-subscription'); ?></label>
                                </div>
                                <div class="radio-outer">
                                    <input type="radio" id="weekly" name="notification" value="weekly">
                                    <label for="weekly"><?php echo __('Weekly', 'user-subscription'); ?></label>
                                </div>
                                <div class="radio-outer">
                                    <input type="radio" id="monthly" name="notification" value="monthly">
                                    <label for="monthly"><?php echo __('Monthly', 'user-subscription'); ?></label>
                                </div>
                            </p>
                        </div>
                        <?php if($selected_categories) { ?>
                        <div class="categories-wapper-title"><?php echo esc_html($select_categories_title); ?></div>
                            <?php foreach($selected_categories as $category) { ?>
                                    <input id="<?php echo esc_attr($category); ?>" type="checkbox" name="categories[]" value="<?php echo $category; ?>">
                                    <label for="<?php echo esc_attr($category); ?>"><?php echo esc_html(get_cat_name($category));?></label>
                                    <br />
                            <?php    
                            }
                        }
                        ?>
                        <p id="subscribe-submit">
                            <input class="submit_button btn btn-default" type="submit" value="<?php echo esc_attr($subscribe_button_text); ?>" name="submit_user_subscription" >
                        </p>
                    </div>
                    <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
                </form>
            </div>
              <!-- end of form data -->
             <div class="clearfix"></div>
            </div>

          </div>
        </div>
<?php
}


function subscribe_user_form_handler(){
    parse_str($_POST['form_data'], $FORMPOST);
    $user_email = $FORMPOST['user_email'];
    $notification = $FORMPOST['notification'];
    $categories = $FORMPOST['categories'];
    if(! $categories) {
        $categories = 'all';
    }
    $args = array( 'post_type' => 'user-subscribers', 'post_status' => 'any', 'meta_key' => '_subscriber_email', 'meta_value' => $user_email );
    $subscriber = get_posts( $args );
    
    if(empty($subscriber)) {
        $activation_key = rand();
        $todays_date = date("Y-m-d");
        $new_subscriber = wp_insert_post(array('post_type' => 'user-subscribers'));
        add_post_meta($new_subscriber, '_subscriber_email', $user_email);
        add_post_meta($new_subscriber, '_subscriber_notification', $notification);
        add_post_meta($new_subscriber, '_subscriber_categories', $categories);
        add_post_meta($new_subscriber, '_subscriber_activation_key', $activation_key);
        add_post_meta($new_subscriber, '_subscription_date', $todays_date);
        user_subscriber_send_email('confirmation-mail', $new_subscriber);
        echo json_encode(array('subscribe'=>true, 'message'=>__('An email was just sent to confirm your subscription. Please find the email now and click activate to start subscribing.')));
        die();
    } else {
        $subscriber_id = $subscriber[0]->ID;
        update_post_meta($subscriber_id, '_subscriber_notification', $notification);
        update_post_meta($subscriber_id, '_subscriber_categories', $categories);
        echo json_encode(array('subscribe'=>false, 'message'=>__('Your subscription is updated!', 'subscribe')));
        exit;
    }
}

add_action( 'wp_ajax_subscribe_user', 'subscribe_user_form_handler' );
add_action( 'wp_ajax_nopriv_subscribe_user', 'subscribe_user_form_handler' );

function subscriber_post_unpublished( $new_status, $old_status, $post ) {

    if ( $old_status == 'publish'  &&  $new_status != 'publish' && $new_status != 'auto-draft' ) {
        return;
    } elseif( $old_status == 'draft' && $new_status == 'publish' || $old_status == 'auto-draft' && $new_status == 'publish' ) {
        global $post;
        $curreent_post_id = $post->ID;
        $args = array( 'post_type' => 'user-subscribers', 'post_status' => 'any', 'meta_key' => '_subscriber_activation_key', 'meta_value' => '' );
        $subscribers = get_posts( $args );
        foreach ( $subscribers as $subscriber ) : setup_postdata( $subscriber );
            $flag = false;
            $subscriber_categories = get_post_meta($subscriber->ID, '_subscriber_categories', true);
            if($subscriber_categories == 'all') {
                $flag = true;
            } else {
                $categories = wp_get_post_categories($curreent_post_id);
                $common_categories = array_intersect($categories, $subscriber_categories);
                if (! empty($common_categories)) {
                    $flag = true;
                }
            }
            
            if($flag) {
                $updated_posts_ids = get_post_meta($subscriber->ID, '_updated_post_ids', true);
                if(!empty($updated_posts_ids)) {
                    if(! in_array($curreent_post_id, $updated_posts_ids)) {
                        array_push($updated_posts_ids, $curreent_post_id);
                    }
                } else {
                    $updated_posts_ids[] = $curreent_post_id;
                }
                update_post_meta($subscriber->ID, '_updated_post_ids', $updated_posts_ids);
            }

        endforeach;
        wp_reset_postdata();
    }

}

add_action( 'transition_post_status', 'subscriber_post_unpublished', 10, 3 );

function user_subscription_send_email($notification) {
    
    $args = array( 
        'post_type' => 'user-subscribers',
        'post_status' => 'any',
        'meta_query'    =>  array(
            'relation'  =>  'AND',
            array(
               'key'    =>  '_subscriber_activation_key',
                'value' =>  '',
                'compare'   =>  '='
            ),
            array(
               'key'    =>  '_subscriber_notification',
                'value' =>  'daily',
                'compare'   =>  '='
            ),
            array(
               'key'    =>  '_updated_post_ids',
                'value' =>  '',
                'compare'   =>  '!='
            ),
            array(
               'key'    =>  '_subscriber_email',
                'value' =>  '',
                'compare'   =>  '!='
            ),
        )
    );
    $subscribers = get_posts( $args );
    
    if(!empty($subscribers)) {
        foreach ( $subscribers as $subscriber ) : setup_postdata( $subscriber );
            user_subscriber_send_email('notification-mail', $subscriber->ID);
        endforeach;
        wp_reset_postdata();
    }
}

function user_subscriber_send_email($mail_type = 'confirmation-email', $subscriber_id = '') {
    $subscription_data = get_option('_subscription_data');
    if(!empty($subscription_data)) {
        if($mail_type) {
            $subscriber_email = get_post_meta($subscriber_id, '_subscriber_email', true);
            $url = get_site_url();
            $website_name = get_bloginfo( 'name' );
            $subject = 'Update from '.$website_name;
            if($mail_type == 'confirmation-mail') {
                $activation_key = get_post_meta($subscriber_id, '_subscriber_activation_key', true);
                $subject = 'Please confirm your subscription to '.$website_name;
                $message = '<a href="'.esc_url($url).'/activation_key='.$activation_key.'">Clik here</a>';
                if(isset($subscription_data['confirmation_email_data'])) {
                    $confirmation_email_data = $subscription_data['confirmation_email_data'];
                    if(isset($confirmation_email_data['email-subject']) && $confirmation_email_data['email-subject'] !== '') {
                        $email_subject = esc_html($confirmation_email_data['email-subject']);
                    }
                    if(isset($confirmation_email_data['email-content']) && $confirmation_email_data['email-content'] !== '') {
                        $message = $confirmation_email_data['email-content'];
                        if($activation_key) {
                            $message = str_replace("%activate_link%","$url/?activation_key=$activation_key",$message);
                        }
                        $message = str_replace("%user_email%",$user_email,$message);
                        $message = apply_filters('the_content', $message);
                        
                    }
                    
                }
                
            } else if($mail_type == 'confirmed-user-mail') {
                    $subject = 'Thank-you for subscribing!';
                    $message = 'Thank-you for subscribing to our blog';
                    $confirmed_email_data = $subscription_data['confirmed_email_data'];
                    if(isset($confirmed_email_data['email-subject']) && $confirmed_email_data['email-subject'] !== '') {
                        echo $email_subject = esc_html($confirmed_email_data['email-subject']);
                    }
                    if(isset($confirmed_email_data['email-content']) && $confirmed_email_data['email-content'] !== '') {
                        $message = $confirmed_email_data['email-content'];
                        $message = apply_filters('the_content', $message);
                        $message = str_replace("%unsubscribe_link%","$url/?unsubscribe=$subscriber_id",$message);
                        $message = str_replace("%user_email%", $email->email, $message);
                    }

            } else if($mail_type == 'notification-mail') {
                $subject = 'Notification from '.$website_name;
                $message = '';
                $notification_email_data = $subscription_data['notification_email_data'];
                if(isset($notification_email_data['email-subject']) && $notification_email_data['email-subject'] !== '') {
                    echo $email_subject = esc_html($notification_email_data['email-subject']);
                }
                if(isset($notification_email_data['email-content']) && $notification_email_data['email-content'] !== '') {
                    $message = $notification_email_data['email-content'];
                    $updated_posts = get_post_meta($subscriber_id, '_updated_post_ids', true);
                    $post_content = '';
                    if($updated_posts) {
                        $post_content .= '<ul>';
                        foreach($updated_posts as $updated_post) {
                            $post_content .= '<li><a href="'.esc_url(get_permalink($updated_post)).'">'+ esc_html(get_the_title($updated_post)) +'</a></li>';
                        }
                        $post_content .= '</ul>';
                    }
                    $message = str_replace('%post_loop%', $post_content, $message);
                    $message = str_replace("%unsubscribe_link%","$url/?unsubscribe=$subscriber_id",$message);
                    $message = str_replace("%user_email%",$subscriber_email,$message);
                    
                    update_post_meta($subscriber_id, '_updated_post_ids', '');
                    
                }

            }
            wp_mail( $subscriber_email, $subject, $email_message, $headers );
        }
    }
}