<?php

if (!defined('WPINC')) {
    exit;
}

if(!class_exists('Webtoffee_Product_Feed_Sync_Facebook_Export')){
class Webtoffee_Product_Feed_Sync_Facebook_Export extends Webtoffee_Product_Feed_Product {

    public $parent_module = null;
	public $product;
	public $current_product_id;
	public $form_data;

	public function __construct($parent_object) {

        $this->parent_module = $parent_object;
    }

    public function prepare_header() {

        $export_columns = $this->parent_module->get_selected_column_names();
        return apply_filters('hf_alter_product_export_csv_columns', $export_columns);
    }

    /**
     * Prepare data that will be exported.
     */
    public function prepare_data_to_export($form_data, $batch_offset,$step) {

	$this->form_data = $form_data;
		
        $include_products = !empty($form_data['filter_form_data']['wt_pf_product']) ? $form_data['filter_form_data']['wt_pf_product'] : '';
        $exclude_products = !empty($form_data['filter_form_data']['wt_pf_exclude_product']) ? $form_data['filter_form_data']['wt_pf_exclude_product'] : '';
	$exp_stock_status = !empty($form_data['filter_form_data']['wt_pf_stock_status']) ? $form_data['filter_form_data']['wt_pf_stock_status'] : '';
		
        $prod_exc_categories = !empty($form_data['post_type_form_data']['item_exc_cat']) ? $form_data['post_type_form_data']['item_exc_cat'] : array();            
        $prod_inc_categories = !empty($form_data['post_type_form_data']['item_inc_cat']) ? $form_data['post_type_form_data']['item_inc_cat'] : array();

        $cat_filter_type = !empty($form_data['post_type_form_data']['cat_filter_type']) ? $form_data['post_type_form_data']['cat_filter_type'] : '';
        if( '' === $cat_filter_type ){
            $cat_filter_type = !empty($form_data['post_type_form_data']['wt_pf_export_cat_filter_type']) ? $form_data['post_type_form_data']['wt_pf_export_cat_filter_type'] : 'include_cat';
        }

        $inc_exc_category = !empty($form_data['post_type_form_data']['inc_exc_cat']) ? $form_data['post_type_form_data']['inc_exc_cat'] : array();
        if( empty($inc_exc_category) ){
            $inc_exc_category = !empty($form_data['post_type_form_data']['wt_pf_inc_exc_category']) ? $form_data['post_type_form_data']['wt_pf_inc_exc_category'] : array();
        }

        if ('include_cat' === $cat_filter_type) {
            $prod_inc_categories = $inc_exc_category;
        } else {
            $prod_exc_categories = $inc_exc_category;
        }
             
        $prod_tags = !empty($form_data['filter_form_data']['wt_pf_product_tags']) ? $form_data['filter_form_data']['wt_pf_product_tags'] : array();
        $prod_types = !empty($form_data['filter_form_data']['wt_pf_product_types']) ? $form_data['filter_form_data']['wt_pf_product_types'] : array();
        $prod_status = !empty($form_data['filter_form_data']['wt_pf_product_status']) ? $form_data['filter_form_data']['wt_pf_product_status'] : array();
        
        $export_sortby = !empty($form_data['filter_form_data']['wt_pf_sort_columns']) ? $form_data['filter_form_data']['wt_pf_sort_columns'] : 'ID';
        $export_sort_order = !empty($form_data['filter_form_data']['wt_pf_order_by']) ? $form_data['filter_form_data']['wt_pf_order_by'] : 'ASC';

        $export_limit = !empty($form_data['filter_form_data']['wt_pf_limit']) ? intval($form_data['filter_form_data']['wt_pf_limit']) : 999999999; //user limit
        $current_offset = !empty($form_data['filter_form_data']['wt_pf_offset']) ? intval($form_data['filter_form_data']['wt_pf_offset']) : 0; //user offset

        $batch_count = !empty($form_data['advanced_form_data']['wt_pf_batch_count']) ? $form_data['advanced_form_data']['wt_pf_batch_count'] : Webtoffee_Product_Feed_Sync_Common_Helper::get_advanced_settings('default_export_batch');
        $batch_count = apply_filters('wt_woocommerce_csv_export_limit_per_request', $batch_count); //ajax batch limit

                
        $real_offset = ($current_offset + $batch_offset);

        if($batch_count<=$export_limit)
        {
            if(($batch_offset+$batch_count)>$export_limit) //last offset
            {
                $limit=$export_limit-$batch_offset;
            }else
            {
                $limit=$batch_count;
            }
        }else
        {
            $limit=$export_limit;
        }
        
        $product_array = array();
	$total_products = 0;
        if ($batch_offset < $export_limit)
        {
            $args = array(
                'status' => array('publish'),
                'type' => array('simple','grouped','external', 'variable'),
                'limit' => $limit,
                'offset' => $real_offset,
                'orderby' => $export_sortby,
                'order' => $export_sort_order,                                
                'return' => 'ids',
                'paginate' => true,
            );

            if (!empty($prod_status)) {
                $args['status'] = $prod_status;
            } 
                
            if (!empty($prod_types)) {
                $args['type'] = $prod_types;
            }

            if (!empty($prod_exc_categories)) {
                $args['exclude_category'] = $prod_exc_categories;
            }

            if (!empty($prod_tags)) {
                $args['tag'] = $prod_tags;
            }

            if (!empty($include_products)) {
                $args['include'] = $include_products;
            }
            if ( !empty( $prod_inc_categories ) ) {
                $args['category'] = $prod_inc_categories;
            }else{
                array_push($args['type'], 'variation');
            }
            
            if (!empty($exclude_products)) {
                $args['exclude'] = $exclude_products;
            }
			
            if (!empty($exp_stock_status)) {
                $args['stock_status'] = $exp_stock_status;
            }
			
            // Export all language products if WPML is active and the language selected is all.
            if ( function_exists('icl_object_id') && isset( $_SERVER["HTTP_REFERER"] ) && strpos($_SERVER["HTTP_REFERER"], 'lang=all') !== false ) {
                     $args['suppress_filters'] = true;
            }
			
            $args['exclude_discarded'] = '_wt_feed_discard'; // To exclude individual excluded from product fetching.
            
            $args = apply_filters("woocommerce_csv_product_export_args", $args);

            $products = wc_get_products($args); 

            $total_products=0;
            if( 0 == $batch_offset ) //first batch
            {
                $total_item_args=$args;
                $total_item_args['limit']=$export_limit; //user given limit
                $total_item_args['offset']=$current_offset; //user given offset
                $total_products_count = wc_get_products($total_item_args);
                $total_products=count($total_products_count->products);
            }


            $products_ids = $products->products;

            // If include category is selected and variable products are under those category, the variations will not be returned by the WC query
            if (!empty($prod_inc_categories)) {
                    $temp_prod_ids = $products_ids;
                    foreach ($temp_prod_ids as $key => $product_id) {
                            $product = wc_get_product($product_id);
                            if ($product->is_type('variable')) {
                                    $variations = $product->get_available_variations();
                                    $variations_ids = wp_list_pluck($variations, 'variation_id');
                                    foreach ($variations_ids as $variations_id){
                                            $products_ids[] = $variations_id;
                                    }
                            }
                    }
            }
            foreach ($products_ids as $key => $product_id) {  
                $product = wc_get_product($product_id); 

                // Skip variations that belongs to a specific categories that is excluded in filter
                if ($product->is_type('variation') && !empty($prod_exc_categories)) {

                    $parent_id = $product->get_parent_id();
                    if( has_term( $prod_exc_categories, 'product_cat', $parent_id ) ){
                            continue;
                    }

                }
                
                if( $product->is_type( 'variation' ) ){
                    $parent_id = $product->get_parent_id();
                    $parent_post = get_post( $parent_id );
                    if( !is_object( $parent_post ) || ( is_object( $parent_post ) && ( 'draft' == $parent_post->post_status || 'private' == $parent_post->post_status || 'pending' == $parent_post->post_status ) ) ){
                        continue;
                    }
                }                

                $this->parent_product = $product;
                $this->current_product_id = $product->get_id();
                $this->product = $product;

                if ($product->is_type('variable')) {
                        continue;
                }

                $product_array[] = $this->generate_row_data_wc_lower($product);
                if (($product->is_type('variable') || $product->has_child()) ) {
                    $children_ids = $product->get_children();
                    if (!empty($children_ids)) {
                        foreach ($children_ids as $id) {                                
                            if(!in_array($id, $products_ids)){  // skipping if alredy processed in $products_ids                                                                                                                               
                                $variation = wc_get_product($id);  
                                $this->parent_product = $product;
                                $this->product = $variation;
                                                                    $this->current_product_id = $variation->get_id();
                                if(is_object($variation)){
                                    $product_array[] = $this->generate_row_data_wc_lower($variation);
                                }

                            }
                        }
                    }                        
                }


            }
            
        }         

        $return_products =  array(
            'total' => $total_products,
            'data' => $product_array,
        );
		if( 0 == $batch_offset && 0 == $total_products ){
				$return_products['no_post'] = __( 'Nothing to export under the selected criteria. Please try adjusting the filters.' );
		}
		return $return_products;

    }        

    protected function generate_row_data_wc_lower($product_object) {

        $export_columns = $this->parent_module->get_selected_column_names();

	$product_id = $product_object->get_id();        

        $csv_columns = $export_columns;

        $export_columns = !empty($csv_columns) ? $csv_columns : array();
        
        $row = array();

        foreach ($export_columns as $key => $value) {
            if (method_exists($this, $value)) {
                $row[$key] = $this->$value($key, $value, $export_columns);
            }elseif (strpos($value, 'meta:') !== false) {
                $mkey = str_replace('meta:', '', $value);
				if($product_object->is_type('variation')){
					$product_id = $product_object->get_parent_id();
				}
				if ( (strpos($value, 'global_unique_id') !== false) || (strpos($value, 'alg_ean') !== false) ){
					$product_id = $product_object->get_id();
				}
				$row[$key] = wp_strip_all_tags( get_post_meta($product_id, $mkey, true) );
                // TODO
                // wt_image_ function can be replaced with key exist check
            }elseif (strpos($value, 'wt_pf_pa_') !== false) {
                $atr_key = str_replace('wt_pf_pa_', '', $value);
                if($product_object->is_type('variation')){
                    $product_object = wc_get_product($product_object->get_parent_id());
                }
                $value = '';
                if(is_object($product_object)){
                    $value = $product_object->get_attribute( $atr_key );
                }
                if ( ! empty( $value ) ) {
                            $value = trim( $value );
                    }
                $row[$key] = $value;
            }elseif (strpos($value, 'wt_pf_cattr_') !== false) {
                $atr_key = str_replace('wt_pf_cattr_', '', $value);
                if($product_object->is_type('variation')){
                    $product_object = wc_get_product($product_object->get_parent_id());
                }
                $value = '';
                if(is_object($product_object)){
                    $value = $product_object->get_attribute( $atr_key );

                }
                if ( ! empty( $value ) ) {
                            $value = trim( $value );
                            $value = str_replace('|', ',', $value);
                    }
                $row[$key] = $value;
            } elseif (strpos($value, 'wt_static_map_vl:') !== false) {
                $static_feed_value = str_replace('wt_static_map_vl:', '', $value);
                $row[$key] = $static_feed_value;
            }else {
                $row[$key] = '';
            }
        }

        return apply_filters("wt_batch_product_export_row_data_{$this->parent_module->module_base}", $row, $product_object);
    }

	
	/**
	 * Get product id.
	 *
	 * @return mixed|void
	 */
	public function id($catalog_attr, $product_attr, $export_columns) {
					
		$product_id = $this->product->get_id();
		$fb_retailer_id = $this->product->get_sku() ? $this->product->get_sku() . '_' . $product_id : 'wc_post_id_' . $product_id;
					
		$fb_retailer_id = apply_filters( 'wt_feed_filter_product_id', $fb_retailer_id, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_id", $fb_retailer_id, $this->product, $this->form_data );
                
	}	
	
	/**
	 * Get parent product title for variation.
	 *
	 * @return mixed|void
	 */
	public function parent_title($catalog_attr, $product_attr, $export_columns) {
            $title = '';
            if ($this->product->is_type('variation')) {
                $parent_product = wc_get_product($this->product->get_parent_id());
                if (is_object($parent_product)) {
                    $title = $parent_product->get_name();
                }
            } else {
                $title = $this->product->get_name();
            }
		
		$title = apply_filters( 'wt_feed_filter_product_parent_title', $title, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_parent_title", $title, $this->product, $this->form_data );
	}
	

	/**
	 * Get product description with HTML tags.
	 *
	 * @return mixed|void
	 */
	public function description_with_html($catalog_attr, $product_attr, $export_columns) {
		$description = $this->product->get_description();
		
		// Get Variation Description
		if ( empty( $description ) && $this->product->is_type( 'variation' ) ) {
			$description = '';
			if ( ! is_null( $this->parent_product ) ) {
				$description = $this->parent_product->get_description();
			}
		}
		
		if ( empty( $description ) ) {
			$description = $this->product->get_short_description();
		}
		
		//$description = CommonHelper::remove_shortcodes( $description );
		
		// Add variations attributes after description to prevent Facebook error
		if (  $this->product->is_type( 'variation' ) ) {
			$variationInfo = explode( '-', $this->product->get_name() );
			if ( isset( $variationInfo[1] ) ) {
				$extension = $variationInfo[1];
			} else {
				$extension = $this->product->get_id();
			}
			$description .= ' ' . $extension;
		}
		
		//remove spacial characters
		$description = wp_check_invalid_utf8( wp_specialchars_decode( $description ), true );
		
		$description = apply_filters( 'wt_feed_filter_product_description_with_html', $description, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_description_with_html", $description, $this->product, $this->form_data );
	}
        
	
	/**
	 * Get product short description.
	 *
	 * @return mixed|void
	 */
	public function short_description($catalog_attr, $product_attr, $export_columns) {
		$short_description = $this->product->get_short_description();
		
		// Get Variation Short Description
		if ( empty( $short_description ) && $this->product->is_type( 'variation' ) ) {
                    $parent_product = wc_get_product( $this->product->get_parent_id() );
                    if(is_object($parent_product)){
                        $short_description = $parent_product->get_short_description();
                    }
		}		
		
		// Strip tags and special characters
		$short_description = strip_tags( $short_description );
		
		$short_description = apply_filters( 'wt_feed_filter_product_short_description', $short_description, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_short_description", $short_description, $this->product, $this->form_data );
	}	
	
	/**
	 * Get product full category.
	 *
	 * @return mixed|void
	 */
	public function product_full_cat($catalog_attr, $product_attr, $export_columns) {
		
		$id = $this->product->get_id();
		if ( $this->product->is_type( 'variation' ) ) {
			$id = $this->product->get_parent_id();
		}
		
		$separator = apply_filters( 'wt_feed_product_type_separator', ' > ', $this->product );
		
		$product_type = wp_strip_all_tags( wc_get_product_category_list( $id, $separator ) );		
		
		$product_type = apply_filters( 'wt_feed_filter_product_local_category', $product_type, $this->product );
                
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_local_category", $product_type, $this->product, $this->form_data );
	}
	
	/**
	 * Get product URL.
	 *
	 * @return mixed|void
	 */
	public function link($catalog_attr, $product_attr, $export_columns) {
		$link = $this->product->get_permalink();
		
		$link = apply_filters( 'wt_feed_filter_product_link', $link, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_link", $link, $this->product, $this->form_data );
	}
	
	/**
	 * Get product parent URL.
	 *
	 * @return mixed|void
	 */
	public function parent_link($catalog_attr, $product_attr, $export_columns) {
		$link = $this->product->get_permalink();
		if ( $this->product->is_type( 'variation' ) ) {
			$link = $this->parent_product->get_permalink();
		}
				
		$link = apply_filters( 'wt_feed_filter_product_parent_link', $link, $this->product);
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_parent_link", $link, $this->product, $this->form_data );
	}
	
	/**
	 * Get product Canonical URL.
	 *
	 * @return mixed|void
	 */
	public function canonical_link($catalog_attr, $product_attr, $export_columns) {
		//TODO: check if SEO plugin installed then return SEO canonical URL
		$canonical_link = $this->parent_link();
				
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_canonical_link", $canonical_link, $this->product, $this->form_data );
	}
	
	/**
	 * Get external product URL.
	 *
	 * @return mixed|void
	 */
	public function ex_link($catalog_attr, $product_attr, $export_columns) {
		$ex_link = '';
		if ( $this->product->is_type( 'external' ) ) {
			$ex_link = $this->product->get_product_url();
		}
		
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_ex_link", $ex_link, $this->product, $this->form_data );
	}
	
	
	

	/**
	 * Get Formatted URL
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function wt_feed_get_formatted_url( $url = '' ) {
		if ( ! empty( $url ) ) {
			if ( substr( trim( $url ), 0, 4 ) === 'http' || substr( trim( $url ),
					0,
					3 ) === 'ftp' || substr( trim( $url ), 0, 4 ) === 'sftp' ) {
				return rtrim( $url, '/' );
			} else {
				$base = get_site_url();
				$url  = $base . $url;

				return rtrim( $url, '/' );
			}
		}

		return '';
	}

	
	/**
	 * Get product image URL.
	 *
	 * @return mixed|void
	 */
	public function image_link($catalog_attr, $product_attr, $export_columns) {
		$image = '';
		if ( $this->product->is_type( 'variation' ) ) {
			// Variation product type
			if ( has_post_thumbnail( $this->product->get_id() ) ) {
				$getImage = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_id() ), 'single-post-thumbnail' );
				$image    = self::wt_feed_get_formatted_url( $getImage[0] );
			} elseif ( has_post_thumbnail( $this->product->get_parent_id() ) ) {
				$getImage = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_parent_id() ), 'single-post-thumbnail' );
				$image    = self::wt_feed_get_formatted_url( $getImage[0] );
			}
		} elseif ( has_post_thumbnail( $this->product->get_id() ) ) { // All product type except variation
			$getImage = wp_get_attachment_image_src( get_post_thumbnail_id( $this->product->get_id() ), 'single-post-thumbnail' );
			$image    = isset( $getImage[0] ) ? self::wt_feed_get_formatted_url( $getImage[0] ) : '';
		}
		if( '' === $image){
			$image = 'https://via.placeholder.com/300';
		}
		
		$image = apply_filters( 'wt_feed_filter_product_image', $image, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_image", $image, $this->product, $this->form_data );
                
	}
	
	/**
	 * Get product featured image URL.
	 *
	 * @return mixed|void
	 */
	public function feature_image($catalog_attr, $product_attr, $export_columns) {
		
		
		$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
		
		$getImage = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'single-post-thumbnail' );
		$image    = isset( $getImage[0] ) ? self::wt_feed_get_formatted_url( $getImage[0] ) : '';
		
		$image = apply_filters( 'wt_feed_filter_product_feature_image', $image, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_feature_image", $image, $this->product, $this->form_data );
	}
	
	public static function get_product_gallery( $product ) {
		$imgUrls       = [];
		$attachmentIds = [];
		
		if ( $product->is_type( 'variation' ) ) {
		
				/**
				 * If any Variation Gallery Image plugin not installed then get Variable Product Additional Image Ids .
				 */
			$parent_prod = wc_get_product( $product->get_parent_id() );
			if( is_object( $parent_prod ) ){
				$attachmentIds = $parent_prod->get_gallery_image_ids();
			}
			
		}
		
		/**
		 * Get Variable Product Gallery Image ids if Product is not a variation
		 * or variation does not have any gallery images
		 */
		if ( empty( $attachmentIds ) ) {
			$attachmentIds = $product->get_gallery_image_ids();
		}
		
		if ( $attachmentIds && is_array( $attachmentIds ) ) {
			$mKey = 1;
			foreach ( $attachmentIds as $attachmentId ) {
				$imgUrls[ $mKey ] = Wt_Pf_IE_Basic_Helper::feed_get_formatted_url( wp_get_attachment_url( $attachmentId ) );
				$mKey ++;
			}
		}
		
		return $imgUrls;
	}
	
	
	
		/**
	 * Get product images (comma separated URLs).
	 *
	 * @return mixed|void
	 */
	public function additional_image_link( $catalog_attr, $product_attr, $export_columns, $additionalImg = '' ) {
		$imgUrls   = self::get_product_gallery( $this->product );
		$separator = apply_filters( 'wt_feed_filter_category_separator', ' | ', $this->product );
		
		// Return Specific Additional Image URL
		if ( '' !== $additionalImg ) {
			if ( array_key_exists( $additionalImg, $imgUrls ) ) {
				$images = $imgUrls[ $additionalImg ];
			} else {
				$images = '';
			}
		} else {
			
			$images = implode( $separator, array_filter( $imgUrls ) );
		}
		
		$images = apply_filters( 'wt_feed_filter_product_images', $images, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_feature_image", $images, $this->product, $this->form_data );
	}
	
	/**
	 * Get product images (comma separated URLs).
	 *
	 * @return mixed|void
	 */
	public function images( $catalog_attr, $product_attr, $export_columns, $additionalImg = '' ) {
		$imgUrls   = self::get_product_gallery( $this->product );
		$separator = apply_filters( 'wt_feed_filter_category_separator', ' > ', $this->product );
		
		// Return Specific Additional Image URL
		if ( '' !== $additionalImg ) {
			if ( array_key_exists( $additionalImg, $imgUrls ) ) {
				$images = $imgUrls[ $additionalImg ];
			} else {
				$images = '';
			}
		} else {
			
			$images = implode( $separator, array_filter( $imgUrls ) );
		}
		
		$images = apply_filters( 'wt_feed_filter_product_images', $images, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_images", $images, $this->product, $this->form_data, $additionalImg );
	}
	public function wtimages_1($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns, 1);
	}
	public function wtimages_2($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns, 2);
	}
		public function wtimages_3($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,3);
	}
		public function wtimages_4($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,4);
	}
		public function wtimages_5($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,5);
	}
		public function wtimages_6($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,6);
	}
		public function wtimages_7($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,7);
	}
		public function wtimages_8($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,8);
	}
		public function wtimages_9($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,9);
	}
	public function wtimages_10($catalog_attr, $product_attr, $export_columns){
		return $this->images($catalog_attr, $product_attr, $export_columns,10);
	}
	
	
		
	public function identifier_exists($catalog_attr, $product_attr, $export_columns){
		
		$identifier_exists = 'no';
		if(isset($export_columns['sku']) || isset($export_columns['brand'] )){
			$identifier_exists = 'yes';
		}
		$identifier_exists = apply_filters( 'wt_feed_product_identifier_exists', $identifier_exists, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_identifier_exists", $identifier_exists, $this->product, $this->form_data );
	}
	
	public function type($catalog_attr, $product_attr, $export_columns) {
		$ptype = apply_filters( 'wt_feed_filter_product_type', $this->product->get_type(), $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_type", $ptype, $this->product, $this->form_data );
	}
	
	public function is_bundle($catalog_attr, $product_attr, $export_columns) {

                $is_bundle = 'no';
                if ($this->product->is_type('bundle') || $this->product->is_type('yith_bundle') || $this->product->is_type('bopobb') ) {
                    $is_bundle = 'yes';
                }		
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_is_bundle", $is_bundle, $this->product, $this->form_data );
	}
	
	public function multipack($catalog_attr, $product_attr, $export_columns) {
		$multi_pack = '';
		if ( $this->product->is_type( 'grouped' ) ) {
			$multi_pack = ( ! empty( $this->product->get_children() ) ) ? count( $this->product->get_children() ) : '';
		}
				
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_is_multipack", $multi_pack, $this->product, $this->form_data );
	}
	
	public function visibility($catalog_attr, $product_attr, $export_columns) {
		/*
		 * "active", "archived", "staging", "published", "hidden", "visible_only_with_overrides", "whitelist_only"
		 */
		$visibility =  $this->product->get_catalog_visibility();
		$visibility = ('visible' === $visibility) ? 'active' : 'staging';
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_visibility", $visibility, $this->product, $this->form_data);
	}
	
	public function rating_total($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_rating_total", $this->product->get_rating_count(), $this->product, $this->form_data );
	}
	
	public function rating_average($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_rating_average", $this->product->get_average_rating(), $this->product, $this->form_data );
	}
	
	public function fb_product_category($catalog_attr, $product_attr, $export_columns) {

            $custom_fb_category = get_post_meta($this->current_product_id, '_wt_facebook_fb_product_category', true);

            if ( !$custom_fb_category ) {

                $product_id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
                $category_path = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));

                $fb_product_category = [];
                foreach ($category_path as $category) {
                    $fb_category_id = get_term_meta($category->term_id, 'wt_fb_category', true);
                    if ($fb_category_id) {

                        $fb_category_list = wp_cache_get('wt_fbfeed_fb_product_categories_array');

                        if (false === $fb_category_list) {
                            $fb_category_list = Webtoffee_Product_Feed_Sync_Facebook::get_category_array();
                            wp_cache_set('wt_fbfeed_fb_product_categories_array', $fb_category_list, '', WEEK_IN_SECONDS);
                        }


                        $fb_category = isset($fb_category_list[$fb_category_id]) ? $fb_category_list[$fb_category_id] : '';
                        if ('' !== $fb_category) {
                            $fb_product_category[] = $fb_category;
                        }
                    }
                }

                $fb_product_category = empty($fb_product_category) ? '' : implode(', ', $fb_product_category);
            } else {

                $fb_category_list = wp_cache_get('wt_fbfeed_fb_product_categories_array');

                if (false === $fb_category_list) {
                    $fb_category_list = Webtoffee_Product_Feed_Sync_Facebook::get_category_array();
                    wp_cache_set('wt_fbfeed_fb_product_categories_array', $fb_category_list, '', WEEK_IN_SECONDS);
                }

                $fb_product_category = $fb_category_list[$custom_fb_category];
            }

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_fb_category", $fb_product_category, $this->product, $this->form_data);
        }
	
        
        public function google_product_category($catalog_attr, $product_attr, $export_columns){
		
		
		
			$category_path = wp_get_post_terms( $this->product->get_id(), 'product_cat', array( 'fields' => 'all' ) );

			$fb_product_category = [];
			foreach ( $category_path as $category ) {
				$fb_category_id = get_term_meta( $category->term_id, 'wt_google_category', true );
				if ( $fb_category_id ) {
					
					$fb_category_list = wp_cache_get( 'wt_fbfeed_google_product_categories_array' );
					
					if ( false === $fb_category_list ) {
						$fb_category_list			 = Webtoffee_Product_Feed_Sync_Google::get_category_array( );
						wp_cache_set( 'wt_fbfeed_google_product_categories_array', $fb_category_list, '', WEEK_IN_SECONDS );
					}
					
					
					$fb_category = isset($fb_category_list[$fb_category_id]) ? $fb_category_list[$fb_category_id] : '';
					if('' !== $fb_category){
						$fb_product_category[]	 = $fb_category;
					}
				}
			}

			
			$fb_product_category = empty( $fb_product_category ) ? '' : implode( ', ', $fb_product_category );

			return apply_filters("wt_feed_{$this->parent_module->module_base}_product_google_category", $fb_product_category, $this->product, $this->form_data);
			
		
		
	}        
        
	public function total_sold($catalog_attr, $product_attr, $export_columns) {
		
		// Todo
		
	}
	
	public function tags($catalog_attr, $product_attr, $export_columns) {

		$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
		
		/**
		 * Separator for multiple tags
		 *
		 * @param string                     $separator
		 * @param array                      $config
		 * @param WC_Abstract_Legacy_Product $product
		 *
		 * @since 2.0.0
		 */
		$separator = apply_filters( 'wt_feed_tags_separator', ',', $this->product );
		
		$tags = strip_tags( get_the_term_list( $id, 'product_tag', '', $separator, '' ) );
		
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_tags", $tags, $this->product, $this->form_data );
	}
	
	public function item_group_id($catalog_attr, $product_attr, $export_columns) {

		$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
		
		$id = apply_filters( 'wt_feed_filter_product_item_group_id', $id, $this->product );
                
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_item_group_id", $id, $this->product, $this->form_data );
	}
	
	public function sku($catalog_attr, $product_attr, $export_columns) {
		$sku = apply_filters( 'wt_feed_filter_product_sku', $this->product->get_sku(), $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_sku", $sku, $this->product, $this->form_data );
	}
	
	public function sku_id($catalog_attr, $product_attr, $export_columns) {
				

		$id = ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() );
		
		$sku    = ! empty( $this->product->get_sku() ) ? $this->product->get_sku() . '_' : '';
		$sku_id = $sku . $id;
		
		$sku_id = apply_filters( 'wt_feed_filter_product_sku_id', $sku_id, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_sku_id", $sku_id, $this->product, $this->form_data );
	}
	
	public function brand($catalog_attr, $product_attr, $export_columns) {

                $id = ( $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id() );
		$custom_brand = get_post_meta($this->current_product_id, '_wt_feed_brand', true);
		
                if( !$custom_brand ){
                    $custom_brand = get_post_meta($this->current_product_id, '_wt_facebook_brand', true);
                }
                
		if( !$custom_brand ){
		
		$brand	 = get_the_term_list( $id, 'product_brand', '', ', ' );
                
                $has_brand = true;
                if( is_wp_error($brand) || false === $brand ){
                    $has_brand = false;
                }

                if( !$has_brand && is_plugin_active('perfect-woocommerce-brands/perfect-woocommerce-brands.php')){
                    $brand	 = get_the_term_list( $id, 'pwb-brand', '', ', ' );
                }
                
		$string	 = is_wp_error( $brand ) || !$brand ? wp_strip_all_tags( self::get_store_name() ) : self::clean_string( $brand );
		$length = 100;
			if ( extension_loaded( 'mbstring' ) ) {

				if ( mb_strlen( $string, 'UTF-8' ) <= $length ) {
					return apply_filters( 'wt_feed_filter_product_brand', $string, $this->product );
				}

				$length -= mb_strlen( '...', 'UTF-8' );

				$brand_string = mb_substr( $string, 0, $length, 'UTF-8' ) . '...';
				return apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
			} else {

				$string	 = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );
				$string	 = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

				if ( strlen( $string ) <= $length ) {
					return apply_filters( 'wt_feed_filter_product_brand', $string, $this->product );
				}

				$length -= strlen( '...' );

				$brand_string = substr( $string, 0, $length ) . '...';
				$brand_string = apply_filters( 'wt_feed_filter_product_brand', $brand_string, $this->product );
                                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_brand", $brand_string, $this->product, $this->form_data );
			}
		}else{
			$custom_brand = apply_filters( 'wt_feed_filter_product_brand', $custom_brand, $this->product );
                        return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_brand", $custom_brand, $this->product, $this->form_data );
		}	
		
	}
	
	
	public function age_group($catalog_attr, $product_attr, $export_columns) {

			$age_group = get_post_meta($this->product->get_id(), '_wt_feed_agegroup', true);
                        if( '' == $age_group ){
                            $age_group = get_post_meta($this->product->get_id(), '_wt_facebook_agegroup', true);
                        }
			return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_age_group", $age_group, $this->product, $this->form_data);
	}
        
        public function gender($catalog_attr, $product_attr, $export_columns) {

            $gender = get_post_meta($this->product->get_id(), '_wt_feed_gender', true);
            if (!$gender) {
                $gender = get_post_meta($this->product->get_id(), '_wt_facebook_gender', true);
            }
            if ( !$gender && $this->product->is_type('variation')) {

                $attributes = $this->product->get_variation_attributes();

                if (!$attributes) {
                    return apply_filters('wt_feed_facebook_product_gender', $gender, $this->product);
                }

                $variant_names = array_keys($attributes);

                foreach ($variant_names as $original_variant_name) {

                    $label = wc_attribute_label($original_variant_name, $this->product);

                    $new_name = str_replace('custom_data:', '', self::sanitize_variant_name($original_variant_name));
                    if ('gender' === $new_name || 'geschlecht' === $new_name || 'Geschlecht' === $new_name) {
                        if ($options = $this->get_variant_option_name($this->product->get_id(), $label, $attributes[$original_variant_name])) {

                            if (is_array($options)) {

                                $option_values = array_values($options);
                            } else {

                                $option_values = [$options];

                                if (count($option_values) === 1 && empty($option_values[0])) {
                                    $option_values[0] = 'any';
                                }
                            }

                            switch ($new_name) {

                                case 'gender':
                                case 'geschlecht':
                                case 'Geschlecht':
                                    $gender = $option_values[0];

                                    break;

                                default:
                                    break;
                            }
                        }
                    }
                }
                if ('' == $gender) {
                    $parent = wc_get_product($this->product->get_parent_id());
                    $product_attributes = $parent->get_attributes();

                    if (isset($product_attributes['gender'])) {
                        $gender = $product_attributes['gender']['options']['0'];
                    }
                    if (isset($product_attributes['geschlecht'])) {
                        $gender = $product_attributes['geschlecht']['options']['0'];
                    }
                    if (isset($product_attributes['Geschlecht'])) {
                        $gender = $product_attributes['Geschlecht']['options']['0'];
                    }
                }
                $gender = apply_filters('wt_feed_facebook_product_gender', $gender, $this->product);
                
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_productgender", $gender, $this->product, $this->form_data);
                
            } elseif ('' == $gender) {
                $product_attributes = $this->product->get_attributes();
                if (isset($product_attributes['gender'])) {
                    $gender = $product_attributes['gender']['options']['0'];
                }
                if (isset($product_attributes['geschlecht'])) {
                    $gender = $product_attributes['geschlecht']['options']['0'];
                }
                if (isset($product_attributes['Geschlecht'])) {
                    $gender = $product_attributes['Geschlecht']['options']['0'];
                }
            }
            $gender = apply_filters('wt_feed_facebook_product_gender', $gender, $this->product);
            return apply_filters( "wt_feed_{$this->parent_module->module_base}_productgender", $gender, $this->product, $this->form_data);
        }
        
        public function size($catalog_attr, $product_attr, $export_columns) {

            $size = get_post_meta($this->product->get_id(), '_wt_feed_size', true);
            if ( !$size) {
                $size = get_post_meta($this->product->get_id(), '_wt_facebook_size', true);
            }
            if ( !$size && $this->product->is_type('variation')) {


                $attributes = $this->product->get_variation_attributes();

                if (!$attributes) {
                    return apply_filters('wt_feed_facebook_product_size', $size, $this->product);
                }

                $variant_names = array_keys($attributes);

                foreach ($variant_names as $original_variant_name) {

                    $label = wc_attribute_label($original_variant_name, $this->product);

                    $new_name = str_replace('custom_data:', '', self::sanitize_variant_name($original_variant_name));
                    if ('size' === $new_name || 'größe' === $new_name || 'grose' === $new_name || 'groesse' === $new_name || 'groessen' === $new_name) {
                        if ($options = $this->get_variant_option_name($this->product->get_id(), $label, $attributes[$original_variant_name])) {

                            if (is_array($options)) {

                                $option_values = array_values($options);
                            } else {

                                $option_values = [$options];

                                if (count($option_values) === 1 && empty($option_values[0])) {
                                    $option_values[0] = 'any';
                                }
                            }
                            switch ($new_name) {

                                case 'size':
                                case 'grose':
                                case 'groesse':
                                case 'größe':
                                case 'groessen':
                                    $size = $option_values[0];
                                    break;

                                default:
                                    break;
                            }
                        }
                    }
                }
                if ('' == $size) {
                    $parent = wc_get_product($this->product->get_parent_id());
                    $product_attributes = $parent->get_attributes();
                    if (isset($product_attributes['size'])) {
                        $size = $product_attributes['size']['options']['0'];
                    }
                    if (isset($product_attributes['größe'])) {
                        $size = $product_attributes['größe']['options']['0'];
                    }
                    if (isset($product_attributes['groesse'])) {
                        $size = $product_attributes['groesse']['options']['0'];
                    }
                    if (isset($product_attributes['groessen'])) {
                        $size = $product_attributes['groessen']['options']['0'];
                    }
                }
                $size = apply_filters('wt_feed_facebook_product_size', $size, $this->product);
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_productsize", $size, $this->product, $this->form_data);
            } elseif ('' == $size) {
                $product_attributes = $this->product->get_attributes();
                if (isset($product_attributes['size'])) {
                    $size = $product_attributes['size']['options']['0'];
                }
                if (isset($product_attributes['größe'])) {
                    $size = $product_attributes['größe']['options']['0'];
                }
                if (isset($product_attributes['groesse'])) {
                    $size = $product_attributes['groesse']['options']['0'];
                }
                if (isset($product_attributes['groessen'])) {
                    $size = $product_attributes['groessen']['options']['0'];
                }
            }
            $size = apply_filters('wt_feed_facebook_product_size', $size, $this->product);
            return apply_filters( "wt_feed_{$this->parent_module->module_base}_productsize", $size, $this->product, $this->form_data);
        }
        
	public function color($catalog_attr, $product_attr, $export_columns) {

            $color = get_post_meta($this->product->get_id(), '_wt_feed_color', true);
            if ( !$color ) {
                $color = get_post_meta($this->product->get_id(), '_wt_facebook_color', true);
            }
            if ( !$color && $this->product->is_type('variation') ) {


                $attributes = $this->product->get_variation_attributes();

                if (!$attributes) {
                    return apply_filters('wt_feed_facebook_product_color', $color, $this->product);
                }

                $variant_names = array_keys($attributes);

                foreach ($variant_names as $original_variant_name) {

                    $label = wc_attribute_label($original_variant_name, $this->product);

                    $new_name = str_replace('custom_data:', '', self::sanitize_variant_name($original_variant_name));
                    if ('color' === $new_name || 'farbe' === $new_name || 'farben' === $new_name) {
                        if ($options = $this->get_variant_option_name($this->product->get_id(), $label, $attributes[$original_variant_name])) {

                            if (is_array($options)) {
                                $option_values = array_values($options);
                            } else {

                                $option_values = [$options];

                                if (count($option_values) === 1 && empty($option_values[0])) {
                                    $option_values[0] = 'any';
                                }
                            }

                            switch ($new_name) {

                                case 'color':
                                case 'farbe':
                                case 'farben':
                                    $color = $option_values[0];
                                    break;

                                default:
                                    break;
                            }
                        }
                    }
                }
                if ('' == $color) {
                    $parent = wc_get_product($this->product->get_parent_id());
                    $product_attributes = is_object($parent) ? $parent->get_attributes() : array();
                    if (isset($product_attributes['color'])) {
                        $color = $product_attributes['color']['options']['0'];
                    }
                    if (isset($product_attributes['farbe'])) {
                        $color = $product_attributes['farbe']['options']['0'];
                    }
                    if (isset($product_attributes['farben'])) {
                        $color = $product_attributes['farben']['options']['0'];
                    }
                    if (isset($product_attributes['Farbe'])) {
                        $color = $product_attributes['Farbe']['options']['0'];
                    }
                }
                $color = apply_filters('wt_feed_facebook_product_color', $color, $this->product);
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_productcolor", $color, $this->product, $this->form_data);
            } elseif ('' == $color) {
                $product_attributes = $this->product->get_attributes();
                if (isset($product_attributes['color'])) {
                    $color = $product_attributes['color']['options']['0'];
                }
                if (isset($product_attributes['farbe'])) {
                    $color = $product_attributes['farbe']['options']['0'];
                }
                if (isset($product_attributes['farben'])) {
                    $color = $product_attributes['farben']['options']['0'];
                }
                if (isset($product_attributes['Farbe'])) {
                    $color = $product_attributes['Farbe']['options']['0'];
                }
            }
            $color = apply_filters('wt_feed_facebook_product_color', $color, $this->product);
            return apply_filters( "wt_feed_{$this->parent_module->module_base}_productcolor", $color, $this->product, $this->form_data);
        }
        
        public function get_variant_option_name( $product_id, $label, $default_value ) {

			$meta			 = get_post_meta( $product_id, $label, true );
			$attribute_name	 = str_replace( 'attribute_', '', $label );
			$term			 = get_term_by( 'slug', $meta, $attribute_name );
			return ( $term && $term->name ) ? $term->name : $default_value;
		}        
		public static function sanitize_variant_name( $name ) {

			$name = str_replace( array( 'attribute_', 'pa_' ), '', strtolower( $name ) );

			if ( 'colour' === $name ) {
				$name = 'color';
			}

			switch ( $name ) {
				case 'size':
				case 'color':
				case 'gender':
				case 'pattern':
					break;
				default:
					$name = 'custom_data:' . strtolower( $name );
					break;
			}

			return $name;
		}         
	
        public function gtin($catalog_attr, $product_attr, $export_columns) {

            $custom_gtin = get_post_meta($this->product->get_id(), '_wt_feed_gtin', true);
            if ( !$custom_gtin ) {
                $custom_gtin = get_post_meta($this->product->get_id(), '_wt_facebook_gtin', true);
            }
            if(!$custom_gtin){
                $custom_gtin = get_post_meta($this->product->get_id(), '_global_unique_id', true);
            }
            $gtin = ('' == $custom_gtin) ? '' : $custom_gtin;
            $gtin = apply_filters('wt_feed_product_gtin', $gtin, $this->product);
            return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_gtin", $gtin, $this->product, $this->form_data );
        }
        public function mpn($catalog_attr, $product_attr, $export_columns) {

            $custom_mpn = get_post_meta($this->product->get_id(), '_wt_feed_mpn', true);
            if ( !$custom_mpn ) {
                $custom_mpn = get_post_meta($this->product->get_id(), '_wt_facebook_mpn', true);
            }
            $mpn = ( $custom_mpn ) ? $custom_mpn : '';
            $mpn = apply_filters('wt_feed_product_mpn', $mpn, $this->product);
            return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_mpn", $mpn, $this->product, $this->form_data );
        }                
                
	public static function get_store_name() {

			$url = get_bloginfo('name');
			return ( $url) ? ( $url ) : 'My Store';
		}

		/**
		 * Clean up strings for FB Graph POSTing.
		 * This function should will:
		 * 1. Replace newlines chars/nbsp with a real space
		 * 2. strip_tags()
		 * 3. trim()
		 *
		 * @access public
		 * @param String string
		 * @return string
		 */
		public static function clean_string($string) {
			$string = do_shortcode($string);
			$string = str_replace(array('&amp%3B', '&amp;'), '&', $string);
			$string = str_replace(array("\r", '&nbsp;', "\t"), ' ', $string);
			$string = wp_strip_all_tags($string, false); // true == remove line breaks
			return $string;
		}

	public function parent_sku($catalog_attr, $product_attr, $export_columns) {
            
		$parent_sku = $this->product->get_sku();
		if ( $this->product->is_type( 'variation' ) ) {
                        $parent_prod = wc_get_product( $this->product->get_parent_id() );
			if( is_object( $parent_prod ) ){
                            $parent_sku = $parent_prod->get_sku();
                        }                        
		}
		
		$parent_sku = apply_filters( 'wt_feed_filter_product_parent_sku', $parent_sku, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_parent_sku", $parent_sku, $this->product, $this->form_data );
	}
	
	public function availability($catalog_attr, $product_attr, $export_columns) {
		$status = $this->product->get_stock_status();
		if ( 'instock' === $status ) {
			$status = 'in stock';
		} elseif ( 'outofstock' === $status ) {
			$status = 'out of stock';
		} elseif ( 'onbackorder' === $status ) {
			$status = 'on backorder';
		}
		
		
		$status = apply_filters( 'wt_feed_filter_product_availability', $status, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_availability", $status, $this->product, $this->form_data );
	}
	
	public function availability_date($catalog_attr, $product_attr, $export_columns) {
		
		$availability_date = get_post_meta( $this->product->get_id(), '_wt_feed_availability_date', true );
		
		if ( $availability_date ) {
			$availability_date = gmdate( 'c', strtotime( $availability_date ) );
		}
		
		$availability_date = apply_filters( 'wt_feed_filter_product_availability_date', $availability_date, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_availability_date", $availability_date, $this->product, $this->form_data );
	}
	
	public function add_to_cart_link($catalog_attr, $product_attr, $export_columns) {
		$url    = $this->link();
		$suffix = 'add-to-cart=' . $this->product->get_id();
		
		$add_to_cart_link = wt_feed_make_url_with_parameter( $url, $suffix );
		
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_add_to_cart_link", $add_to_cart_link, $this->product, $this->form_data );
	}
	
	public function quantity($catalog_attr, $product_attr, $export_columns) {
		$quantity = $this->product->get_stock_quantity();
		$status   = $this->product->get_stock_status();
		
		//when product is outofstock , and it's quantity is empty, set quantity to 0
		if ( 'outofstock' === $status && $quantity === null ) {
			$quantity = 0;
		}
		
		if ( $this->product->is_type( 'variable' ) && $this->product->has_child() ) {
			$visible_children = $this->product->get_visible_children();
			$qty              = array();
			foreach ( $visible_children as $child ) {
				$childQty = get_post_meta( $child, '_stock', true );
				$qty[]    = (int) $childQty + 0;
			}
			
					$quantity = array_sum( $qty );
				
			
		}
		
		$quantity = apply_filters( 'wt_feed_filter_product_quantity', $quantity, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_quantity", $quantity, $this->product, $this->form_data );
	}
	        
        public function quantity_to_sell_on_facebook($catalog_attr, $product_attr, $export_columns) {
            $quantity = $this->product->get_stock_quantity();
            $status = $this->product->get_stock_status();

            //when product is outofstock , and it's quantity is empty, set quantity to 0
            if ('outofstock' === $status && $quantity === null) {
                $quantity = 0;
            }

            if ($this->product->is_type('variable') && $this->product->has_child()) {

                $visible_children = $this->product->get_visible_children();
                $qty = array();
                foreach ($visible_children as $child) {
                    $childQty = get_post_meta($child, '_stock', true);
                    $qty[] = (int) $childQty;
                }

                $quantity = array_sum($qty);
            }

            return apply_filters( "wt_feed_{$this->parent_module->module_base}_fb_product_quantity", $quantity, $this->product, $this->form_data);
        }
	/**
	 * Get Store Currency.
	 *
	 * @return mixed|void
	 */
	public function currency($catalog_attr, $product_attr, $export_columns) {
		$currency = get_option( 'woocommerce_currency' );
		
		$currency = apply_filters( 'wt_feed_filter_product_currency', $currency, $this->product );
                return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_currency", $currency, $this->product, $this->form_data);
	}
	
	/**
	 * Get Product Sale Price start date.
	 *
	 * @return mixed|void
	 */
	public function sale_price_sdate($catalog_attr, $product_attr, $export_columns) {
		$startDate = $this->product->get_date_on_sale_from();
		if ( is_object( $startDate ) ) {
			$sale_price_sdate = $startDate->date_i18n();
		} else {
			$sale_price_sdate = '';
		}
		
		return apply_filters( 'wt_feed_filter_product_sale_price_sdate', $sale_price_sdate, $this->product );
	}
	
	/**
	 * Get Product Sale Price End Date.
	 *
	 * @return mixed|void
	 */
	public function sale_price_edate($catalog_attr, $product_attr, $export_columns) {
		$endDate = $this->product->get_date_on_sale_to();
		if ( is_object( $endDate ) ) {
			$sale_price_edate = $endDate->date_i18n();
		} else {
			$sale_price_edate = "";
		}
		
		return apply_filters( 'wt_feed_filter_product_sale_price_edate', $sale_price_edate, $this->product );
	}
	
	public function price($catalog_attr, $product_attr, $export_columns) {
			$price = $this->product->get_regular_price();
			
			if ($this->product->is_type('variable') ) {
                            $price = $this->first_variation_price();
			}
            
			if ($price > 0) {
                                $price = wc_format_decimal($price, 2);
				$price = $price . ' ' . get_woocommerce_currency();
			}
			return apply_filters( 'wt_feed_filter_product_price', $price, $this->product );
		}

		public function current_price($catalog_attr, $product_attr, $export_columns) {
			$price = $this->product->get_price();
			if ($price > 0) {
                                $price = wc_format_decimal($price, 2);
				$price = $price . ' ' . get_woocommerce_currency();
			}
			return apply_filters( 'wt_feed_filter_product_current_price', $price, $this->product );
		}

		public function sale_price($catalog_attr, $product_attr, $export_columns) {
			$price = $this->product->get_sale_price();
			if ($price > 0) {
                                $price = wc_format_decimal($price, 2);
				$price = $price . ' ' . get_woocommerce_currency();
			}
			return apply_filters( 'wt_feed_filter_product_sale_price', $price, $this->product );
		}

		public function price_with_tax($catalog_attr, $product_attr, $export_columns) {
						
			$tprice = $this->product->get_regular_price();
			$price = wc_get_price_including_tax( $this->product, array('price' => $tprice) );
			if ($price > 0) {
                                $price = wc_format_decimal($price, 2);
				$price = $price . ' ' . get_woocommerce_currency();
			}
			return apply_filters( 'wt_feed_filter_product_price_with_tax', $price, $this->product );
		}

		public function current_price_with_tax($catalog_attr, $product_attr, $export_columns) {
			$cprice = $this->product->get_price();
			$price = wc_get_price_including_tax( $this->product, array('price' => $cprice) );			
			if ($price > 0) {
                                $price = wc_format_decimal($price, 2);
				$price = $price . ' ' . get_woocommerce_currency();
			}
			return apply_filters( 'wt_feed_filter_product_current_price_with_tax', $price, $this->product );
		}

		public function sale_price_with_tax($catalog_attr, $product_attr, $export_columns) {
			$sprice = $this->product->get_sale_price();
			$price = wc_get_price_including_tax( $this->product, array('price' => $sprice) );
			if ($price > 0) {
                                $price = wc_format_decimal($price, 2);
				$price = $price . ' ' . get_woocommerce_currency();
			}
			return apply_filters( 'wt_feed_filter_product_sale_price_with_tax', $price, $this->product );
		}

		
	public function first_variation_price() {

			$children = $this->product->get_visible_children();
			$price = $this->product->get_variation_price();
			if (isset($children[0]) && !empty($children[0])) {
				$variation = wc_get_product($children[0]);
				$price = $variation->get_price();
			}

			return apply_filters('wt_feed_filter_product_first_variation_price', $price, $this->product);
		}

		/**
	 * Get Product Weight.
	 *
	 * @return mixed|void
	 */
	public function weight($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_weight', $this->product->get_weight(), $this->product );
	}
	
	/**
	 * Get Weight Unit.
	 *
	 * @return mixed|void
	 */
	public function weight_unit($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_weight_unit', get_option( 'woocommerce_weight_unit' ), $this->product );
	}
	
	/**
	 * Get Product Width.
	 *
	 * @return mixed|void
	 */
	public function width($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_width', $this->product->get_width(), $this->product );
	}
	
	/**
	 * Get Product Height.
	 *
	 * @return mixed|void
	 */
	public function height($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_height', $this->product->get_height(), $this->product );
	}
	
	/**
	 * Get Product Length.
	 *
	 * @return mixed|void
	 */
	public function length($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_length', $this->product->get_length(), $this->product );
	}
	
	/** 
	 * Facebook Formatted Shipping info
	 * @param array $catalog_attr
	 * @param array $product_attr
	 * @param array $export_columns
	 * @param string $key
	 * @return string
	 *
	 */
	public function shipping( $catalog_attr, $product_attr, $export_columns, $key = '' ) {
		
		
		$shipping_details = $this->get_shipping();
		$shipping_details_xml = array();
		$shipping_str = '';
		if ( isset($shipping_details) && is_array($shipping_details) ) {
				foreach ( $shipping_details as $k => $shipping_item ) {
					
					unset($shipping_details['zone_name']);
                                        if( isset( $shipping_item['region']) && empty( $shipping_item['region'] ) ){
                                            unset( $shipping_item['region'] );
                                        }                                        
					$shipping_child = '';
					foreach ( $shipping_item as $shipping_item_attr => $shipping_value ) {
						
						if ( 'price' === $shipping_item_attr ) {
							$shipping_value  = number_format( $shipping_value, 2). ' ' . get_woocommerce_currency();
						}
						if( ( 'zone_name' !== $shipping_item_attr) ){
							$shipping_details_xml[$k][$shipping_item_attr] = $shipping_value;
						}
						if ( 'postal_code' !== $shipping_item_attr ) {
							$shipping_child  .= $shipping_value . ":";
						}
					}
					$shipping_child = trim( $shipping_child, ":" );

					//Add separator for multiple shipping method -  comma for facebook
					$shipping_str .= $shipping_child . ',';
				}

				$shipping_str = trim($shipping_str, ',');
			}

		if( isset($this->form_data['advanced_form_data']['wt_pf_file_as']) && 'xml' === $this->form_data['advanced_form_data']['wt_pf_file_as']){
			return apply_filters( 'wt_feed_facebook_product_shipping_xml', $shipping_details_xml, $shipping_details, $this->product );
		}	
		
		
		return apply_filters( 'wt_feed_facebook_product_shipping', $shipping_str, $shipping_details, $this->product );
	}
	
	
	public function shipping_data($catalog_attr, $product_attr, $export_columns){
		
		return $this->shipping($catalog_attr, $product_attr, $export_columns);
		
	}
	
	
	public function get_shipping() {

			$shpping_country = $this->form_data['post_type_form_data']['item_country'];
			$shipping_obj = new Webtoffee_Product_Feed_Basic_Shipping($this->product, 'facebook', $this->form_data);
			$shipping_info = $shipping_obj->get_shipping_by_location($shpping_country);

			return apply_filters("wt_feed_processed_shipping_infos", $shipping_info, $this->product);
		}
	
	
	
	/**
	 * Get Shipping Cost.
	 *
	 */
	public function shipping_cost($catalog_attr, $product_attr, $export_columns) {
		//Todo
	}
	
	
	/**
	 * Get Product Shipping Class
	 *
	 * @return mixed
	 * @since 2.0.0
	 *
	 */
	public function shipping_class($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_shipping_class', $this->product->get_shipping_class(), $this->product );
	}
	
	/**
	 * Get author name.
	 *
	 * @return string
	 */
	public function author_name($catalog_attr, $product_attr, $export_columns) {
		$post = get_post( $this->product->get_id() );
		
		return get_the_author_meta( 'user_login', $post->post_author );
	}
	
	/**
	 * Get Author Email.
	 *
	 * @return string
	 */
	public function author_email($catalog_attr, $product_attr, $export_columns) {
		$post = get_post( $this->product->get_id() );
		
		return get_the_author_meta( 'user_email', $post->post_author );
	}
	
	/**
	 * Get Date Created.
	 *
	 * @return mixed|void
	 */
	public function date_created($catalog_attr, $product_attr, $export_columns) {
		$date_created = gmdate( 'Y-m-d', strtotime( $this->product->get_date_created() ) );
		
		return apply_filters( 'wt_feed_filter_product_date_created', $date_created, $this->product );
	}
	
	/**
	 * Get Date updated.
	 *
	 * @return mixed|void
	 */
	public function date_updated($catalog_attr, $product_attr, $export_columns) {
		$date_updated = gmdate( 'Y-m-d', strtotime( $this->product->get_date_modified() ) );
		
		return apply_filters( 'wt_feed_filter_product_date_updated', $date_updated, $this->product );
	}
	
	/** Get Google Sale Price effective date.
	 *
	 * @return string
	 */
	public function sale_price_effective_date($catalog_attr, $product_attr, $export_columns) {
		$effective_date = '';
		$from           = $this->sale_price_sdate($catalog_attr, $product_attr, $export_columns);
		$to             = $this->sale_price_edate($catalog_attr, $product_attr, $export_columns);
		if ( ! empty( $from ) && ! empty( $to ) ) {
			$from = gmdate( 'c', strtotime( $from ) );
			$to   = gmdate( 'c', strtotime( $to ) );
			
			$effective_date = $from . '/' . $to;
		}
		
		return $effective_date;
	}
	
	
	public function tax_class($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_tax_class', $this->product->get_tax_class(), $this->product );
	}
	
	public function tax_status($catalog_attr, $product_attr, $export_columns) {
		return apply_filters( 'wt_feed_filter_product_tax_status', $this->product->get_tax_status(), $this->product );
	}
	

    /**
     * Format the data if required
     * @param  string $meta_value
     * @param  string $meta name of meta key
     * @return string
     */
    public static function format_export_meta($meta_value, $meta,$product_object=false) {
        switch ($meta) {
            case 'sale_price_dates_from' :
            case '_sale_price_dates_from' :
                if($product_object){
                    $sale_price_dates_from_timestamp = $product_object->get_date_on_sale_from( 'edit' ) ? $product_object->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() : false;                   
                    $sale_price_dates_from = $sale_price_dates_from_timestamp ? date_i18n( 'Y-m-d', $sale_price_dates_from_timestamp ) : '';
                    return $sale_price_dates_from;
                }                
                break;
            case 'sale_price_dates_to' :    
            case '_sale_price_dates_to' :
                if($product_object){
                    $sale_price_dates_to_timestamp   = $product_object->get_date_on_sale_to( 'edit' ) ? $product_object->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() : false;
                    $sale_price_dates_to   = $sale_price_dates_to_timestamp ? date_i18n( 'Y-m-d', $sale_price_dates_to_timestamp ) : '';
                    return $sale_price_dates_to;
                }
                break;
            case '_upsell_ids' :
            case '_crosssell_ids' :
            case 'upsell_ids' :
            case 'crosssell_ids' :                
                return implode('|', array_filter((array) json_decode($meta_value)));
                break;
            default :
                return $meta_value;
                break;
        }
    }
	public function fb_override($catalog_attr, $product_attr, $export_columns) {

		$override_country = $this->form_data['post_type_form_data']['item_country'];
		return apply_filters('wt_feed_filter_product_fb_override', $override_country, $this->product, $this->form_data);
	}

    public static function format_data($data) {
        if (!is_array($data))            
            $data = (string) urldecode($data);
//        $enc = mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);        
        $use_mb = function_exists('mb_detect_encoding');
        $enc = '';
        if ($use_mb) {
            $enc = mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);
        }
        $data = ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);

        return $data;
    }
	
	
}

}
