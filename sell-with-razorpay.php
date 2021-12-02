<?php
/*
  Plugin Name: Pay Karo
  Plugin URI: https://www.saiwebpro.blogspot.com
  Description: Product manage & razorpay gateway use for payment process
  Version: 1.0
  Author: saikumarbhimarasetty
  Author URI: https://www.saiwebpro.in/
  License: GPL2
 */
if ( !defined( 'ABSPATH' ) )
    exit;


$api_key = esc_attr( get_option( 'swr_api_key' ) );
$api_secret = esc_attr( get_option( 'swr_api_secret' ) );
$api_url = esc_attr( get_option( 'swr_api_url' ) );


define( 'SWR_PLUGIN_PATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' );
define( 'SWR_PLUGIN_TEMPLATE_PATH', dirname(__FILE__) . '/includes' );
define( 'RS_SYMBOL', '&#x20B9;' );


function swr_enqueue_scripts() {  
    
    wp_register_style( 'swr-main-css', plugins_url( '/assets/css/swr-main.css', __FILE__ ) );
    wp_enqueue_style( 'swr-main-css' );
    
    wp_register_script( 'swr-checkout-form', plugins_url( '/assets/js/swr-checkout-form.js', __FILE__ ), array(), '', 1 );
    wp_enqueue_script('swr-checkout-form');
    
    wp_register_script( 'swr-popup-form', plugins_url( '/assets/js/swr-popup.js', __FILE__ ), array(), '', 1 );
    wp_enqueue_script( 'swr-popup-form' );
    
    wp_register_script( 'swr-jquery-validate', plugins_url( '/assets/js/jquery.validate.js', __FILE__ ), array(), '', 1 );
    wp_enqueue_script( 'swr-jquery-validate' );
}
add_action( 'wp_enqueue_scripts', 'swr_enqueue_scripts' );


function swr_admin_enqueue_scripts() {
    
    wp_register_style( 'swr-jquery-datepicker', plugins_url( '/assets/css/datepicker.css', __FILE__ ) );
    wp_enqueue_style( 'swr-jquery-datepicker' );
    wp_enqueue_style( 'thickbox' );
    
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_script( 'media-upload' );
    
    wp_enqueue_media();
    
}
add_action( 'admin_enqueue_scripts', 'swr_admin_enqueue_scripts' );


function swr_admin_ajaxurl() {
    
    echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";
         </script>';
}
add_action( 'wp_head', 'swr_admin_ajaxurl' );


/*
 * Display error when razorpay setting not configure in admin side
 */

function swr_razorpay_admin_notice_error() {
    $class = 'notice notice-error';
    $message = __( 'Pay Karo API setting is required.', 'sample-text-domain' );

    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
}


if ( empty( $api_key ) || empty( $api_secret ) || empty( $api_url ) ) {
    
    add_action( 'admin_notices', 'swr_razorpay_admin_notice_error' );
    
} else {
    
    define( 'razorpay_url', get_option( 'swr_api_url' ) );
    define( 'key_id', get_option( 'swr_api_key' ) );
    define( 'key_secret', get_option( 'swr_api_secret' ) );
}


/*
 * Add setting option while activete plugin
 */

function swr_plugin_action_redirect_link( $redirect_links ) {
    
   $redirect_links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=swr-setting-page') ) .'">Settings</a>';
   return $redirect_links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'swr_plugin_action_redirect_link' );


include_once plugin_dir_path ( __FILE__ ) . '/includes/product-meta-manager.php';
include_once plugin_dir_path ( __FILE__ ) . '/includes/category-template.php';
include_once plugin_dir_path ( __FILE__ ) . '/admin/swr-setting-page.php';
include_once plugin_dir_path ( __FILE__ ) . '/admin/swr-order-detail.php';
include_once plugin_dir_path ( __FILE__ ) . '/admin/swr-order-detail-download.php';
include_once plugin_dir_path ( __FILE__ ) . '/lib/razorpay-php/Razorpay.php';

use Razorpay\Api\Api;

/*
 * Add menu for display razorpay setting and order list in admin
 */

function swr_register_custom_submenu_page() {
    add_submenu_page
        (
            "options-general.php",
            "Pay Karo Setting",
            "Pay Karo Setting",
            "manage_options",
            "swr-setting-page",
            "swr_setting_page_callback"
        );
    add_submenu_page
        (
            NULL,
            "Pay Karo Order List",
            "Pay Karo Order List",
            "manage_options",
            "download_swr_order_list",
            "download_swr_order_list"
        );
}
add_action( 'admin_menu', 'swr_register_custom_submenu_page' );


/*
 *  Auto-Create table and pages while active plugin
 */

register_activation_hook( __FILE__, 'swr_custom_plugin_activation' );
function swr_custom_plugin_activation() {
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    global $wpdb;
    $swr_create_order_table_name = $wpdb->prefix . 'swr_transaction';
    $shipping_addr_table_name = $wpdb->prefix . 'swr_shipping_address';

    if ( $wpdb->get_var( "SHOW TABLES LIKE '$shipping_addr_table_name'" ) != $shipping_addr_table_name ) {
        
        if ( !empty( $wpdb->charset ) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        
        if ( !empty( $wpdb->collate ) )
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql_rp = "CREATE TABLE " . $shipping_addr_table_name . " (
                         id int(10) unsigned NOT NULL AUTO_INCREMENT,
                         user_id int(10) NOT NULL,
                         first_name varchar(200) NOT NULL,
                         last_name varchar(200) NOT NULL,
                         email varchar(200) NOT NULL,
			 pincode varchar(200) NOT NULL,
                         address_type varchar(50) NOT NULL,
			 address text NOT NULL,
			 landmark varchar(200) NOT NULL,
			 city varchar(200) NOT NULL,
			 state varchar(200) NOT NULL,
			 country varchar(200) NOT NULL,
			 mobile varchar(200) NOT NULL,
			 PRIMARY KEY (id)
			 ) $charset_collate;";
        dbDelta($sql_rp);
    }

    if ( $wpdb->get_var( "SHOW TABLES LIKE '$swr_create_order_table_name'" ) != $swr_create_order_table_name ) {
        
        if ( !empty( $wpdb->charset ) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        
        if ( !empty( $wpdb->collate ) )
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql_rp = "CREATE TABLE " . $swr_create_order_table_name . " (
                         trans_id int(10) unsigned NOT NULL AUTO_INCREMENT,
                         user_id int(10) NOT NULL,
                         shipping_id int(10) NOT NULL,
                         order_id varchar(100) NOT NULL,
                         customer_name varchar(200) NOT NULL,
                         customer_email varchar(200) NOT NULL,
                         mobile varchar(50) NOT NULL,
                         customer_mobile varchar(200) NOT NULL,
                         payment_id varchar(250) NOT NULL,
                         product_name varchar(250) NOT NULL,
                         product_id int(10) NOT NULL,
                         total_amount varchar(200) NOT NULL,
  			 shipping_fare varchar(30) NOT NULL,
                         currency varchar(200) NOT NULL,
                         payment_status varchar(200) NOT NULL,
                         payment_type varchar(200) NOT NULL,
                         description text NOT NULL,
                         shipping varchar(100) NOT NULL,
                         created_at varchar(200) NOT NULL,
                         PRIMARY KEY (trans_id)
                         ) $charset_collate;";
        dbDelta( $sql_rp );
    }
    $user_id = get_current_user_id();
    
    if ( get_page_by_title( 'Payment Process' ) == NULL ) {
        $create_page = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $user_id,
            'post_name' => 'payment-process',
            'post_status' => 'publish',
            'post_title' => 'Payment Process',
            'post_content' => '[razorpay-payment-form]',
            'post_type' => 'page',
        );
        //insert page and save the id
        $payment_page = wp_insert_post( $create_page );
    }
    
    if ( get_page_by_title( 'Checkout' ) == NULL ) {
        $checkout_post = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $user_id,
            'post_name' => 'checkout',
            'post_status' => 'publish',
            'post_title' => 'Checkout',
            'post_content' => '[razorpay-checkout-page]',
            'post_type' => 'page',
        );
        //insert page and save the id
        $checkout_page = wp_insert_post( $checkout_post );
    }
    
    if ( get_page_by_title( 'Thank you!' ) == NULL ) {
        $thankyou_post = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $user_id,
            'post_name' => 'thank-you',
            'post_status' => 'publish',
            'post_title' => 'Thank you!',
            'post_content' => '<h4>Your order has been received</h4><p>You will receive an order confirmation email.</p>',
            'post_type' => 'page',
        );
        //insert page and save the id
        $thankyou_page = wp_insert_post( $thankyou_post );
    }
    
    if ( get_page_by_title( 'Product List' ) == NULL ) {
        $product_list = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $user_id,
            'post_name' => 'product-list',
            'post_status' => 'publish',
            'post_title' => 'Product List',
            'post_content' => '[display_product_list]',
            'post_type' => 'page',
        );
        //insert page and save the id
        $productlist_page = wp_insert_post( $product_list );
    }
    
    if ( get_page_by_title( 'My Account' ) == NULL ) {
        $edit_profile_post = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $user_id,
            'post_name' => 'my-account',
            'post_status' => 'publish',
            'post_title' => 'My Account',
            'post_content' => '[edit-customer-profile]',
            'post_type' => 'page',
        );
        //insert page and save the id
        $my_account_page = wp_insert_post( $edit_profile_post );
    }
    
    if ( get_page_by_title( 'My Order' ) == NULL ) {
        $my_order_post = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $user_id,
            'post_name' => 'my-order',
            'post_status' => 'publish',
            'post_title' => 'My Order',
            'post_content' => '[my-order-list-page]',
            'post_type' => 'page',
        );
        //insert page and save the id
        $my_order_page = wp_insert_post( $my_order_post );
    }
}

