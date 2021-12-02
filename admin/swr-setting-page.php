<?php
/*
 * SWR setting page callbackk
 */
function swr_setting_page_callback() {
    
    if ( isset( $_GET [ 'tab' ] ) ) {
        $active_tab = $_GET[ 'tab' ];
    } else {
        $active_tab = 'tab_rezorpay';
    }
    ?>  

    <div class="wrap">
        <h2><?php _e ( 'Pay Karo Settings' ); ?> </h2>
        
        <script type="text/javascript">
            jQuery ( document ).ready( function() {
                    jQuery( '#upload_logo_button' ).click( function() {
                     formfield = jQuery( '.swr_custom_logo_url' ).attr( 'name' );
                     tb_show ( '', 'media-upload.php?type=image&TB_iframe=true' );
                     return false;
                    });
                    
                        window.send_to_editor = function( html ) {
                            imgurl = jQuery ( 'img', html ).attr( 'src' );
                            jQuery ( '.swr_custom_logo_url' ). val ( imgurl );
                            tb_remove ();
                        }
                    });

            </script>
            
        <h2 class="nav-tab-wrapper">  
            <a href="?page=swr-setting-page&tab=tab_rezorpay" class="nav-tab <?php echo $active_tab == 'tab_rezorpay' ? 'nav-tab-active' : ''; ?>"><?php _e ( 'Razorpay Payment Settings' ); ?></a>
            <a href="?page=swr-setting-page&tab=tab_shipping" class="nav-tab <?php echo $active_tab == 'tab_shipping' ? 'nav-tab-active' : ''; ?>"><?php _e ( 'Shipping Amount' ); ?></a>
            <a href="?page=swr-setting-page&tab=tab_email" class="nav-tab <?php echo $active_tab == 'tab_email' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Emails' ); ?></a>  
        </h2> 
            
        <form method="post" action="options.php"> 
            <?php
            if ( $active_tab == 'tab_rezorpay' ) {
                settings_fields ( 'swr-plugin-setting-group-1' );
                do_settings_sections ( 'swr-plugin-setting-group-1' );
            } elseif ($active_tab == 'tab_shipping' ) {
                settings_fields ( 'swr-plugin-setting-group-2' );
                do_settings_sections ( 'swr-plugin-setting-group-2' );
            } elseif ($active_tab == 'tab_email' ) {
                settings_fields ( 'swr-plugin-setting-group-3' );
                do_settings_sections ( 'swr-plugin-setting-group-3' );
            }
            submit_button();
            ?> 
        </form> 
    </div>
    <?php
}


/*
 * Register admin setting for SWR
 */

function swr_sandbox_initialize_theme_options() {
    
    add_settings_section ( 'razorpay_section', 'Razorpay Payment Settings', 'swr_razorpay_section_callback', 'swr-plugin-setting-group-1' );
    add_settings_section ( 'shipping_section', 'Shipping Amount ', 'swr_shipping_section_callback', 'swr-plugin-setting-group-2' );
    add_settings_section ( 'email_section', 'Emails', 'swr_email_section_callback', 'swr-plugin-setting-group-3' );

    register_setting ( 'swr-plugin-setting-group-1', 'swr_api_key' );
    register_setting ( 'swr-plugin-setting-group-1', 'swr_api_secret' );
    register_setting ( 'swr-plugin-setting-group-1', 'swr_api_url' );
    register_setting ( 'swr-plugin-setting-group-1', 'swr_custom_title' );
    register_setting ( 'swr-plugin-setting-group-1', 'swr_custom_logo' );
    register_setting ( 'swr-plugin-setting-group-2', 'swr_shipping_amount_checked' );
    register_setting ( 'swr-plugin-setting-group-2', 'swr_shipping_amount' );
    register_setting ( 'swr-plugin-setting-group-3', 'swr_from_email' );
    register_setting ( 'swr-plugin-setting-group-3', 'swr_email_cc' );
    register_setting ( 'swr-plugin-setting-group-3', 'swr_email_subject' );
    register_setting ( 'swr-plugin-setting-group-3', 'swr_email_messagebody' );
    
}
add_action ( 'admin_init', 'swr_sandbox_initialize_theme_options');


/*
 * Razorpay API setting section in backend
 */

