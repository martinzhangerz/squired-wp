<?php
/**
 * The sidebar containing the WooCommerce widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package BuddyBoss_Theme
 */

?>

<?php if ( is_active_sidebar( 'woo_sidebar' ) ) : ?>
    <div id="secondary" class="widget-area sm-grid-1-1 wc-widget-area">
        <div class="widget woocommerce widgets_expand">
            <h2 class="widget-title"><?php echo __( 'Product Filters', 'buddyboss-theme' ); ?><span class="wc-widget-area-expand"><i class="bb-icon-bars"></i></span></h2>
        </div>
        <div class="wc-widget-area-expandable">
    	   <?php dynamic_sidebar( 'woo_sidebar' ); ?>
        </div>
    </div><!-- #secondary -->
<?php endif; ?>