/*
 * Payment-Process Page Function
 */
function swr_payment_process_callback() {
    
    date_default_timezone_set( 'Asia/Calcutta' );
    global $wpdb, $current_user;

    $shipping_tbl_name = $wpdb->prefix . 'swr_shipping_address';
    $errors = array();

    if ( !empty( $_SESSION['h_productID'] ) ) {

        $api = new Api( key_id, key_secret );

        if ( empty( $_POST['razorpay_payment_id'] ) ) {
            echo "<p>Your Payment Is Being Processed Please don't click the back or refresh buttons.</p>";

            if ( ( isset( $_POST['guest_submit'] ) && wp_verify_nonce( $_POST['guest_submit_field'], 'guest-submit-form' ) ) || ( isset ( $_POST['submit'] ) && wp_verify_nonce( $_POST['submit_order_field'], 'submit-order-action' ) ) ) {                
               
                $productID = $_SESSION['h_productID'];
                $productName = get_post($productID)->post_title;
                $productPrice = get_post_meta( $productID, 'sell_price', true );
                $shipping_charge = '';
                
                if ( get_option( 'swr_shipping_amount_checked', true ) == 1 ) {
                    
                    $totalAmount = $productPrice;
                    
                } else {
                    
                    $shipping_charge = get_option( 'swr_shipping_amount', true );
                    $totalAmount = $productPrice + $shipping_charge;
                }
                $shipping_rate = get_option( 'swr_shipping_amount', true ) ? get_option( 'swr_shipping_amount', true ) : 'Free Shipping';
                
                $random_order_id =  swr_random_paymentid_generate( 
                                            array(
                                                'characters'  =>  'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
                                                'length'      =>  14,
                                                'before'      =>  'order_'
                                                )
                                            );

                
                if ( is_user_logged_in() ) {
                    
                    $user_id = $current_user->ID;
                    $countAddress = sanitize_text_field( $_POST['result_count'] );

                    if ( $countAddress > 0 ) {
                        $selected_id = sanitize_text_field( $_POST['address_choice'] );
                        $selected_address = $wpdb->get_results( "SELECT * FROM $shipping_tbl_name WHERE id = $selected_id" );

                        foreach ( $selected_address as $cust_info ) {
                            
                            $address_type = $cust_info->address_type;
                            $cust_first_name = $cust_info->first_name;
                            $cust_last_name = $cust_info->last_name;
                            $cust_email = $cust_info->email;
                            $cust_pincode = $cust_info->pincode;
                            $cust_address = $cust_info->address;
                            $cust_landmark = $cust_info->landmark;
                            $cust_city = $cust_info->city;
                            $cust_state = $cust_info->state;
                            $cust_country_code = $cust_info->country;
                            $mobile = $cust_info->mobile;
                        }
                    } else {
                        
                        $cust_first_name = sanitize_text_field( $_POST['cust_first_name'] );
                        $cust_last_name = sanitize_text_field( $_POST['cust_last_name'] );
                        $cust_email = strtolower( sanitize_text_field( $_POST['cust_email'] ) );
                        $address_type = sanitize_text_field( $_POST['address_type'] );
                        $cust_pincode = sanitize_text_field( $_POST['cust_pincode'] );
                        $cust_address = sanitize_text_field( $_POST['cust_address'] );
                        $cust_landmark = sanitize_text_field( $_POST['cust_landmark'] );
                        $cust_city = sanitize_text_field( $_POST['cust_city'] );
                        $cust_state = sanitize_text_field( $_POST['cust_state'] );
                        $cust_country_code = sanitize_text_field($_POST[ 'country_code'] );
                        $mobile = sanitize_text_field( $_POST['mobile'] );
                        

                        if ( empty( $cust_first_name ) || empty( $cust_last_name ) || empty( $cust_email ) || empty( $address_type ) || empty( $cust_pincode ) || empty( $cust_address ) || empty( $cust_city ) || empty( $cust_state ) || empty( $mobile ) ) {
                            
                            $errors[] = "Please fill up all required field for further process";
                            
                        }

                        /****************** Insert login user shipping address detail in database ****************** */
                        $selected_id = swr_insert_address($user_id, $cust_first_name, $cust_last_name, $cust_email, $address_type, $cust_pincode, $cust_address, $cust_landmark, $cust_city, $cust_state, $cust_country_code, $mobile);
                        
                    }
                        $customer_name = $cust_first_name .' '. $cust_last_name; 

                        $transcation_id = swr_insert_transaction($user_id, $selected_id, $random_order_id, $shipping_rate, $customer_name, $cust_email, $mobile, $productName, $productID, $totalAmount ) ;
                } else {
                    
                    $user_name = trim(sanitize_text_field( $_POST['cust_first_name']. ' ' .$_POST['cust_last_name'] ) );
                    $user_password = wp_generate_password(12);
                    $first_name = sanitize_text_field( $_POST['cust_first_name']);
                    $last_name = sanitize_text_field( $_POST['cust_last_name']);
                    $user_email = strtolower(sanitize_text_field( $_POST['cust_email']) );

                    /****************** Insert new user ****************** */
                    $user_id = swr_add_new_user( $user_name, $user_password, $first_name, $last_name, $user_email );
                    
                    /****************** Send Email to guest user ****************** */
                    $send_email = swr_send_email_for_guest_user($user_password, $user_email);
                    echo $send_email;
                    $cust_first_name = sanitize_text_field( $_POST['cust_first_name'] );
                    $cust_last_name = sanitize_text_field( $_POST['cust_last_name'] );
                    $cust_email = strtolower( sanitize_text_field( $_POST['cust_email'] ) );
                    $address_type = sanitize_text_field( $_POST['address_type'] );
                    $cust_pincode = sanitize_text_field( $_POST['cust_pincode'] );
                    $cust_address = sanitize_text_field( $_POST['cust_address'] );
                    $cust_landmark = sanitize_text_field( $_POST['cust_landmark'] );
                    $cust_city = sanitize_text_field( $_POST['cust_city'] );
                    $cust_state = sanitize_text_field( $_POST['cust_state'] );
                    $cust_country_code = sanitize_text_field( $_POST['country_code'] );
                    $mobile = sanitize_text_field( $_POST['mobile'] );

                    if ( empty( $cust_first_name ) || empty( $cust_last_name ) || empty( $cust_email ) || empty( $address_type ) || empty( $cust_pincode ) || empty( $cust_address ) || empty( $cust_city ) || empty( $cust_state ) || empty( $mobile ) ) {
                        
                        $errors[] = "Please fill up all required field for further process";
                    }

                    /****************** Insert guest shipping address detail in database ****************** */
                    $selected_id = swr_insert_address( $user_id, $cust_first_name, $cust_last_name, $cust_email, $address_type, $cust_pincode, $cust_address, $cust_landmark, $cust_city, $cust_state, $cust_country_code, $mobile );
                    
                    $customer_name = $cust_first_name .' '. $cust_last_name; 

                    $transcation_id = swr_insert_transaction($user_id, $selected_id, $random_order_id, $shipping_rate, $customer_name, $cust_email, $mobile, $productName, $productID, $totalAmount ) ;

                }
                
                if ( get_option( 'swr_custom_logo' ) ) {
                    
                    $logo = get_option( 'swr_custom_logo' );
                    
                } else {
                    
                    $logo = SWR_PLUGIN_PATH . 'assets/images/logo-pmc.png';
                }
		$razorpay_title = get_option( 'swr_custom_title' ) ? get_option( 'swr_custom_title' ) : 'Sell with Razorpay';

                $swr_razorpay_args = array(
                    'key' => key_id,
                    'name' => $razorpay_title,
                    'amount' => $totalAmount * 100,
                    'currency' => 'INR',
                    'description' => 'Order Payment',
                    'image' => $logo,
                    'theme' => array('color' => '#70723a', 'image_padding' => true),
                    'prefill' => array(
                        'email' => $cust_email,
                    ),
                    'notes' => array(
                        'cust_name' => $cust_first_name . ' ' . $cust_last_name,
                        'cust_email' => $cust_email,
                        'cust_mobile' => $mobile,
                        'user_id' => $user_id,
                        'selected_id' => $selected_id,
                        'product_name' => $productName,
                        'product_id' => $productID,
                        'shipping_fare' => $shipping_rate,
                        'order_id'  =>  $random_order_id,
                    )
                );
                $json = json_encode( $swr_razorpay_args );
                $html = <<<EOT
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var data = $json;
</script>
<form name='razorpayform' action="" method="POST">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
</form>
<script>
    data.backdropClose = false;
    data.handler = function(payment){
      document.getElementById('razorpay_payment_id').value = 
        payment.razorpay_payment_id;
      document.razorpayform.submit();
    };	
    var razorpayCheckout = new Razorpay(data);
    razorpayCheckout.open();
</script>
<p>
</p>
EOT;
                echo $html;
            }
        } else {
            
            $id = $_POST['razorpay_payment_id'];
            $payment = $api->payment->fetch( $id );
            $payment->capture(array('amount' => $payment->amount ) );
            $payment = $api->payment->fetch( $id );

            $result = ( $payment->toArray() );
            
            if ($result['status'] == "captured") {
                
                $userID = $result['notes']['user_id'];
                $shippingID = $result['notes']['selected_id'];
                $shipping_fare = $result['notes']['shipping_fare'];
                $customer_name = $result['notes']['cust_name'];
                $customer_email = $result['notes']['cust_email'];
                $customer_mobile = $result['notes']['cust_mobile'];
                $customer_payment_id = $result['id'];
                $customer_product_name = $result['notes']['product_name'];
                $customer_product_ID = $result['notes']['product_id'];
                $customer_total_amount = absint( $result['amount'] / 100 );
                $customer_currency = $result['currency'];
                $customer_payment_status = $result['status'];
                $customer_payment_type = $result['method'];
                $customer_description = $result['description'];
                $customer_created_at = $result['created_at'];
                $customer_random_order_ID = $result['notes']['order_id'];
                $customer_payment_mobile = $result['contact'];
                
                $insert_address = $wpdb->update(
                            $wpdb->prefix . 'swr_transaction', array(
                        'payment_id' => $customer_payment_id,
                        'mobile' => $customer_payment_mobile,
                        'payment_status' => $customer_payment_status,
                        'payment_type' => $customer_payment_type,
                        'description' => $customer_description,
                        'created_at' => date('Y-m-d H:i:s', $customer_created_at)
                            ), array(
                        'order_id' => $customer_random_order_ID
                            ), array(
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                            ), array(
                        '%s'
                            )
                    );
                
                if ( $insert_address > 0 ) {
                    
                    $stock_quantity = get_post_meta( $customer_product_ID, 'stock_quantity', true );
                    $count_stock_quantity = $stock_quantity - ( count( $insert_address ) );
                
                    update_post_meta( $customer_product_ID, 'stock_quantity', $count_stock_quantity );
                    
                    set_transient('order_paymentId', $result['id']);
                    $order_confirm_email = swr_order_confirmation_email( $customer_email );
                    wp_redirect( home_url( 'thank-you' ) );
                    exit;
                    
                } else {
                    $errors[] = "Your submission has been failed, Please try again later";
                    echo '<div class="addtocartBox">';
                    echo '<a href=" ' . home_url( '/checkout' ) . ' " class="addtocart"> << Back To Checkout</a>';
                    echo '</div>';
                    
                }
                
            } else {
                $errors[] = "Your payment has been failed, Please try again later";
                echo '<div class="addtocartBox">';
                echo '<a href=" ' . home_url() . ' " class="addtocart"> << Back</a>';
                echo '</div>';
            }
        }
    } else {
        
        $errors[] = "Please select any one product for further process";
    }
    if (count( $errors ) > 0) {
        
        foreach ( $errors as $error ) {
            echo $error;
        }
    }
}
add_shortcode( 'razorpay-payment-form', 'swr_payment_process_callback' );


