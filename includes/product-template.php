<?php get_header(); 

/*
 * Display all product on product page
 */
?>
<div class="mainPluginBox">
    <?php
    $category = get_queried_object();
    $categoryname = $category->slug;

    $metaquery = '';
    $search_product_name = '';
    $lower_price = '';
    $higher_price = '';

    if ( isset( $_POST['submit_search_product'] ) ) {
        $search_product_name = sanitize_text_field( $_POST['search_product'] );
    }
    if ( isset( $_POST['search_price_filter'] ) ) {

        $lower_price = intval( sanitize_text_field( $_POST['lower_price'] ) );
        $higher_price = intval( sanitize_text_field( $_POST['higher_price'] ) );
        $metaquery = array(
            array(
                'key' => 'sell_price',
                'value' => array( $lower_price, $higher_price ),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            )
        );
    }
    $query_args_meta = array(
        'post_type' => 'swr_product',
        's' => $search_product_name,
        'meta_key' => 'sell_price',
        'meta_query' => $metaquery,
        'tax_query' => array(
            array(
                'taxonomy' => 'swr_productcat',
                'field' => 'slug',
                'terms' => $categoryname,
            ),
        )
    );
    $meta_query = new WP_Query( $query_args_meta );

    global $post;
    ?>
    <form name="search_product_form" method="post">
        <input type="text" name="search_product" id="search_product" value="<?php echo $search_product_name; ?>" placeholder="Search Product">
        <input type="submit" name="submit_search_product" id="submit_search_product" value="Search">
    </form>
    <form name="price_filter_form" method="post">
        <input type="text" name="lower_price" id="lower_price" value="<?php $lower_price; ?>" placeholder="Low Price(From)">
        <input type="text" name="higher_price" id="higher_price" value="<?php $higher_price; ?>" placeholder="High Price(TO)">
        <input type="submit" name="search_price_filter" id="search_price_filter" value="View Product">
    </form>

    <?php if ($meta_query->have_posts()) : ?>

        <header class="archive-header productTitle">
            <h1 class="archive-title"><?php printf( __('%s', 'twentyfourteen' ), single_cat_title( '', false ) ); ?></h1>
        </header><!-- .archive-header -->

        <div class="product-list-main grid-container">
            <ul class="product-grid-list rig columns-4">
                <?php
                while ( $meta_query->have_posts() ) : $meta_query->the_post();
                
                    $productactualprice = get_post_meta( $post->ID, 'actual_price', true );
                    $productsellprice = get_post_meta( $post->ID, 'sell_price', true );
                    $productimg = get_post_meta( $post->ID, 'product_image', true );
                    $stock_quantity = get_post_meta( $post->ID, 'stock_quantity', true );

                    if ( !empty( $productactualprice ) ) {
                        $total_percentage = ( $productactualprice - $productsellprice ) / $productactualprice * 100;
                    }
                    ?>

                    <li class="single-product-item" id="<?php echo the_ID(); ?>">
                        <form method="post" id="WRL_form" name="WRL_form" action="<?php echo home_url( "checkout" ); ?>">
                            <a href="<?php the_permalink(); ?>" class="bl1"><img src="<?php echo $productimg; ?>" height="200" width="200" alt=""/></a>
                            <div class="listDesc">
                                <div class="innerlistDesc">
                                    <a href="<?php the_permalink(); ?>" class="bl1desc"><?php the_title(); ?></a>
                                    <?php
                                    if ( absint( $productsellprice ) ) {
                                        
                                        if ( absint($stock_quantity > 0 ) ) {
                                            echo '<p>'. absint( $stock_quantity ) .' In stock</p>'; 
                                        
                                        } else {
                                            echo '<p>Out of stock</p>'; 
                                        }
                                        
                                        if ( !empty( $productactualprice ) && absint( $productactualprice ) ) {
                                            if ( $total_percentage > 0 ) {
                                                echo '<p><strike>' . RS_SYMBOL . ' ' . trim( $productactualprice ) . '</strike>';
                                                echo '(' . ceil( $total_percentage ) . '% off)</p>';
                                            }
                                        }
                                        echo '<p class="price">' . RS_SYMBOL . ' ' . trim( $productsellprice ) . '</p>';
                                    ?>
                                    </div>
                                
                                <?php if ( absint( $stock_quantity ) > 0 ) { ?>
                                
                                    <div class="addtocartBox">
                                        <input type="hidden" id="h_productID" name="h_productID" value="<?php the_ID(); ?>">
                                        <input type="submit" name="pay_submit" class="addtocart" id="pay_submit" value="Buy Now">
                                    </div>
                                <?php 
                                        } 
                                    } 
                                ?>
                            </div>
                        </form>
                    </li>

                    <?php
                endwhile;
            else :
                echo '<p>No Product Found</p>';
                ?>
            </ul>
        </div>
    <?php endif; ?>
</div><!-- #mainPluginBox-->
<?php
get_footer();
