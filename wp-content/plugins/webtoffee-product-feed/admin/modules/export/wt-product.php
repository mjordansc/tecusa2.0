<?php

if (!defined('WPINC')) {
    exit;
}

if (!class_exists('Webtoffee_Product_Feed_Product')) {

    class Webtoffee_Product_Feed_Product {

        public $parent_product;
        public $current_product_id;
        public $product;

        public function __construct($product) {
            $this->parent_product = $product;
            $this->current_product_id = $product->get_id();
            $this->product = $product;
        }


        /**
         * Get product name.
         *
         * @return mixed|void
         */
        public function title($catalog_attr, $product_attr, $export_columns) {

            $title = $this->product->get_name();
            
            $title = apply_filters('wt_feed_filter_product_title', $title, $this->product, $this->form_data);
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_title", $title, $this->product, $this->form_data);
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

            $title =  apply_filters('wt_feed_filter_product_parent_title', $title, $this->product);
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_parent_title", $title, $this->product, $this->form_data);
        }

        /**
         * Get parent product description for variation.
         *
         * @return mixed|void
         */
        public function parent_description($catalog_attr, $product_attr, $export_columns) {
            $description = '';
            if ($this->product->is_type('variation')) {
                $parent_product = wc_get_product($this->product->get_parent_id());
                if (is_object($parent_product)) {
                    $description = $parent_product->get_description();
                }
            } else {
                $description = $this->product->get_name();
            }

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_parent_description", $description, $this->product);
        }        
        
        /**
         * Get product description.
         *
         * @return mixed|void
         */
        public function description($catalog_attr, $product_attr, $export_columns) {
            $description = $this->product->get_description();

            // Get Variation Description
            if ('' === $description && $this->product->is_type('variation')) {
                $description = '';
                $parent_product = wc_get_product($this->product->get_parent_id());
                if (is_object($parent_product)) {
                    $description = $parent_product->get_description();
                }
            }

            if ('' === $description) {
                $description = $this->product->get_short_description();
            }

            // Add variations attributes after description to prevent Google error
            if ($this->product->is_type('variation') && ( '' === $description )) {
                $variationInfo = explode('-', $this->product->get_name());
                if (isset($variationInfo[1])) {
                    $extension = $variationInfo[1];
                } else {
                    $extension = $this->product->get_id();
                }
                $description .= ' ' . $extension;
            }

            $do_shortcode = apply_filters("wt_feed_{$this->parent_module->module_base}_product_description_do_shortcode", true);
            
            if($do_shortcode){
                $description = do_shortcode($description);
            }            
            
            //strip tags and special characters
            $description = trim( strip_tags($description) );

            $description = apply_filters('wt_feed_filter_product_description', $description, $this->product);
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_description", $description, $this->product, $this->form_data);
        }

        /**
         * Get product description with HTML tags.
         *
         * @return mixed|void
         */
        public function description_with_html($catalog_attr, $product_attr, $export_columns) {
            $description = $this->product->get_description();

            // Get Variation Description
            if (empty($description) && $this->product->is_type('variation')) {
                $description = '';
                if (!is_null($this->parent_product)) {
                    $description = $this->parent_product->get_description();
                }
            }

            if (empty($description)) {
                $description = $this->product->get_short_description();
            }

            //$description = CommonHelper::remove_shortcodes( $description );
            // Add variations attributes after description to prevent Facebook error
            if ($this->product->is_type('variation')) {
                $variationInfo = explode('-', $this->product->get_name());
                if (isset($variationInfo[1])) {
                    $extension = $variationInfo[1];
                } else {
                    $extension = $this->product->get_id();
                }
                $description .= ' ' . $extension;
            }

            //remove spacial characters
            $description = wp_check_invalid_utf8(wp_specialchars_decode($description), true);

            $description = apply_filters('wt_feed_filter_product_description_with_html', $description, $this->product);
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_description_with_html", $description, $this->product, $this->form_data);
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
            $short_description = strip_tags($short_description);

            $short_description = apply_filters('wt_feed_filter_product_short_description', $short_description, $this->product);
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_short_description", $short_description, $this->product, $this->form_data);
        }
        
        
        /**
         * Get product type.
         *
         * @return mixed|void
         */
        public function product_type($catalog_attr, $product_attr, $export_columns) {
            $id = $this->product->get_id();
            if ($this->product->is_type('variation')) {
                $id = $this->product->get_parent_id();
            }                      
            
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');
            $product_categories = '';
            $term_list = get_the_terms($id, 'product_cat');

            if (is_array($term_list)) {
                $col = array_column($term_list, "term_id");
                array_multisort($col, SORT_ASC, $term_list);
                $term_list = array_column($term_list, "name");                
                $product_categories = implode($separator, $term_list);
            }


            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_local_category", $product_categories, $this->product, $this->form_data);
        }        

        /**
         * Get product primary category.
         *
         * @return mixed|void
         */
        public function primary_category($catalog_attr, $product_attr, $export_columns) {
            $parent_category = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');

            $full_category = $this->product_type();
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $parent_category = $full_category_array[0];
            }

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_primary_category", $parent_category, $this->product, $this->form_data);
        }

        /**
         * Get product primary category id.
         *
         * @return mixed|void
         */
        public function primary_category_id($catalog_attr, $product_attr, $export_columns) {
            $parent_category_id = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');
            $full_category = $this->product_type();
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $parent_category_obj = get_term_by('name', $full_category_array[0], 'product_cat');
                $parent_category_id = isset($parent_category_obj->term_id) ? $parent_category_obj->term_id : "";
            }

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_primary_category_id", $parent_category_id, $this->product, $this->form_data);
        }

        /**
         * Get product child category.
         *
         * @return mixed|void
         */
        public function child_category($catalog_attr, $product_attr, $export_columns) {
            $child_category = "";
            $separator = apply_filters('wt_feed_product_type_separator', ' > ');
            $full_category = $this->product_type();
            if (!empty($full_category)) {
                $full_category_array = explode($separator, $full_category);
                $child_category = end($full_category_array);
            }

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_child_category", $child_category, $this->product, $this->form_data);
        }
        
	/**
	 * Get product child category id.
	 *
	 * @return mixed|void
	 */
	public function child_category_id($catalog_attr, $product_attr, $export_columns) {
		$child_category_id = "";
		$separator         = apply_filters( 'wt_feed_product_type_separator', ' > ' );
		$full_category     = $this->product_type();
		if ( ! empty( $full_category ) ) {
			$full_category_array = explode( $separator, $full_category );
			$child_category_obj  = get_term_by( 'name', end( $full_category_array ), 'product_cat' );
			$child_category_id   = isset( $child_category_obj->term_id ) ? $child_category_obj->term_id : "";
		}
		
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_child_category_id", $child_category_id, $this->product, $this->form_data );
	}        
        
        public function material($catalog_attr, $product_attr, $export_columns) {

            $material = get_post_meta($this->product->get_id(), '_wt_feed_material', true);
          
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_material", $material, $this->product, $this->form_data);
        }

        public function pattern($catalog_attr, $product_attr, $export_columns) {

            $pattern = get_post_meta($this->product->get_id(), '_wt_feed_pattern', true);
           
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_pattern", $pattern, $this->product, $this->form_data);
        }    
                
	public function condition($catalog_attr, $product_attr, $export_columns) {
            
                $condition = get_post_meta($this->product->get_id(), '_wt_feed_condition', true);
                $condition = ( $condition ) ? $condition : 'new';
		return apply_filters( "wt_feed_{$this->parent_module->module_base}_product_condition", $condition, $this->product, $this->form_data );
	}        
        

        public function custom_label_0($catalog_attr, $product_attr, $export_columns) {

            $custom_label_0 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_0', true);

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_custom_label_0", $custom_label_0, $this->product, $this->form_data);
        }

        public function custom_label_1($catalog_attr, $product_attr, $export_columns) {

            $custom_label_1 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_1', true);

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_custom_label_1", $custom_label_1, $this->product, $this->form_data);
        }

        public function custom_label_2($catalog_attr, $product_attr, $export_columns) {

            $custom_label_2 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_2', true);

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_custom_label_2", $custom_label_2, $this->product, $this->form_data);
        }

        public function custom_label_3($catalog_attr, $product_attr, $export_columns) {

            $custom_label_3 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_3', true);

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_custom_label_3", $custom_label_3, $this->product, $this->form_data);
        }

        public function custom_label_4($catalog_attr, $product_attr, $export_columns) {

            $custom_label_4 = get_post_meta($this->product->get_id(), '_wt_feed_custom_label_4', true);

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_custom_label_4", $custom_label_4, $this->product, $this->form_data);
        }
        
        public function parent_sku($catalog_attr, $product_attr, $export_columns) {

            $parent_sku = $this->product->get_sku();
            if ($this->product->is_type('variation')) {
                $parent_prod = wc_get_product($this->product->get_parent_id());
                if (is_object($parent_prod)) {
                    $parent_sku = $parent_prod->get_sku();
                }
            }

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_parent_sku", $parent_sku, $this->product, $this->form_data);
        }

        /**
         * Get Product weight with unit.
         *
         * @return mixed|void
         */
        public function weightnunit($catalog_attr, $product_attr, $export_columns) {
            $weight = ($this->product->get_weight()) ? $this->product->get_weight().' '.get_option('woocommerce_weight_unit') : '';
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_weight", $weight, $this->product, $this->form_data);
        }
        
        /**
         * Get Product length with unit.
         *
         * @return mixed|void
         */
        public function lengthnunit($catalog_attr, $product_attr, $export_columns) {
            $length = ($this->product->get_length()) ? $this->product->get_length().' '.get_option('woocommerce_dimension_unit') : '';
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_weight", $length, $this->product, $this->form_data);
        }     
        
        /**
         * Get Product height with unit.
         *
         * @return mixed|void
         */
        public function heightnunit($catalog_attr, $product_attr, $export_columns) {
            $height = ($this->product->get_height()) ? $this->product->get_height().' '.get_option('woocommerce_dimension_unit') : '';
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_weight", $height, $this->product, $this->form_data);
        }

        /**
         * Get Product width with unit.
         *
         * @return mixed|void
         */
        public function widthnunit($catalog_attr, $product_attr, $export_columns) {
            $width = ($this->product->get_width()) ? $this->product->get_width().' '.get_option('woocommerce_dimension_unit') : '';
            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_weight", $width, $this->product, $this->form_data);
        }        
        
        /**
         * Get Product tax.
         *
         * @return mixed|void
         */
        public function vat($catalog_attr, $product_attr, $export_columns) {

            $tax_value = 0;
            // Tax rate for a product
            $tax_rates = WC_Tax::get_rates();
            if (!empty($tax_rates)) {
                // Getting the first rate percentage
                $tax_rate = reset($tax_rates);
                $tax_value = $tax_rate['rate']; // Returns the tax rate percentage
            }

            $tax_value = '::'.$tax_value;
            
            /*
             *  Tax amount for a product
              if( is_object( $this->product ) ){
              $price_excl_tax = wc_get_price_excluding_tax($this->product);
              $price_incl_tax = wc_get_price_including_tax($this->product);
              $tax_amount = $price_incl_tax - $price_excl_tax;
              $tax_value =  $tax_amount;
              }
             * 
             */

            return apply_filters("wt_feed_{$this->parent_module->module_base}_product_tax", $tax_value, $this->product, $this->form_data);
        }

        /**
         * Get additional attributes for variation other than color, size, gender and pattern.
         *
         * @return mixed|void
         */
        public function additional_variant_attribute($catalog_attr, $product_attr, $export_columns) {

            if ( !$this->product->is_type( 'variation' ) ) {
				return '';
			}

			$attributes = $this->product->get_variation_attributes();


			if ( !$attributes ) {
				return [];
			}
                        $product_data = [];
			$variant_names	 = array_keys( $attributes );
			$variant_data	 = [];

			foreach ( $variant_names as $original_variant_name ) {


				$label = wc_attribute_label( $original_variant_name, $this->product );

				$new_name = str_replace( 'custom_data:', '', self::sanitize_variant_name( $original_variant_name ) );


				if ( $options = $this->get_variant_option_name( $this->product->get_id(), $label, $attributes[ $original_variant_name ] ) ) {

					if ( is_array( $options ) ) {

						$option_values = array_values( $options );
					} else {

						$option_values = [ $options ];

						if ( count( $option_values ) === 1 && empty( $option_values[ 0 ] ) ) {
							$option_values[ 0 ]				 = 'any';
							$product_data[ 'checkout_url' ]	 = $product_data[ 'url' ];
						}
					}

					if ( 'gender' === $new_name ) {

						$product_data[ $new_name ] = $option_values[ 0 ];
					}

					switch ( $new_name ) {

						case 'size':
						case 'color':
                                                    break;
						case 'pattern':

							$variant_data[] = [
								'product_field'	 => $new_name,
								'label'			 => $label,
								'options'		 => $option_values,
							];

							$product_data[ $new_name ] = $option_values[ 0 ];

							break;

						case 'gender':

							if ( $product_data[ $new_name ] ) {

								$variant_data[] = [
									'product_field'	 => $new_name,
									'label'			 => $label,
									'options'		 => $option_values,
								];
							}

							break;

						default:

                                                        if(!isset($product_data)){
                                                            $product_data = [];
                                                        }
                                                        $variant_details = array(
                                                            'label' => ucwords( $new_name ) ,
                                                            'value' => urldecode( $option_values[ 0 ] ),
                                                        );
                                                        
                                                        array_push($product_data, $variant_details);//							
							break;
					}
                                    } else {

					continue;
				}
			}
                        if( 'xml' !== $this->form_data['advanced_form_data']['wt_pf_file_as'] ){
                            $product_data_str = '';
                            foreach($product_data as $product_data_single){
                                $product_data_str.= $product_data_single['label'].':'.$product_data_single['value'].',';
                            }
                            $product_data = rtrim(trim($product_data_str), ',');
                        }


            return apply_filters("wt_feed_{$this->parent_module->module_base}_additional_variant_attributes", $product_data, $this->product, $this->form_data);
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
        

    }

}