/*
 * Checkout Page Function
 */

function swr_checkout_page_callback() {
    
    global $wpdb, $current_user;

    if ( isset( $_POST['pay_submit'] ) ) {
        $_SESSION['h_productID'] = $_POST['h_productID'];
    }
    if ( !empty( $_SESSION['h_productID'] ) ) {
        $productID = $_SESSION['h_productID'];
    }
    ?>
    <div class="mainPluginBox shipping_address">
        <fieldset>
            <fieldset>
                <legend>Order Details</legend>
    <?php if ( !empty( $productID ) ) { ?>
                    <table class="tg fulltable" style="font-size: 12px;">
                        <th class="tg-yw4l">Product Name</th>
                        <th class="tg-yw4l">Product Price</th>
                        <tr>
                            <?php
                            $productName = get_post( $productID );
                            $productPrice = get_post_meta( $productID, 'sell_price', true );
				$shipping_charge = '';
                            ?>
                            <td class="tg-yw4l pincodeColumn"><?php echo $productName->post_title; ?></td>																		
                            <td class="tg-yw4l"><?php
                                if ( absint( $productPrice ) ) {
                                    echo trim( RS_SYMBOL . ' ' . $productPrice );
                                } else {
                                    echo '-';
                                }
                                ?></td>
                        </tr>
                        <?php
                        if ( get_option( 'swr_shipping_amount_checked', true ) == 1 ) {
                            
                            echo '<tr>';
                            echo '<td class="tg-yw4l pincodeColumn" colspan="1">Shipping Charge</td>';
                            echo '<td class="tg-yw4l">Free Shipping</td>';
                            echo '</tr>';
                            
                        } else {
                            
                            $shipping_charge = absint(get_option('swr_shipping_amount',true));
                            echo '<tr>';
                            echo '<td class="tg-yw4l pincodeColumn" colspan="1">Shipping Charge</td>';
                            echo '<td class="tg-yw4l">' . RS_SYMBOL . ' ' . ( absint( $shipping_charge ) ) . '</td>';
                            echo '</tr>'; 
                            
                        }
                        $totalAmount = absint( $productPrice ) + absint( $shipping_charge );
                        ?>
                        <tr>
                            <td class="tg-yw4l" colspan="1"><b>Total</b></td>
                            <td class="tg-yw4l"><b><?php echo ( RS_SYMBOL . ' ' . absint( $totalAmount ) ); ?></b></td>
                        </tr>
                    </table> 
                    <?php
                } else {
                    echo '<p>No Product Found</p>';
                }
                ?>
            </fieldset>
                <?php if (is_user_logged_in()) { ?>
                <form method="post" id="submit_payment" name="submit_payment" class="cmxform" action="<?php echo home_url( "/payment-process" ); ?>">
                    <?php
                    wp_nonce_field('submit-order-action', 'submit_order_field');
                    $user_id = $current_user->ID;
                    $shipping_tbl_name = $wpdb->prefix . 'swr_shipping_address';

                    $countAddress = $wpdb->get_var( "SELECT count(*) FROM $shipping_tbl_name WHERE user_id = $user_id" );
                    
                    if ( $countAddress > 0 ) {
                        swr_razorpay_show_address();
                    } else {
                        ?>
                        <fieldset>
                            <legend>Delivery Address</legend>
                            <div class="registration_form">
                                <div class="newGusetRegisterDiv">
                                    <div class="modal-box">
                                        <div class="modal-body">
                                            <div class="fullradiodiv">
                                                <div class="radio_button_div">
                                                    <p class="first">
                                                        <label for="address_choice">
                                                            <label>Home Address</label>
                                                            <input type="radio" id="home_address" class="address_ch" value="home" name="address_type" checked>
                                                        </label>
                                                    </p>
                                                    <p class="last">
                                                        <label for="address_choice">
                                                            <label>Office Address</label>
                                                            <input type="radio" id="office_address" class="address_ch" value="office" name="address_type">
                                                        </label>
                                                    </p>
                                                </div>
                                            </div>

                                            <p class="first">
                                                <input id="cust_first_name" name="cust_first_name" type="text" value="" placeholder="First Name*">
                                            </p>

                                            <p class="last">
                                                <input id="cust_last_name" name="cust_last_name" type="text" value="" placeholder="Last Name*">
                                            </p>

                                            <p class="first">
                                                <input id="cust_email" name="cust_email" type="text" value="<?php echo $current_user->user_email; ?>" placeholder="Email*" readonly>

                                            </p>

                                            <p class="last">
                                                <input id="cust_pincode" name="cust_pincode" type="text" value="" maxlength="20" placeholder="Pincode*">
                                            </p>

                                            <p class="fullBox">
                                                <textarea rows="2" cols="50" name="cust_address" id="cust_address" placeholder="Address*"></textarea>
                                            </p>

                                            <p class="first">
                                                <input id="cust_landmark" name="cust_landmark" type="text" value="" placeholder="Optional" maxlength="50" placeholder="Landmark(Optional)">
                                            </p>

                                            <p class="last">
                                                <input id="cust_city" name="cust_city" type="text" value="" maxlength="20" placeholder="City*">
                                            </p>

                                            <p class="first">
                                                <input id="cust_state" name="cust_state" type="text" value="" maxlength="20" placeholder="State*">
                                            </p>

                                            <p class="last">
                                                <?php swr_country_name_code( 'IN' ); ?>
                                            </p>

                                            <p class="fullBox">
                                            <div class="formrow">
                                                <div class="mobile_field_div">
                                                    <input id="mobile" name="mobile" type="text" maxlength="10" value="" placeholder="Mobile No.*">
                                                </div>
                                            </div>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
        <?php } ?>
                        <input type="hidden" name="result_count" id="result_count" value="<?php echo $countAddress; ?>">
                        <input type="submit" name="submit" id="submit" value="Submit">
                    </fieldset>
                </form>
                <?php
                swr_address_popup_form();
            }
            if ( !is_user_logged_in() ) {
                
                $page_redirect = home_url( '/checkout' );
                $login_form = swr_login_form( $page_redirect );
                ?>
                <div class="guest_form">
                    <form method="post" id="submit_guest" name="submit_guest" class="cmxform" action="<?php echo home_url( "/payment-process" ); ?>">
        <?php wp_nonce_field( 'guest-submit-form', 'guest_submit_field' ); ?>

                        <div class="newGusetRegisterDiv">
                            <div class="modal-box">
                                <div class="modal-body">
                                    <fieldset>
                                        <legend>Sign Up</legend>

                                        <div class="registration_form">

                                            <div class="fullradiodiv">
                                                <div class="radio_button_div">
                                                    <p class="first">
                                                        <label for="address_choice">
                                                            <label>Home Address</label>
                                                            <input type="radio" id="home_address" class="address_ch" value="home" name="address_type" checked>
                                                        </label>
                                                    </p>
                                                    <p class="last">
                                                        <label for="address_choice">
                                                            <label>Office Address</label>
                                                            <input type="radio" id="office_address" class="address_ch" value="office" name="address_type">
                                                        </label>
                                                    </p>
                                                </div>
                                            </div>

                                            <p class="first">
                                                <input id="cust_first_name" name="cust_first_name" type="text" value="" placeholder="First Name*">
                                            </p>

                                            <p class="last">
                                                <input id="cust_last_name" name="cust_last_name" type="text" value="" placeholder="Last Name*">
                                            </p>

                                            <p class="first">
                                                <input id="cust_email" name="cust_email" type="text" value="" placeholder="Email*">
                                            </p>

                                            <p class="last">
                                                <input id="cust_pincode" name="cust_pincode" type="text" value="" maxlength="20" placeholder="Pincode*">
                                            </p>

                                            <p class="fullBox">
                                                <textarea rows="2" cols="50" name="cust_address" id="cust_address" placeholder="Address*"></textarea>
                                            </p>

                                            <p class="first">
                                                <input id="cust_landmark" name="cust_landmark" type="text" value="" placeholder="Optional" maxlength="50" placeholder="Landmark(Optional)">
                                            </p>

                                            <p class="last">
                                                <input id="cust_city" name="cust_city" type="text" value="" maxlength="20" placeholder="City*">
                                            </p>

                                            <p class="first">
                                                <input id="cust_state" name="cust_state" type="text" value="" maxlength="20" placeholder="State*">
                                            </p>

                                            <p class="last">
                                                <?php swr_country_name_code( 'IN' ); ?>
                                            </p>

                                            <p class="fullBox">
                                            <div class="formrow">
                                                <div class="mobile_field_div">
                                                    <input id="mobile" name="mobile" type="text" maxlength="10" value="" placeholder="Mobile No.*">
                                                </div>
                                            </div>
                                            </p>
                                        </div>
                                        <input type="submit" name="guest_submit" id="guest_submit" value="Submit">
                                    </fieldset>

                                </div>
                            </div>
                        </div>
                    </form>
                </div>
    <?php } ?>
        </fieldset>
    </div>
    <?php
}
add_shortcode( 'razorpay-checkout-page', 'swr_checkout_page_callback' );