function swr_razorpay_section_callback() {
    
    ?>
    <p> <label> <b> <?php _e ( 'API Key: '); ?> </b> </label> &nbsp; &nbsp;
        <input type="text" id="swr_api_key" class="swr_api_key" name="swr_api_key" size="25" value="<?php echo get_option ( 'swr_api_key' ); ?>" >
        <i> <?php _e ( 'You can generate a api key' ) ?> </i> 
        <a href="https://dashboard.razorpay.com/#/access/signup" target="_blank"> Click Here </a>
    </p> 
    
    <p> <label> <b> <?php _e ( 'API Secret: '); ?> </b> </label> &nbsp;
        <input type="text" id="swr_api_secret" class="swr_api_secret" name="swr_api_secret" size="25" value="<?php echo get_option ( 'swr_api_secret' ); ?>" >
    </p> 
    
    <p> <label> <b> <?php _e ( 'Api URL: '); ?> </b> </label> &nbsp; &nbsp;
        <input type="text" id="swr_api_url" class="swr_api_url" name="swr_api_url" size="40" value="<?php echo get_option ( 'swr_api_url' ); ?>" >
    </p>
    
    <p> <label> <b> <?php _e ( 'Razorpay Title: '); ?> </b> </label> &nbsp; &nbsp;
        <input type="text" id="swr_custom_title" class="swr_custom_title" name="swr_custom_title" size="40" value="<?php echo get_option ( 'swr_custom_title' ); ?>">
    </p>
    
    <p> <label> <b> <?php _e ( 'Razorpay Logo: '); ?> </b> </label>
        <img class="swr_custom_logo" src=" <?php echo get_option ( 'swr_custom_logo' ) ; ?> " height="100" width="100" />
        <input class="swr_custom_logo_url" type="text" name="swr_custom_logo" size="60" value="<?php echo get_option ( 'swr_custom_logo' ); ?>">
        <input id="upload_logo_button" type="button" class="button" value="Upload" /> <?php update_option ( 'image_default_link_type', 'file' ); ?>
    <span class="description"> <?php _e ( 'Upload an image for the API custom logo.' ); ?> </span>
    </p>
    <?php
    
}


/*
 * Shipping setting section in backend
 */
function swr_shipping_section_callback() {
    
    ?>
    <p> <label> <b> <?php _e ( 'Free Shipping ' ); ?> </b> </label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
        <input type="checkbox" name="swr_shipping_amount_checked" id="swr_shipping_amount_checked" value="1" <?php checked ( 1, get_option( 'swr_shipping_amount_checked' ), true ); ?> /> <label> 
        <i> <?php _e ( 'If free shipping checked, no shipping amount apply on product.' ); ?> </i> </label> 
    </p> 

    <p> <label> <b><?php _e ( 'Shipping amount: '); ?> </b> </label> &nbsp; <?php echo RS_SYMBOL ?> &nbsp;
        <input type="text" id="swr_shipping_amount" class="swr_shipping_amount" name="swr_shipping_amount" size="5" value=" <?php echo absint( get_option( 'swr_shipping_amount' ) ); ?> ">
    </p>
    <?php
    
}


/*
 * Email setting section in backend
 */
function swr_email_section_callback() {
    
    ?>
    <p> <label> <b> <?php _e (' From: '); ?> </b> </label> &nbsp;
        <input type="text" id="swr_from_email" class="swr_from_email" name="swr_from_email" size="40" value=" <?php echo get_option( 'swr_from_email' ); ?>" >
    </p>
    
    <p> <label> <b> <?php _e ( 'CC: '); ?> </b> </label> &nbsp; &nbsp; 
        <input type="text" id="swr_email_cc" class="swr_email_cc" name="swr_email_cc" size="40" value="<?php echo get_option( 'swr_email_cc' ); ?>"> 
        <label> <i> <?php _e ( 'If not enter email by default consider admin email.' );?></i> </label>
    </p>
    
    <p> <label> <b> <?php _e ( 'Subject:' ); ?> </b> </label> &nbsp; 
        <input type="text" id="swr_email_subject" class="swr_email_subject" name="swr_email_subject" size="40" value="<?php echo get_option( 'swr_email_subject' ); ?> "> 
    </p>
    
    <p> <label> <b> <?php _e ( 'Message: '); ?> </b> </label> &nbsp;
        
        <?php
        $content = get_option( 'swr_email_messagebody' );
        wp_editor( $content, 'swr_email_messagebody' );
        ?>
        
    </p>
    
<?php  
    }
