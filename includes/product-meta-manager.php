<?php
/*
 * Create Custom Post Type for Add Product
 */
add_action( 'init', 'swr_create_custom_post_type', 0 );

function swr_create_custom_post_type() {
    
    register_post_type( 'SWR_Product', array(
        'labels' => array(
            'name' => 'SWR Product',
            'singular_name' => 'SWR Product',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Product',
            'edit' => 'Edit',
            'edit_item' => 'Edit Product',
            'new_item' => 'New Product',
            'view' => 'View',
            'view_item' => 'View Product',
            'search_items' => 'Search Product',
            'not_found' => 'No Product found',
            'not_found_in_trash' => 'No Product found in Trash',
            'parent' => 'Parent Product'
        ),
        'public' => true,
        'menu_position' => null,
        'supports' => array( 'title', 'editor' ),
        'has_archive' => true,
        'capability_type' => 'post',
        'menu_icon' => 'dashicons-cart'
            )
    );
}


/*
 * Create Custom taxonomy
 */
add_action( 'init', 'swr_product_register_taxonomy' );

function swr_product_register_taxonomy() {
    
    $labels = array(
        'name' => 'Product Categories',
        'singular_name' => 'Product Category',
        'search_items' => 'Search Product Categories',
        'all_items' => 'All Product Categories',
        'edit_item' => 'Edit Product Category',
        'update_item' => 'Update Product Category',
        'add_new_item' => 'Add New Product Category',
        'new_item_name' => 'New Product Category',
        'menu_name' => 'Product Categories'
    );
    register_taxonomy( 'swr_productcat', 'swr_product', array(
        'hierarchical' => true,
        'labels' => $labels,
        'query_var' => true,
        'show_admin_column' => true
    ) );
}


/*
 * Create Custom Meta Box
 */
add_action( 'add_meta_boxes', 'swr_add_product_metaboxes' );

function swr_add_product_metaboxes() {
    
    add_meta_box
        (
            'swr_product_price',
            'Product Data',
            'swr_product_metabox_callback',
            'SWR_Product',
            'normal',
            'default'
        );
}

function swr_product_metabox_callback() {
    
    global $post;
    // Noncename needed to verify where the data originated
    echo '<input type="hidden" name="podcastmeta_noncename" id="podcastmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

    global $wpdb;
    $strFile = get_post_meta( $post->ID, $key = 'product_image', true );
    $media_file = get_post_meta( $post->ID, $key = '_wp_attached_file', true );
    if ( !empty( $media_file ) ) {
        $strFile = $media_file;
    } ?>
    <script type = "text/javascript">
        var file_frame;
        jQuery( '#upload_image_button' ).live( 'click', function ( podcast ) {

            podcast.preventDefault();
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: jQuery( this ).data( 'uploader_title' ),
                button: {
                    text: jQuery( this ).data( 'uploader_button_text' ),
                },
                multiple: false // Set to true to allow multiple files to be selected
            });

            // When a file is selected, run a callback.
            file_frame.on( 'select', function () {
                // We set multiple to false so only get one image from the uploader
                attachment = file_frame.state().get( 'selection' ).first().toJSON();

                var url = attachment.url;

                var field = document.getElementById( "product_image" );

                field.value = url; //set which variable you want the field to have
            });

            // Finally, open the modal
            file_frame.open();
        });
    </script>
    <?php
     // Add a nonce field so we can check for it later.
    wp_nonce_field( 'swr_save_product_meta', 'swr_meta_box_nonce' );

    $actual_price = get_post_meta( $post->ID, 'actual_price', true );
    $selling_price = get_post_meta( $post->ID, 'sell_price', true );
    $stock_quantity = get_post_meta( $post->ID, 'stock_quantity', true );
    $poduct_sku = get_post_meta( $post->ID, 'product_sku', true );
    
    echo 'Product Actual Price: <input type="text" name="actual_price" value="' . $actual_price . '" class="widefat"/>';
    echo '<p></p>';
    echo 'Product Selling Price: <input type="text" name="sell_price" value="' . $selling_price . '" class="widefat" required/>';
    echo '<p></p>';
    echo 'Product SKU: <input type="text" name="product_sku" value="' . $poduct_sku . '" class="widefat" required/>';
    echo '<p></p>';
    echo 'Product stock quantity: <input type="text" name="stock_quantity" value="' . $stock_quantity . '" class="widefat" required/>';
    echo '<p></p>';
    echo 'Product Image: <input type="text" name="product_image" id="product_image" value="' . $strFile . '" class="widefat" required/>'
    . '<input id = "upload_image_button" name="upload_image_button" type = "button"  value = "Upload">'; 
}


/*
 * Save custom metabox
 */