function swr_start_session() {
    
    if ( !session_id() ) {
        session_set_cookie_params( 18000 );
        session_start();
    }
}
add_action( 'init', 'swr_start_session', 1 );


/*
 * Session destroy
 */

function swr_end_session() {
    
    session_destroy();
}
add_action( 'wp_logout', 'swr_end_session' );


/*
 * Add new user in admin side
 */

function swr_add_new_user( $user_name, $user_password, $first_name, $last_name, $user_email ) {
    
    global $wpdb, $current_user;
    
    if ( !empty( $user_name ) && !empty( $user_password ) && !empty( $user_email ) ) {
        
        $user_id = wp_insert_user(
                array(
                    'user_login' => $user_name,
                    'user_pass' => $user_password,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'user_email' => $user_email,
                    'display_name' => $first_name . ' ' . $last_name,
                    'nickname' => $first_name . ' ' . $last_name
                )
        );
        return $user_id;
    } else {
        return false;
    }
}

/*
 * Fetch shipping address from database
 */

function swr_razorpay_show_address() {
    
    global $wpdb, $current_user;
    $user_id = $current_user->ID;
    $shipping_tbl_name = $wpdb->prefix . 'swr_shipping_address';

    $display_existing_address = $wpdb->get_results( "SELECT * FROM $shipping_tbl_name WHERE user_id = $user_id" );
        
    if ( $display_existing_address ) {
        
        $count_address = count( $display_existing_address );
        ?>
        <fieldset>
            <legend>Shipping Address</legend>
            <div class="product-list-main grid-container">
                <ul class="product-grid-list rig columns-4">
                    <?php
                    $class_count = 0;
                    
                    foreach ( $display_existing_address as $value ) {
                        
                        $class_count++;
                        $active_class = ( ( $class_count == $count_address ) ? $class_count = 'checked' : '' );
                        ?>
                        <li class="single-product-item exitsting_add existing_add <?php echo $active_class; ?>" id="<?php echo "address_" . $value->id; ?>">
                            <div class="title">
                                <strong><p class="bl1"><?php echo $value->first_name . ' ' . $value->last_name; ?>  <span class="actionBox"><a href="#" id="<?php echo $value->id; ?>" class="delete"><i class="fa fa-trash" aria-hidden="true"></i></a></span></p></strong>
                            </div>
                            <p class="address"><i class="fa fa-map-marker" aria-hidden="true"></i>
                                <?php echo $value->address . ' ' . $value->landmark . '<br/>' . $value->pincode . ', ' . $value->city . '<br/>' . $value->state; ?></p>
                            <p class="mobile"><i class="fa fa-phone" aria-hidden="true"></i>
            <?php echo $value->mobile; ?></p>
                            <input type="radio" class="hidden_radio" id="<?php echo "radio_" . $value->id; ?>" name="address_choice" value="<?php echo $value->id; ?>" checked>
                            <input type="hidden" name="total_address" id="total_address" value="<?php echo count( $display_existing_address ); ?>">
                        </li>
        <?php } ?> 
                </ul>
            </div>
        </fieldset>
        <section class="v-center">
            <div><a class="js-open-modal btn" href="#" data-modal-id="popup1"> + ADD NEW ADDRESS</a></div>
        </section>
        <?php
    }
}

/*
 * Shipping address form open in Pop-Up box
 */

function swr_address_popup_form() {
    
    global $current_user;
    ?>
    <div id="popup1" class="modal-box">
        <header> <a href="#" class="js-modal-close close">×</a>
            <h3>Enter a new shipping address</h3>
        </header>
        <div class="modal-body">

            <form method="POST" id="add_new_add_popup" name="add_new_add_popup" class="cmxform Address_validation">
                <fieldset>
                    <div class="fullradiodiv">
                        <div class="radio_button_div">
                            <p class="first">
                                <label for="address_choice">
                                    <label>Home Address</label>
                                    <input type="radio" id="sa_home_address" class="address_ch" value="home" name="sa_address_type" checked>
                                </label>
                            </p>
                            <p class="last">
                                <label for="address_choice">
                                    <label>Office Address</label>
                                    <input type="radio" id="sa_office_address" class="address_ch" value="office" name="sa_address_type">
                                </label>
                            </p>
                        </div>
                    </div>

                    <p class="first">
                        <input id="sa_cust_first_name" name="sa_cust_first_name" type="text" value=""   placeholder="First Name*">
                    </p>

                    <p class="last">
                        <input id="sa_cust_last_name" name="sa_cust_last_name" type="text" value="" placeholder="Last Name*">
                    </p>

                    <p class="first">
                        <input id="sa_cust_email" name="sa_cust_email" type="text" value="<?php echo $current_user->user_email; ?>" placeholder="Email*" disabled>
                    </p>

                    <p class="last">
                        <input id="sa_cust_pincode" name="sa_cust_pincode" type="text" value="" maxlength="20" placeholder="Pincode*">
                    </p>

                    <p class="fullBox">
                        <textarea rows="1" cols="50" name="sa_cust_address" id="sa_cust_address" placeholder="Address*"></textarea>
                    </p>

                    <p class="first">
                        <input id="sa_cust_landmark" name="sa_cust_landmark" type="text" value="" placeholder="Landmark(Optional)" maxlength="50">
                    </p>

                    <p class="last">
                        <input id="sa_cust_city" name="sa_cust_city" type="text" value="" maxlength="20" placeholder="City*">
                    </p>

                    <p class="first">
                        <input id="sa_cust_state" name="sa_cust_state" type="text" value="" maxlength="20" placeholder="State*">
                    </p>

                    <p class="last">
                        <?php swr_country_name_code( 'IN' ); ?>
                    </p>

                    <p class="fullBox">
                    <div class="formrow">
                        <div class="mobile_field_div">
                            <input id="sa_mobile" name="sa_mobile" type="text" maxlength="10" value="" placeholder="Mobile No.*">
                        </div>
                    </div>
                    </p>

                    <input type="hidden" name="sa_current_uid" id="sa_current_uid" value="<?php echo $current_user->ID; ?>">
                    <input type="submit" class="btn btn-small" name="sa_submit_address" id="sa_submit_address" value="Save & Continue">
                </fieldset>
            </form>

        </div>
    </div>
    <?php
}

