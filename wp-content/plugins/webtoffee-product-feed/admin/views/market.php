<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wt-profeed-upsell-wrapper market-box table-box-main">
    <div class="wt-profeed-premium-upgrade wt-profeed-sidebar">

		<div class="wt-profeed-header">
                    <div class="wt-feed-why-premium">
                        <div class="why-premium-inner-box" style="padding-right: 0px;">
                            <img style="width:50px;" src="<?php echo esc_url( WT_PRODUCT_FEED_PLUGIN_URL ); ?>/images/crown.svg" alt="alt"/>
                        </div>
			<div class="why-premium-inner-box" style="padding-left: 0px;">	
                            <?php if( isset( $fb_sync_tab ) ){ ?>
				<p style="margin:10px 0px;font-size: 16px;"><?php esc_html_e('What\'s in premium?'); ?></p>
				<?php } else{ ?>
				<p style="margin:10px 0px;font-size: 16px;"><?php esc_html_e('Why use premium?'); ?></p>
				<?php } ?>
                        </div>
                        <div class="why-premium-inner-box">
                        </div>
			</div>
			<div class="wt-profeed wt-profeed_review wt-profeed_tags wt-profeed_categories wt-profeed-gopro-cta wt-profeed-features">
				<ul class="ticked-list wt-profeed-allfeat">
                                    <?php if( isset( $fb_sync_tab ) ): ?>
                                        <li><?php esc_html_e( 'Enable auto-sync for the Facebook catalog', 'webtoffee-product-feed' ); ?></li>				
					<li><?php esc_html_e( 'Add fields like condition, color, gender, material, etc., to products.', 'webtoffee-product-feed' ); ?></li>
					<li><?php esc_html_e( 'Exclude out-of-stock products from syncing', 'webtoffee-product-feed' ); ?></li>
                                        <li><?php esc_html_e( 'Exclude specific products/variations from the sync.', 'webtoffee-product-feed' ); ?></li>            
					<?php else: ?>     
                                        <li><span><b style="font-weight: 800;"><?php esc_html_e( 'Priority support:' ); ?></b>&nbsp;<?php esc_html_e( 'Make feeds live faster', 'webtoffee-product-feed' ); ?></span></li>
                                        <li><span><b style="font-weight: 800;"><?php esc_html_e( 'Filter') ;?></b>&nbsp;<?php esc_html_e( 'unwanted (out of stock, price missing, description missing, etc.) products from your feed for better ad performance', 'webtoffee-product-feed' ); ?></span></li>
                                        <li><span><b style="font-weight: 800;"><?php esc_html_e( 'Variations:', 'webtoffee-product-feed' ); ?></b>&nbsp;<?php esc_html_e( 'Option to choose lowest priced/highest priced/default variant', 'webtoffee-product-feed' ); ?></span></li>
                                        <li><span><b style="font-weight: 800;"><?php esc_html_e( 'WPML' ); ?></b>&nbsp;<?php esc_html_e( 'Multilingual & Multicurrency support', 'webtoffee-product-feed' ); ?></span></li>
                                        <li><span><?php esc_html_e( 'Aelia, Curcy, Fox');?>&nbsp;<b style="font-weight: 800;"><?php esc_html_e( 'currency switcher'); ?></b>&nbsp;<?php esc_html_e( 'plugin compatibility', 'webtoffee-product-feed' ); ?></span></li>  
                                        <li><span><b style="font-weight: 800;"><?php esc_html_e( 'Bulk update price'); ?></b>&nbsp;<?php esc_html_e( 'in the feed (without changing it in the store), allow channel wise ' );?><b style="font-weight: 800;"><?php esc_html_e( 'discount'); ?></b><?php esc_html_e(', etc.', 'webtoffee-product-feed' ); ?></span></li>                                    															                                        
                                        <li><span><b style="font-weight: 800;"><?php esc_html_e( 'Multi-vendor');?></b>&nbsp;<?php esc_html_e( 'compatibility', 'webtoffee-product-feed' ); ?></span></li>                                        
                                        <li><span><?php esc_html_e( 'Supports'); ?>&nbsp;<b style="font-weight: 800;"><?php esc_html_e( 'WooCommerce Brands'); ?></b> <?php esc_html_e( ', YITH WooCommerce Brands Add-on & Perfect Brands for WooCommerce plugins', 'webtoffee-product-feed' ); ?><span></li>
                                        <?php endif; ?>
				</ul>			
			</div>
				<div class="wt-profeed-btn-wrapper">
					<?php if( isset( $utm_source ) ){ ?>
					<a href="<?php echo esc_url( 'https://www.webtoffee.com/product/woocommerce-product-feed/?utm_source='.$utm_source.'&utm_medium=feed_basic&utm_campaign=WooCommerce_Product_Feed&utm_content=' . WEBTOFFEE_PRODUCT_FEED_SYNC_VERSION); ?>" class="wt-profeed-blue-btn-new" target="_blank"><?php esc_html_e( 'Unlock Pro features', 'webtoffee-product-feed' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
					<?php }else { ?>
						<a href="<?php echo esc_url("https://www.webtoffee.com/product/woocommerce-product-feed/?utm_source=free_plugin_new_feed&utm_medium=feed_basic&utm_campaign=WooCommerce_Product_Feed&utm_content=" . WEBTOFFEE_PRODUCT_FEED_SYNC_VERSION); ?>" class="wt-profeed-blue-btn-new" target="_blank"><?php esc_html_e( 'Unlock Pro features', 'webtoffee-product-feed' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
					<?php } ?>                    
                </div>               			
		</div>	
		<div class="wt-feed-cs-rating-money-back">
			<div class="wt-feed-money-back">
				<img src="<?php echo esc_url( WT_PRODUCT_FEED_PLUGIN_URL ); ?>assets/images/gopro/wt-money-back.svg" alt="alt"/>
				<p><?php echo sprintf(esc_html__('You are covered by our %s 30-day money back guarantee %s'), '<b>', '</b>'); ?></p>
			</div>
			<div class="wt-feed-cs-rating">
				<img src="<?php echo esc_url( WT_PRODUCT_FEED_PLUGIN_URL ); ?>assets/images/gopro/wt-satisfaction-rating.svg"" alt="alt"/>
				<p><?php echo sprintf(esc_html__('Supported by a team with %s %s customer satisfaction %s score'), '<b>', '99%', '</b>'); ?></p>
			</div>
		</div>
    </div>
</div>