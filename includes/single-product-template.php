<?php get_header(); 
/*
 * Display single product
 */
?>
<div class="swr-content">
    <div class="swr-single">
        <div class="swr-container">
            <?php
            if ( is_singular( 'swr_product' ) ) :
                
                while ( have_posts() ) : the_post();
            
                    $productactualprice = get_post_meta( $post->ID, 'actual_price', true );
                    $productsellprice = get_post_meta( $post->ID, 'sell_price', true );
                    $productimg = get_post_meta( $post->ID, 'product_image', true );
                    $stock_quantity = get_post_meta( $post->ID, 'stock_quantity', true );

                    if ( !empty( $productactualprice ) ) {
                       $total_percentage = floor( ( $productactualprice - $productsellprice ) / $productactualprice * 100 );
                    }
                    ?>
                    <form method="post" id="WRL_form" name="WRL_form" action="<?php echo home_url( "checkout" ); ?>">
                        <?php wp_nonce_field( 'order_payment_action', 'order_payment_field' ); ?>
                        <ul class="product-grid-list detailProduct">
                            <li>
                                <div class="single-section">
                                    <p class="productTitle"><?php the_title(); ?></p>
                                    <div class="single-pic">
                                        <img src="<?php echo $productimg; ?>" height="300" width="300" alt="" class="single-product-img"/>
                                    </div>
                                    <?php
                                    if ( absint( $productsellprice ) ) {
                                        
                                        if ( !empty( $productactualprice ) && absint( $productactualprice ) ) {
                                            if ( $total_percentage > 0 ) {
                                                echo '<p>Actual Price : <strike>' . RS_SYMBOL . ' ' . trim( $productactualprice ) . '</strike>';
                                                echo ' ( ' .ceil( $total_percentage ) . '% off )</p>';
                                            }
                                        }
                                        echo '<p class="">Sell Price : <b> ' . RS_SYMBOL . ' ' . trim( $productsellprice ) . '</b> </p>';
                                    }
                                    ?>
                                </div>
                                <div class="content">
                                    
                                <?php 
                                    the_content(); 
                                    
                                    if ( absint( $stock_quantity > 0 ) ) {
                                        echo '<b>'. absint( $stock_quantity ) .' In stock</b>'; 

                                    } else {
                                        echo '<b>Out of stock</b>'; 
                                    }
                                ?>
                                </div>
                            </li>
                        </ul>
                            <?php
                                    if ( !empty( absint( $productsellprice ) && ( absint( $stock_quantity ) > 0 ) ) ) { ?>
                        <input type="hidden" id="h_productID" name="h_productID" value="<?php the_ID(); ?>">
                        <p><input type="submit" name="pay_submit" id="pay_submit" value="Buy Now"></p>
                                    <?php } ?>
                    </form>
                    <?php
                endwhile;
            endif;
            ?>
        </div>
    </div>
</div>
<?php
get_footer();