/*
 * Insert pop-up form shipping address in database
 */

function swr_insert_customer_shipping_address() {
    
    global $wpdb;
    
    $userid = sanitize_text_field( $_POST['cust_user_id'] );
    $address_type = sanitize_text_field( $_POST['cust_address_type'] );
    $first_name = sanitize_text_field( $_POST['cust_first_name'] );
    $last_name = sanitize_text_field( $_POST['cust_last_name'] );
    $email = sanitize_text_field( $_POST['cust_email'] );
    $pincode = sanitize_text_field( $_POST['cust_pincode'] );
    $address = sanitize_text_field( $_POST['cust_address'] );
    $landmark = sanitize_text_field( $_POST['cust_landmark'] );
    $city = sanitize_text_field( $_POST['cust_city'] );
    $state = sanitize_text_field( $_POST['cust_state'] );
    $country = sanitize_text_field( $_POST['cust_country'] );
    $mobile = sanitize_text_field( $_POST['cust_mobile'] );

    $insert_address = $wpdb->insert(
            $wpdb->prefix . 'swr_shipping_address', array(
        "user_id" => $userid,
        "first_name" => $first_name,
        "last_name" => $last_name,
        "email" => $email,
        "pincode" => $pincode,
        "address_type" => $address_type,
        "address" => $address,
        "landmark" => $landmark,
        "city" => $city,
        "state" => $state,
        "country" => $country,
        "mobile" => $mobile
            ), array(
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
            )
    );

    if ( $insert_address ) {
        
        echo $wpdb->insert_id;
        
    } else {
        
        echo false;
    }
    die();
}
add_action( 'wp_ajax_swr_insert_customer_shipping_address', 'swr_insert_customer_shipping_address' );
add_action( 'wp_ajax_nopriv_swr_insert_customer_shipping_address', 'swr_insert_customer_shipping_address' );


/*
 * Ajax request for delete shipping address
 */

function swr_delete_ajax_request_response() {
    
    global $wpdb;
    
    $db_table_name = $wpdb->prefix . 'swr_shipping_address';
    
    if ( isset( $_POST['add_id'] ) ) {
        
        $addID = sanitize_text_field( $_POST['add_id'] );
        $delete_query = "DELETE FROM $db_table_name WHERE id = '" . $addID . "' ";
        echo $delete_image = $wpdb->query( $delete_query );
    }
    die();
}
add_action( "wp_ajax_swr_delete_ajax_request_response", "swr_delete_ajax_request_response" );
add_action( "wp_ajax_nopriv_swr_delete_ajax_request_response", "swr_delete_ajax_request_response" );


/*
 * Add custom content in thank you page
 */

function swr_thankyou_page_template_redirect() {
    
    global $current_user;
    
    $user_id = $current_user->ID;
    
    if ( is_page( 'thank-you' ) && !empty( get_transient( 'order_paymentId' ) ) ) {
        
        add_filter( 'the_content', 'swr_thankyou_page_content' );
        
    }
}
add_action('template_redirect', 'swr_thankyou_page_template_redirect');

function swr_thankyou_page_content() {
    
    global $current_user;
    
    $user_id = $current_user->ID;
    $get_trans = get_transient( 'order_paymentId' );
    $success = '<div class="thankyouBox"><h4>Your payment has been received</h4><p>A confirmation email has been sent to your email id.</p>';
    $success .= '<p>Your payment reference no: ' . $get_trans . ' </p></div>';
    session_destroy();
    return $success;
}


/*
 * Insert customer address in database
 */
function swr_insert_address( $user_id, $cust_first_name, $cust_last_name, $cust_email, $address_type, $cust_pincode, $cust_address, $cust_landmark, $cust_city, $cust_state, $cust_country_code, $mobile ) {
    
    global $wpdb;

    $selected_id = $wpdb->insert(
            $wpdb->prefix . 'swr_shipping_address', array(
        "user_id" => $user_id,
        "first_name" => $cust_first_name,
        "last_name" => $cust_last_name,
        "email" => $cust_email,
        "address_type" => $address_type,
        "pincode" => $cust_pincode,
        "address" => $cust_address,
        "landmark" => $cust_landmark,
        "city" => $cust_city,
        "state" => $cust_state,
        "country" => $cust_country_code,
        "mobile" => $mobile ), array(
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
            )
    );
    if ( $selected_id > 0 ) {
        
        return $wpdb->insert_id;
    
    } else {
        
        return false;
        
    }
}


/*
 * Insert data taransaction table in database
 */
//swr_insert_transaction($user_id, $selected_id, $shipping_rate, $customer_name, $cust_email, $mobile, $productName, $productID, $totalAmount ) ;
function swr_insert_transaction($userID, $shippingID, $random_order_id, $shipping_fare, $customer_name, $customer_email, $customer_mobile, $customer_product_name, $customer_product_ID, $customer_total_amount ) {

    global $wpdb;

    $selected_id = $wpdb->insert(
            $wpdb->prefix . 'swr_transaction', array(
        'user_id' => $userID,
        'shipping_id' => $shippingID,
        'order_id' => $random_order_id,
        'shipping_fare' => $shipping_fare,
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'mobile' => '',
        'customer_mobile' => $customer_mobile,
        'payment_id' => '',
        'product_name' => $customer_product_name,
        'product_id' => $customer_product_ID,
        'total_amount' => $customer_total_amount,
        'currency' => 'INR',
        'payment_status' => 'pending',
        'payment_type' => '',
        'description' => '',
        'shipping' => 'pending',
        'created_at' =>  date('Y-m-d H:i:s')
            ), array(
        '%d',
        '%d',
        '%s',        
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',        
        '%s',
        '%s',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
            )
    );
    
    if ($selected_id > 0) {

        return $wpdb->insert_id;
    } else {

        return false;
    }
}


/*
 * Ckeck email adress exist or not
 */

function swr_check_email_adress() {
    
    if ( isset( $_POST['cust_email'] ) ) {

        $email = sanitize_text_field( $_POST['cust_email'] );
        $exists = email_exists( $email );

        if ( $exists ) {
            echo "false";
        } else {
            echo "true";
        }
    }
    die();
}
add_action( "wp_ajax_swr_check_email_adress", "swr_check_email_adress" );
add_action( "wp_ajax_nopriv_swr_check_email_adress", "swr_check_email_adress" );


/*
 * Send email for new guest checkout
 */

function swr_send_email_for_guest_user($user_password, $user_email) {
    
    $set_from = ( esc_attr( get_option( 'swr_from_email' ) ) ? esc_attr( get_option( 'swr_from_email' ) ) : get_option( 'admin_email' ) );
    $set_cc = ( esc_attr( get_option( 'swr_email_cc' ) ) ? esc_attr( get_option( 'swr_email_cc' ) ) : get_option( 'admin_email' ) );
    $to = $user_email;
    $subject = get_option( 'swr_email_subject' );
    $msg_body = get_option( 'swr_email_messagebody' );
    $rep_keyword = array( "[email]", "[password]" );
    $rep_value = array( $user_email, $user_password );
    $body = str_replace( $rep_keyword, $rep_value, $msg_body );
    $site_url = get_bloginfo();
    $content = '<!DOCTYPE HTML>
<head>
<meta http-equiv="content-type" content="text/html">
<title>Email notification</title>
</head>
<body>
<div style="width:600px;margin:0 auto;background:#ededed;border:#bbb solid 3px;">
<div style="width: 600px;height: 90px;margin: 0 auto;padding: 10px 0;border-bottom:#dddddd solid 1px; color: #fff;text-align: center;background-color: #ffffff;font-family: Open Sans,Arial,sans-serif;box-sizing:border-box;">
   <p height="200" width="180" style="border-width:0;color:#484848; font-size: 240%;">' . $subject . '</p>
</div>
<div id="outer" style="width: 580px;margin: 0 auto;margin-top: 10px;padding:10px;box-sizing:border-box;">
   <div id="inner" style="width: 580px;margin: 0 auto;font-family: Open Sans,Arial,sans-serif;font-size: 13px;font-weight: normal;line-height: 1.4em;color: #444;margin-top: 10px;">
    <span> ' . $body . '</span>
   </div>
</div>
<div id="footer" style="width:600px;height: 20px;margin: 0 auto;text-align: center;padding: 10px 0;font-family: Open Sans,Arial,sans-serif;background-color: #bbbbbb;box-sizing:border-box;color:#484848;">
&copy; ' . date('Y') . ' ' . $site_url . '. All Rights Reserved.
</div>
</div>
</body>';
    $header = "From: $set_from \r\n";
    $header .= "Cc: $set_cc \r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-type: text/html\r\n";
    
    $sent_register_email = wp_mail( $to, $subject, $content, $header );
}