function swr_save_product_meta($post_id, $post) {
    
    // Check if our nonce is set.
    if ( !isset( $_POST['swr_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( !wp_verify_nonce( $_POST['swr_meta_box_nonce'], 'swr_save_product_meta' ) && !wp_verify_nonce( $_POST['podcastmeta_noncename'], plugin_basename(__FILE__) ) ) {
        return $post->ID;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( !current_user_can( 'edit_post', $post->ID ) )
        return $post->ID;

    if ( !isset( $_POST['sell_price'] ) && !isset( $_POST['actual_price'] ) && !isset( $_POST['product_sku'] ) && !isset( $_POST['stock_quantity'] ) && !isset( $_POST['fileattach'] ) ) {
        return $post->ID;
    }

    $swr_selling_price = sanitize_text_field( $_POST['sell_price'] );
    $swr_actual_price = sanitize_text_field( $_POST['actual_price'] );
    $swr_stock_quantity = sanitize_text_field( $_POST['stock_quantity'] );
    $swr_product_sku = sanitize_text_field( $_POST['product_sku'] );

    update_post_meta( $post_id, 'sell_price', $swr_selling_price );
    update_post_meta( $post_id, 'actual_price', $swr_actual_price );
    update_post_meta( $post_id, 'stock_quantity', $swr_stock_quantity );
    update_post_meta( $post_id, 'product_sku', $swr_product_sku );
    
    $podcasts_meta['product_image'] = $_POST['product_image'];

    foreach ( $podcasts_meta as $key => $value ) {
        
        if ( $post->post_type == 'SWR_Product' )
            return;
        $value = implode( ',', ( array ) $value );
        if ( get_post_meta( $post->ID, $key, FALSE ) ) {
            update_post_meta( $post->ID, $key, $value );
        } else {
            add_post_meta( $post->ID, $key, $value );
        }
        if ( !$value )
            delete_post_meta( $post->ID, $key ); // Delete if blank value
    }
}
add_action( 'save_post', 'swr_save_product_meta', 1, 2 );


add_filter( 'template_include', 'swr_category_set_template' );
function swr_category_set_template($template) {

    if ( is_tax( 'swr_productcat' ) )
        $template = SWR_PLUGIN_TEMPLATE_PATH . '/product-template.php';

    return $template;
}

function swr_category_is_template( $template_path ) {

    //Get template name
    $template = basename( $template_path );

    if ( 1 == preg_match( '/^product-template((-(\S*))?).php/', $template ) )
        return true;

    return false;
}

/*
 * single.php file
 */
add_filter( 'template_include', 'swr_custom_post_type_template' );
function swr_custom_post_type_template( $single_template ) {
    
    if ( is_singular( 'swr_product' ) && "single-product-template.php" != $single_template ) {

        $single_template = plugin_dir_path(__FILE__) . '/single-product-template.php';
    }
    return $single_template;
}


/*
 * Add Upload fields to "Add New Taxonomy" form
 */

function swr_add_product_image_field() { ?>
    
    <div class="form-field">
        <label for="productcat_image"><?php _e('Product Image:', 'swr_product'); ?></label>
        <input type="text" name="productcat_image[image]" id="productcat_image[image]" class="product-image" value="">
        <input class="upload_image_button button" name="_add_product_image" id="_add_product_image" type="button" value="Select/Upload Image" />
        <script>
            jQuery(document).ready(function () {
                jQuery('#_add_product_image').click( function () {
                    var send_attachment_bkp = wp.media.editor.send.attachment;
                    var button = jQuery( this );
                    var id = button.attr('id').replace('_button', '');
                    _custom_media = true;
                    wp.media.editor.send.attachment = function (props, attachment) {
                        if (_custom_media) {
                            //jQuery("#"+id).val(attachment.url);
                            jQuery('.product-image').val( attachment.url )
                        } else {
                            return _orig_send_attachment.apply( this, [props, attachment] );
                        }
                        ;
                    }

                    wp.media.editor.open( button );
                    return false;
                });
            });
        </script>
    </div>
    <?php
}
add_action( 'swr_productcat_add_form_fields', 'swr_add_product_image_field', 10, 2 );


/*
 * Add Upload fields to "Edit Taxonomy" form
 */
function swr_product_edit_meta_field( $term ) {

    // put the term ID into a variable
    $t_id = $term->term_id;

    // retrieve the existing value(s) for this meta field. This returns an array
    $term_meta = get_option( "swr_productcat_$t_id" ); ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="_productcat_image"><?php _e('Product Image', 'swr_product'); ?></label></th>
        <td>
    <?php $productimage = esc_attr( $term_meta['image'] ) ? esc_attr( $term_meta['image'] ) : ''; ?>
            <input type="text" name="productcat_image[image]" id="productcat_image[image]" class="product-image" value="<?php echo $productimage; ?>">
            <input class="upload_image_button button" name="_productcat_image" id="_productcat_image" type="button" value="Select/Upload Image" />
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"></th>
        <td style="height: 150px;">
            <style>
                div.img-wrap {
                    background-size:contain; 
                    max-width: 450px; 
                    max-height: 150px; 
                    width: 100%; 
                    height: 100%; 
                    overflow:hidden; 
                }
                div.img-wrap img {
                    max-width: 450px;
                }
            </style>
            <div class="img-wrap">
                <img src="<?php echo $productimage; ?>" id="product-img">
            </div>
            <script>
                jQuery( document ).ready( function () {
                    jQuery('#_productcat_image').click( function () {
                        wp.media.editor.send.attachment = function ( props, attachment ) {
                            jQuery('#product-img').attr( "src", attachment.url )
                            jQuery('.product-image').val( attachment.url )
                        }
                        wp.media.editor.open( this );
                        return false;
                    });
                });
            </script>
        </td>
    </tr>
    <?php
}
add_action( 'swr_productcat_edit_form_fields', 'swr_product_edit_meta_field', 10, 2 );


/*
 * Save Taxonomy Image fields callback function.
 */

function swr_save_product_custom_meta($term_id) {
    
    if ( isset( $_POST['productcat_image'] ) ) {
        
        $t_id = $term_id;
        $term_meta = get_option( "swr_productcat_$t_id" );
        $cat_keys = array_keys( $_POST['productcat_image'] );
        
        foreach ( $cat_keys as $key ) {
            
            if ( isset( $_POST['productcat_image'][ $key ] ) ) {
                
                $term_meta[$key] = $_POST['productcat_image'][$key];
            }
        }
        // Save the option array.
        update_option( "swr_productcat_$t_id", $term_meta );
    }
}
add_action( 'edited_swr_productcat', 'swr_save_product_custom_meta', 10, 2 );
add_action( 'create_swr_productcat', 'swr_save_product_custom_meta', 10, 2 );