// Custom taxonomy filter for get product query

add_filter('woocommerce_product_data_store_cpt_get_products_query', 'wt_exclude_product_taxonomy_query', 10, 2);
if (!function_exists('wt_exclude_product_taxonomy_query')) {

    function wt_exclude_product_taxonomy_query($query, $query_vars) {
        if (!empty($query_vars['exclude_category'])) {

            $query['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $query_vars['exclude_category'],
                'operator' => 'NOT IN',
            );
        }
        if (!empty($query_vars['exclude_discarded'])) {
            $query['meta_query'][] = array(
                array(
                    'key' => '_wt_feed_discard',
                    'compare' => 'NOT EXISTS' // this should exclude all exclude from feed checked products
                ),
            );
        }

        if (!empty($query_vars['exclude_brands'])) {

            $query['tax_query'][] = array(
                'taxonomy' => 'pwb-brand',
                'field' => 'slug',
                'terms' => $query_vars['exclude_brands'],
                'operator' => 'NOT IN',
            );
        }
        if (!empty($query_vars['include_brands'])) {

            $query['tax_query'][] = array(
                'taxonomy' => 'pwb-brand',
                'field' => 'slug',
                'terms' => $query_vars['include_brands'],
                'operator' => 'IN',
            );
        }

        if (!empty($query_vars['exclude_tag'])) {

            $query['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => $query_vars['exclude_tag'],
                'operator' => 'NOT IN',
            );
        }
        return $query;
    }

}