/*
 * Send order confirmation email after succesfully order
 */

function swr_order_confirmation_email( $customer_email ) {
    
    global $wpdb, $current_user;
    
    $body = '';
    $swr_transaction_tbl = $wpdb->prefix . 'swr_transaction';
    $swr_shipping_address = $wpdb->prefix . 'swr_shipping_address';
    $set_from = ( esc_attr( get_option( 'swr_from_email' ) ) ? esc_attr( get_option( 'swr_from_email' ) ) : get_option( 'admin_email' ) );
    $set_cc = ( esc_attr( get_option( 'swr_email_cc' ) ) ? esc_attr( get_option( 'swr_email_cc' ) ) : get_option( 'admin_email' ) );
    $to = $customer_email;
    $payment_ID = get_transient( 'order_paymentId' );
    $subject = 'Your payment has been received!';
    $order_info = $wpdb->get_row("SELECT pt.customer_email, pt.customer_mobile, pt.customer_name, pt.total_amount, pt.shipping_fare, pt.trans_id, pt.product_name, pt.shipping_id, pt.created_at, ps.pincode, ps.address, ps.landmark, ps.city, ps.state, ps.country, ps.id FROM $swr_transaction_tbl as pt, $swr_shipping_address as ps  WHERE payment_id = '" . $payment_ID . "' AND shipping_id = id ");
    $sub_total = ( absint( $order_info->total_amount ) ) - (absint( $order_info->shipping_fare ) );
    $newDate = date("jS F Y \n , l g:ia", strtotime($order_info->created_at));
    $product_qty = '1';
    $site_url = get_bloginfo();
    $body = '<!DOCTYPE HTML>
<head>
<meta http-equiv="content-type" content="text/html">
<title>Email notification</title>
</head>
<body>
<div style="width:600px;margin:0 auto;background:#ededed;border:#bbb solid 3px;">
<div style="width: 600px;height: 90px;margin: 0 auto;padding: 10px 0;border-bottom:#dddddd solid 1px; color: #fff;text-align: center;background-color: #ffffff;font-family: Open Sans,Arial,sans-serif;box-sizing:border-box;">
   <p height="200" width="180" style="border-width:0;color:#484848; font-size: 240%;">' . $subject . '</p>
</div>
<div id="outer" style="width: 580px;margin: 0 auto;margin-top: 10px;padding:10px;box-sizing:border-box;">
   <div id="inner" style="width: 580px;margin: 0 auto;font-family: Open Sans,Arial,sans-serif;font-size: 13px;font-weight: normal;line-height: 1.4em;color: #444;margin-top: 10px;">
    <span> Dear ' . $order_info->customer_name . ',</span>
        <p> ORDER : ' . $order_info->trans_id . ' ( ' . $newDate . ') </p>
     <h2>ORDER INFORMATION</h2>
       <div style="line-height:22px"><strong>Product: </strong>' . $order_info->product_name . '</div>
       <div style="line-height:22px"><strong>Quantity: </strong>' . $product_qty . '</div>
       <div style="line-height:22px"><strong>Price: </strong>' . ( RS_SYMBOL . ' ' . $sub_total ) . '</div>
       <div style="line-height:22px"><strong>Shipping Charge: </strong>' . ( RS_SYMBOL . ' ' . ( absint( $order_info->shipping_fare) ) ) . '</div>
       <div style="line-height:22px"><strong>Total Price: </strong>' . ( RS_SYMBOL . ' ' . ( absint( $order_info->total_amount) ) ) . '</div>
       <h2>CUSTOMER DETAILS</h2>
       <div style="line-height:22px"><strong>Email: </strong>' . $order_info->customer_email . '</div>
       <div style="line-height:22px"><strong>Mobile: </strong>' . $order_info->customer_mobile . '</div>
       <h2>SHIPPING ADDRESS</h2>
       <div style="line-height:22px"><p class="address"><i class="fa fa-map-marker" aria-hidden="true"></i>' . $order_info->address . ',' . $order_info->landmark . ',' . $order_info->pincode . ',' . $order_info->city . ',' . $order_info->state . '</p></div>
   </div>
</div>
<div id="footer" style="width:600px;height: 20px;margin: 0 auto;text-align: center;padding: 10px 0;font-family: Open Sans,Arial,sans-serif;background-color: #bbbbbb;box-sizing:border-box;color:#484848;">
&copy; ' . date('Y') . ' ' . $site_url . '. All Rights Reserved.
</div>
</div>
</body>';
    $header = "From: $set_from \r\n";
    $header .= "Cc: $set_cc \r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-type: text/html\r\n";

    $sent_order_email = wp_mail( $to, $subject, $body, $header );
}

/*
 * User login by email address
 */

function swr_email_login_authenticate( $user, $username, $password ) {
    
    if ( is_a( $user, 'WP_User' ) )
        return $user;

    if ( !empty( $username ) ) {
        
        $username = str_replace( '&', '&amp;', stripslashes( $username ) );
        $user = get_user_by( 'email', $username );
        
        if ( isset( $user, $user->user_login, $user->user_status ) && 0 == ( int ) $user->user_status )
            $username = $user->user_login;
    }

    return wp_authenticate_username_password( null, $username, $password );
}
remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
add_filter( 'authenticate', 'swr_email_login_authenticate', 20, 3 );


/*
 * Change shipping order status
 */

function swr_order_action_status() {
    
    global $wpdb;
    
    $shipping_status = $_POST['shipping_status'];
    $payment_id = $_POST['payment_id'];

    $table_trans = $wpdb->prefix . 'swr_transaction';
    
    if ( $payment_id ) {
        
        $result = $wpdb->update( $table_trans, array (
            'shipping' => $shipping_status
                ), array (
            'payment_id' => $payment_id
                ), array (
            '%s'
                ), array (
            '%s'
                )
        );
    }
    echo $result;
    die();
}
add_action( "wp_ajax_swr_order_action_status", "swr_order_action_status" );
add_action( "wp_ajax_nopriv_swr_order_action_status", "swr_order_action_status" );

/* 
 * Session start
 */

function swr_app_output_buffer() {
    
    ob_start();
}
add_action( 'init', 'swr_app_output_buffer' );

/* 
 * User Logout Redirect
 */

function swr_go_home() {
    
    global $current_user;
    
    if ( user_can( $current_user, "subscriber" ) ) {
        wp_redirect( home_url() );
        exit();
    }
}
add_action( 'wp_logout', 'swr_go_home' );


/*
 *  Error message for invalid user login
 */

function swr_front_end_login_fail( $username ) {
    
    $referrer = $_SERVER[ 'HTTP_REFERER' ];  // where did the post submission come from?
    // if there's a valid referrer, and it's not the default log-in screen
    if ( !empty( $referrer ) && !strstr( $referrer, 'wp-login' ) && !strstr( $referrer, 'wp-admin' ) ) {

        $pos = strpos( $referrer, '?login=failed' );

        if ( $pos === false ) {
            // add the failed
            wp_redirect( home_url( 'checkout' ) . '?login=failed' );  // let's append some information (login=failed) to the URL for the theme to use
        }
        exit;
    }
}
add_action( 'wp_login_failed', 'swr_front_end_login_fail' );


/*
 * Customer Profile Page
 */

