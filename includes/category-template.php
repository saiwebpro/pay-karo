<?php  
/*
 * Display all category
 */
function swr_product_list() {
    
    $args = array(
        'type' => 'post',
        'post_type' => 'swr_product',
        'orderby' => 'name',
        'order' => 'asc',
        'hide_empty' => 0,
        'hierarchical' => 1,
        'exclude' => '',
        'include' => '',
        'number' => '',
        'taxonomy' => 'swr_productcat',
        'pad_counts' => false
    );
    $categories = get_categories( $args );
    $noimage = SWR_PLUGIN_PATH . 'assets/images/noimage.png';
    echo '<h3>Category</h3>';
    
    echo '<ul class="product-grid-list rig columns-4">';
    foreach ( $categories as $cat ) {
        $t_id = $cat->term_id;
        $term_meta = get_option( "swr_productcat_$t_id" );
        
        echo '<li class="single-product-item">';
        echo '<div class="product-item">';
        if ( empty( $term_meta[ 'image' ] ) ) {
            echo '<a class="img-class" href="' . get_category_link( $cat->term_id ) . '"><img src=" ' . $noimage . ' " class="image_responsive"></a>';
        } else {
            echo '<a class="img-class" href="' . get_category_link( $cat->term_id ) . '"><img src=" ' . $term_meta[ 'image' ] . '" class="image_responsive"></a>';
        }
        echo '<a href="' . get_category_link( $cat->term_id ) . '">' . $cat->name . '</a> <br/>';
    }
    echo '</div>';
    echo '</li></ul>';
}
add_shortcode( 'display_product_list', 'swr_product_list' );