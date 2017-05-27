<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

function subscribers_log_menu_items(){
   $hook = add_submenu_page('user-subscription-settings', __('Subscription Log', 'user-subscription'), __('Subscription Log', 'user-subscription'), 'manage_options', 'user-subscription-log', 'user_subscription_log');
    add_action( "load-$hook", 'add_options' );
}
function add_options() {
    global $myLogTable;
    $option = 'per_page';
    $args = array(
           'label' => 'Subscribers',
           'default' => 10,
           'option' => 'logs_per_page'
           );
    add_screen_option( $option, $args );
    $myLogTable = new User_Subscription_Log_Table();
}
add_action( 'admin_menu', 'subscribers_log_menu_items' );
function user_subscription_log(){
    global $myLogTable;
    $myLogTable->prepare_items(); 
    $message = '';
    if ('delete' === $myLogTable->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Subscriber deleted: %d', 'am'), count($_REQUEST['email'])) . '</p></div>';
    }
?>
    <div class="wrap">
        <h2><?php _e('Subscribers', 'user-subscription'); ?></h2>
        <?php echo $message; ?>
        
        <form method="post">
            <?php $myLogTable->display(); ?>
        </form>
    
    </div>
<?php
}

class User_Subscription_Log_Table extends WP_List_Table {
    
    function __construct(){
        global $status, $page;
            parent::__construct( array(
                'singular'  => __( 'log', 'user-subscription' ),     //singular name of the listed records
                'plural'    => __( 'logs', 'user-subscription' ),   //plural name of the listed records
                'ajax'      => false        //does this table support ajax?
        ) );
        add_action( 'admin_head', array( &$this, 'admin_header' ) );            
    }
    
    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'my_list_test' != $page )
        return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-product { width: 40%; }';
        echo '.wp-list-table .column-url { width: 35%; }';
        echo '.wp-list-table .column-role { width: 20%;}';
        echo '</style>';
    }
    
    function no_items() {
        _e( 'No records found.', 'user-subscription' );
    }
    
    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'email':
            case 'categories':
            case 'notification':
            case 'date':
            case 'status':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }
    
    function get_sortable_columns() {
      $sortable_columns = array(
//        'product'  => array('product',false)
      );
      return $sortable_columns;
    }
    
    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'email'         => __( 'Email', 'user-subscription' ),
            'categories'    => __( 'Categories', 'user-subscription' ),
            'notification'  => __( 'Notifications', 'user-subscription' ),
            'status'        => __( 'Status', 'user-subscription' ),
            'date'          => __( 'Date', 'user-subscription' ),
        );
         return $columns;
    }
    
    function column_email($item){
        $actions = array(
                'delete'    => sprintf('<a href="?page=%s&action=%s&email=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
            );
      return sprintf('%1$s %2$s', $item['email'], $this->row_actions($actions) );
    }
    
    function get_bulk_actions() {
        $actions = array(
          'delete'    => 'Delete'
        );
      return $actions;
    }
    
    function process_bulk_action() {
        global $wpdb;
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['email']) ? $_REQUEST['email'] : array();
            if (is_array($ids)) {
                foreach($ids as $id) {
                    wp_delete_post( $id, true );
                }
            } else {
                wp_delete_post( $ids, true );
            }
        }
    }
    
    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="email[]" value="%s" />', $item['ID']
        );    
    }
    
    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $total_items = 0;
        $this->process_bulk_action();
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $paged = isset($_REQUEST['paged']) ?intval($_REQUEST['paged']) : 0;
        $subscriber_args = array(
            'post_type'        => 'user-subscribers',
            'post_status'      => 'any',
            'posts_per_page'   => $per_page,
            'paged'            => $paged
        );
        $subscribers = get_posts($subscriber_args);
        $subscriber_details = '';
        if(!empty($subscribers)) {
            $total_items = wp_count_posts('user-subscribers');
            $total_items = $total_items->draft;
            foreach ( $subscribers as $subscriber ) : setup_postdata( $subscriber );
                $subscriber_id = $subscriber->ID;
                $email = get_post_meta($subscriber_id, '_subscriber_email', true);
                $notification = get_post_meta($subscriber_id, '_subscriber_notification', true);
                $categories = get_post_meta($subscriber_id, '_subscriber_categories', true);
                $cat_names = '';
                if(is_array($categories)) {
                    foreach($categories as $category) {
                        $cat_names[] = get_cat_name( $category );
                    }
                    $categories = implode(" ",$cat_names);
                }
                
                $date = get_the_date('', $subscriber_id);
                $status = get_post_meta($subscriber_id, '_subscriber_activation_key', true);
                if($status == '') {
                    $status = 'Active';
                } else {
                    $status = 'Inactive';
                }
                
                $subscriber_details[] = array(
                    'ID'    => $subscriber_id,
                    'email' => $email,
                    'categories' => $categories,
                    'notification' => $notification,
                    'status' => $status,
                    'date' => $date,
                );
            endforeach;
            wp_reset_postdata();
        }
      $this->set_pagination_args( array(
        'total_items' => $total_items,
        'per_page'    => $per_page
      ) );
      $this->items = $subscriber_details;
    }
    
} //class