function swr_edit_customer_profile() {
    
   global $wpdb, $current_user;
   
    if ( is_user_logged_in() ) {
        
        $error = array();
        if ( 'POST' == $_SERVER[ 'REQUEST_METHOD' ] && !empty( $_POST['action'] ) && $_POST['action'] == 'update-user' ) {

            if ( !empty( $_POST['profile_email'] ) ) {
                if ( !is_email( esc_attr( $_POST['profile_email'] ) ) )
                    $error[] = __('The Email you entered is not valid.  please try again.');
                elseif ( email_exists( esc_attr( $_POST['profile_email'] ) ) != $current_user->id )
                    $error[] = __('This email is already used by another user.  try a different one.');
                else {
                    wp_update_user( array( 'ID' => $current_user->ID, 'user_email' => esc_attr( $_POST['profile_email'] ) ) );
                }
            }
            if ( !empty( $_POST['new_password'] ) ) {
                wp_update_user( array( 'ID' => $current_user->ID, 'user_pass' => esc_attr( $_POST['new_password'] ) ) );
            }

            if ( !empty( $_POST['profile_first_name'] ) )
                update_user_meta( $current_user->ID, 'first_name', esc_attr( $_POST['profile_first_name'] ) );
            if ( !empty( $_POST['profile_last_name'] ) )
                update_user_meta( $current_user->ID, 'last_name', esc_attr( $_POST['profile_last_name'] ) );

            if ( count( $error ) == 0 ) {
                $error[] = "Your profile has been updated";
            }
        }
        ?>
        <a href="<?php echo wp_logout_url( home_url() ); ?>" class="logoutbtn">Logout</a>
        <fieldset>
            <form method="post" id="personal_information" action="<?php the_permalink(); ?>">
                <div class="newGusetRegisterDiv">
                    <div class="modal-box">
                        <div class="modal-body">
                            <fieldset>
                                <legend>Personal Information</legend>
                                <div class="edit_profile">
        <?php if ( count( $error ) > 0 ) echo '<p class="error">' . implode( "<br />", $error ) . '</p>'; ?>
                                    <p class="first">
                                        <input id="profile_first_name" name="profile_first_name" type="text" value="<?php echo $current_user->first_name; ?>" placeholder="First Name*"/>
                                    </p>

                                    <p class="last">
                                        <input id="profile_last_name" name="profile_last_name" type="text" value="<?php echo $current_user->last_name; ?>" placeholder="Last Name*" />
                                    </p>

                                    <p class="fullBox">
                                        <input id="profile_email" name="profile_email" type="text" value="<?php echo $current_user->user_email; ?>" placeholder="Email*" disabled />
                                    </p>
                                </div>
                            </fieldset>
                            <fieldset>
                                <legend>Password Change</legend>
                                <div class="edit_profile">
                                    <p class="fullBox">
                                        <input id="old_password" name="old_password" type="password" value="" placeholder="Old Password*"/>
                                    </p>

                                    <p class="first">
                                        <input id="new_password" name="new_password" type="password" value="" placeholder="New Password*" />
                                    </p>

                                    <p class="last">
                                        <input id="confirm_new_password" name="confirm_new_password" type="password" value="" placeholder="Confirm New Password*" />
                                    </p>
                                </div>
                            </fieldset>
                            <input type="submit" name="update_profile" id="update_profile" value="Save Changes">
        <?php wp_nonce_field( 'update-user_' . $current_user->ID ) ?>
                            <input name="action" type="hidden" id="action" value="update-user" />
                        </div>
                    </div>
                </div>
            </form>
        </fieldset>
        <?php
    }
    else {
        $page_redirect = home_url( '/my-account' );
        $login_form = swr_login_form( $page_redirect );
    }
}
add_shortcode( 'edit-customer-profile', 'swr_edit_customer_profile' );


/*
 * Customer Order History Page
 */

function swr_order_list_page_callback() {
    
    global $wpdb, $current_user;
    date_default_timezone_set('Asia/Calcutta');
    
    $trans_table = $wpdb->prefix . 'swr_transaction';
    
    if ( is_user_logged_in() ) {
        
        $current_userId = $current_user->ID;
        $order_list = $wpdb->get_results( "SELECT trans_id, user_id, created_at, shipping, product_id, product_name, total_amount FROM $trans_table WHERE user_id = $current_userId ORDER BY trans_id DESC" );
        ?>
        <fieldset>
            <legend>My Order Details</legend>
                    <?php if ( count( $order_list ) > 0 ) { ?>
                <div class="product-list-main grid-container">
                    <ul class="product-grid-list rig columns-4 full-width-prduct-item">
                        <?php
                        foreach ( $order_list as $value ) {
                            $product_img = get_post_meta( $value->product_id, 'product_image', true );
                            $productName = get_post( $value->product_id )->post_title;
                            $order_date = date( "jS F Y \n , l g:ia", strtotime( $value->created_at ) );
                            ?>

                            <li class="single-product-item full-width-prduct-item" id="">
                                <div class="cust_order_img"><a href="<?php the_permalink( $value->product_id ); ?>" class="bl1"><img src="<?php echo $product_img; ?>" height="100" width="100" alt=""/></a></div>
                                <div class="cust_order_info"><strong><p class="bl1">Product Name : <a href="<?php the_permalink( $value->product_id ); ?>"><?php echo $productName; ?></a></p></strong>
                                    <p>Order ID : <?php echo $value->trans_id; ?></p>
                                    <p>Total Amount : <?php echo ( RS_SYMBOL . ' ' . $value->total_amount ); ?></p>
                                    <p>Order Status : <?php echo $value->shipping; ?></p>
                                    <p>Order Date : <?php echo $order_date; ?></p></div>
                            </li>
                <?php } ?> 
                    </ul>
                </div>
                <?php
            } else {
                _e( "Your Purchase History Not Found" );
            }
            ?>
        </fieldset>
        <?php
    } else {
        $page_redirect = home_url( '/my-order' );
        $login_form = swr_login_form( $page_redirect );
    }
}
add_shortcode( 'my-order-list-page', 'swr_order_list_page_callback' );


/*
 * User Login Form
 */

function swr_login_form( $page_redirect ) {
    
    $login_args = array(
        'echo' => true,
        'remember' => true,
        'redirect' => $page_redirect,
        'form_id' => 'loginform',
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'label_username' => __( 'Email' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in' => __( 'Log In' ),
        'value_username' => NULL,
        'value_remember' => false
    );
    echo '<fieldset>';
    echo '<legend>Sign In</legend>';
    echo '<div class="login_form">';
    wp_login_form( $login_args );
    echo '</div>';
    echo '</fieldset>';
}


/*
 * Country Name List
 */

function swr_country_name_code( $selected = '' ) {
    ?>
        
    <select name="country_code" id="country_code">
        <option value="">Choose Country</option>
        <option <?php if ( $selected == 'AF' ) echo 'selected'; ?> value="AF">Afghanistan</option>
        <option value="AX">Åland Islands</option>
        <option value="AL">Albania</option>
        <option value="DZ">Algeria</option>
        <option value="AS">American Samoa</option>
        <option value="AD">Andorra</option>
        <option value="AO">Angola</option>
        <option value="AI">Anguilla</option>
        <option value="AQ">Antarctica</option>
        <option value="AG">Antigua and Barbuda</option>
        <option value="AR">Argentina</option>
        <option value="AM">Armenia</option>
        <option value="AW">Aruba</option>
        <option value="AU">Australia</option>
        <option value="AT">Austria</option>
        <option value="AZ">Azerbaijan</option>
        <option value="BS">Bahamas</option>
        <option value="BH">Bahrain</option>
        <option value="BD">Bangladesh</option>
        <option value="BB">Barbados</option>
        <option value="BY">Belarus</option>
        <option value="BE">Belgium</option>
        <option value="BZ">Belize</option>
        <option value="BJ">Benin</option>
        <option value="BM">Bermuda</option>
        <option value="BT">Bhutan</option>
        <option value="BO">Bolivia, Plurinational State of</option>
        <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
        <option value="BA">Bosnia and Herzegovina</option>
        <option value="BW">Botswana</option>
        <option value="BV">Bouvet Island</option>
        <option value="BR">Brazil</option>
        <option value="IO">British Indian Ocean Territory</option>
        <option value="BN">Brunei Darussalam</option>
        <option value="BG">Bulgaria</option>
        <option value="BF">Burkina Faso</option>
        <option value="BI">Burundi</option>
        <option value="KH">Cambodia</option>
        <option value="CM">Cameroon</option>
        <option value="CA">Canada</option>
        <option value="CV">Cape Verde</option>
        <option value="KY">Cayman Islands</option>
        <option value="CF">Central African Republic</option>
        <option value="TD">Chad</option>
        <option value="CL">Chile</option>
        <option value="CN">China</option>
        <option value="CX">Christmas Island</option>
        <option value="CC">Cocos (Keeling) Islands</option>
        <option value="CO">Colombia</option>
        <option value="KM">Comoros</option>
        <option value="CG">Congo</option>
        <option value="CD">Congo, the Democratic Republic of the</option>
        <option value="CK">Cook Islands</option>
        <option value="CR">Costa Rica</option>
        <option value="CI">Côte d'Ivoire</option>
        <option value="HR">Croatia</option>
        <option value="CU">Cuba</option>
        <option value="CW">Curaçao</option>
        <option value="CY">Cyprus</option>
        <option value="CZ">Czech Republic</option>
        <option value="DK">Denmark</option>
        <option value="DJ">Djibouti</option>
        <option value="DM">Dominica</option>
        <option value="DO">Dominican Republic</option>
        <option value="EC">Ecuador</option>
        <option value="EG">Egypt</option>
        <option value="SV">El Salvador</option>
        <option value="GQ">Equatorial Guinea</option>
        <option value="ER">Eritrea</option>
        <option value="EE">Estonia</option>
        <option value="ET">Ethiopia</option>
        <option value="FK">Falkland Islands (Malvinas)</option>
        <option value="FO">Faroe Islands</option>
        <option value="FJ">Fiji</option>
        <option value="FI">Finland</option>
        <option value="FR">France</option>
        <option value="GF">French Guiana</option>
        <option value="PF">French Polynesia</option>
        <option value="TF">French Southern Territories</option>
        <option value="GA">Gabon</option>
        <option value="GM">Gambia</option>
        <option value="GE">Georgia</option>
        <option value="DE">Germany</option>
        <option value="GH">Ghana</option>
        <option value="GI">Gibraltar</option>
        <option value="GR">Greece</option>
        <option value="GL">Greenland</option>
        <option value="GD">Grenada</option>
        <option value="GP">Guadeloupe</option>
        <option value="GU">Guam</option>
        <option value="GT">Guatemala</option>
        <option value="GG">Guernsey</option>
        <option value="GN">Guinea</option>
        <option value="GW">Guinea-Bissau</option>
        <option value="GY">Guyana</option>
        <option value="HT">Haiti</option>
        <option value="HM">Heard Island and McDonald Islands</option>
        <option value="VA">Holy See (Vatican City State)</option>
        <option value="HN">Honduras</option>
        <option value="HK">Hong Kong</option>
        <option value="HU">Hungary</option>
        <option value="IS">Iceland</option>
        <option <?php if ( $selected == 'IN' ) echo 'selected'; ?> value="IN">India</option>
        <option value="ID">Indonesia</option>
        <option value="IR">Iran, Islamic Republic of</option>
        <option value="IQ">Iraq</option>
        <option value="IE">Ireland</option>
        <option value="IM">Isle of Man</option>
        <option value="IL">Israel</option>
        <option value="IT">Italy</option>
        <option value="JM">Jamaica</option>
        <option value="JP">Japan</option>
        <option value="JE">Jersey</option>
        <option value="JO">Jordan</option>
        <option value="KZ">Kazakhstan</option>
        <option value="KE">Kenya</option>
        <option value="KI">Kiribati</option>
        <option value="KP">Korea, Democratic People's Republic of</option>
        <option value="KR">Korea, Republic of</option>
        <option value="KW">Kuwait</option>
        <option value="KG">Kyrgyzstan</option>
        <option value="LA">Lao People's Democratic Republic</option>
        <option value="LV">Latvia</option>
        <option value="LB">Lebanon</option>
        <option value="LS">Lesotho</option>
        <option value="LR">Liberia</option>
        <option value="LY">Libya</option>
        <option value="LI">Liechtenstein</option>
        <option value="LT">Lithuania</option>
        <option value="LU">Luxembourg</option>
        <option value="MO">Macao</option>
        <option value="MK">Macedonia, the former Yugoslav Republic of</option>
        <option value="MG">Madagascar</option>
        <option value="MW">Malawi</option>
        <option value="MY">Malaysia</option>
        <option value="MV">Maldives</option>
        <option value="ML">Mali</option>
        <option value="MT">Malta</option>
        <option value="MH">Marshall Islands</option>
        <option value="MQ">Martinique</option>
        <option value="MR">Mauritania</option>
        <option value="MU">Mauritius</option>
        <option value="YT">Mayotte</option>
        <option value="MX">Mexico</option>
        <option value="FM">Micronesia, Federated States of</option>
        <option value="MD">Moldova, Republic of</option>
        <option value="MC">Monaco</option>
        <option value="MN">Mongolia</option>
        <option value="ME">Montenegro</option>
        <option value="MS">Montserrat</option>
        <option value="MA">Morocco</option>
        <option value="MZ">Mozambique</option>
        <option value="MM">Myanmar</option>
        <option value="NA">Namibia</option>
        <option value="NR">Nauru</option>
        <option value="NP">Nepal</option>
        <option value="NL">Netherlands</option>
        <option value="NC">New Caledonia</option>
        <option value="NZ">New Zealand</option>
        <option value="NI">Nicaragua</option>
        <option value="NE">Niger</option>
        <option value="NG">Nigeria</option>
        <option value="NU">Niue</option>
        <option value="NF">Norfolk Island</option>
        <option value="MP">Northern Mariana Islands</option>
        <option value="NO">Norway</option>
        <option value="OM">Oman</option>
        <option value="PK">Pakistan</option>
        <option value="PW">Palau</option>
        <option value="PS">Palestinian Territory, Occupied</option>
        <option value="PA">Panama</option>
        <option value="PG">Papua New Guinea</option>
        <option value="PY">Paraguay</option>
        <option value="PE">Peru</option>
        <option value="PH">Philippines</option>
        <option value="PN">Pitcairn</option>
        <option value="PL">Poland</option>
        <option value="PT">Portugal</option>
        <option value="PR">Puerto Rico</option>
        <option value="QA">Qatar</option>
        <option value="RE">Réunion</option>
        <option value="RO">Romania</option>
        <option value="RU">Russian Federation</option>
        <option value="RW">Rwanda</option>
        <option value="BL">Saint Barthélemy</option>
        <option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
        <option value="KN">Saint Kitts and Nevis</option>
        <option value="LC">Saint Lucia</option>
        <option value="MF">Saint Martin (French part)</option>
        <option value="PM">Saint Pierre and Miquelon</option>
        <option value="VC">Saint Vincent and the Grenadines</option>
        <option value="WS">Samoa</option>
        <option value="SM">San Marino</option>
        <option value="ST">Sao Tome and Principe</option>
        <option value="SA">Saudi Arabia</option>
        <option value="SN">Senegal</option>
        <option value="RS">Serbia</option>
        <option value="SC">Seychelles</option>
        <option value="SL">Sierra Leone</option>
        <option value="SG">Singapore</option>
        <option value="SX">Sint Maarten (Dutch part)</option>
        <option value="SK">Slovakia</option>
        <option value="SI">Slovenia</option>
        <option value="SB">Solomon Islands</option>
        <option value="SO">Somalia</option>
        <option value="ZA">South Africa</option>
        <option value="GS">South Georgia and the South Sandwich Islands</option>
        <option value="SS">South Sudan</option>
        <option value="ES">Spain</option>
        <option value="LK">Sri Lanka</option>
        <option value="SD">Sudan</option>
        <option value="SR">Suriname</option>
        <option value="SJ">Svalbard and Jan Mayen</option>
        <option value="SZ">Swaziland</option>
        <option value="SE">Sweden</option>
        <option value="CH">Switzerland</option>
        <option value="SY">Syrian Arab Republic</option>
        <option value="TW">Taiwan, Province of China</option>
        <option value="TJ">Tajikistan</option>
        <option value="TZ">Tanzania, United Republic of</option>
        <option value="TH">Thailand</option>
        <option value="TL">Timor-Leste</option>
        <option value="TG">Togo</option>
        <option value="TK">Tokelau</option>
        <option value="TO">Tonga</option>
        <option value="TT">Trinidad and Tobago</option>
        <option value="TN">Tunisia</option>
        <option value="TR">Turkey</option>
        <option value="TM">Turkmenistan</option>
        <option value="TC">Turks and Caicos Islands</option>
        <option value="TV">Tuvalu</option>
        <option value="UG">Uganda</option>
        <option value="UA">Ukraine</option>
        <option value="AE">United Arab Emirates</option>
        <option value="GB">United Kingdom</option>
        <option value="US">United States</option>
        <option value="UM">United States Minor Outlying Islands</option>
        <option value="UY">Uruguay</option>
        <option value="UZ">Uzbekistan</option>
        <option value="VU">Vanuatu</option>
        <option value="VE">Venezuela, Bolivarian Republic of</option>
        <option value="VN">Viet Nam</option>
        <option value="VG">Virgin Islands, British</option>
        <option value="VI">Virgin Islands, U.S.</option>
        <option value="WF">Wallis and Futuna</option>
        <option value="EH">Western Sahara</option>
        <option value="YE">Yemen</option>
        <option value="ZM">Zambia</option>
        <option value="ZW">Zimbabwe</option>
    </select>
    <?php
}


/*
 * Ckeck old password exist or not
 */

function swr_check_old_password() {
    
    global $current_user;

    if ( isset( $_POST['old_password'] ) ) {

        $old_password = sanitize_text_field( $_POST['old_password'] );
        $hash_pass = $current_user->user_pass;
        $check_password = wp_check_password( $old_password, $hash_pass, $current_user->ID );

        if ( $check_password ) {
            echo "true";
        } else {
            echo "false";
        }
    }
    die();
}
add_action( "wp_ajax_swr_check_old_password", "swr_check_old_password" );
add_action( "wp_ajax_nopriv_swr_check_old_password", "swr_check_old_password" );

 /*
 * Generate random string
 */
function swr_random_paymentid_generate( $args = array() ){
    
    $random_string = '';
            
    $defaults = array(  // Set some defaults for the function to use
        'characters'    => '',
        'length'        => '',
        'before'        => '',
        'after'         => '',
        'echo'          => false
    );
    $args = wp_parse_args( $args, $defaults );    // Parse the args passed by the user with the defualts to generate a final '$args' array

    if( absint( $args['length'] ) < 1 ) // Ensure that the length is valid
        return;

    $characters_count = strlen( $args['characters'] );    // Check how many characters the random string is to be assembled from
    for( $i = 0; $i <= $args['length']; $i++ ) :          // Generate a random character for each of '$args['length']'

        $start = mt_rand( 0, $characters_count );
        $random_string.= substr($args['characters'], $start, 1);

    endfor;

    $random_string = $args['before'] . $random_string . $args['after']; // Add the before and after strings to the random string

    if( $args['echo'] ) : // Check if the random string shoule be output or returned
        echo $random_string;
    else :
        return $random_string;
    endif;

}