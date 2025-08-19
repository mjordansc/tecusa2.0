<?php

if (!defined('ABSPATH'))
    exit;

class ACOPLW_Badge {

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;
    public $product_lists           = false;
    public $product_schedule_onsale = false;
    public $products_on_sale        = false;
    public $out_of_stock            = false;
    public $recent_products         = false;
    public $stock_products          = false;
    public $customStyles            = [];
    public $acoplwBadges            = [];
    // public $acoplwTimers            = [];
    public $pScheduleStatus         = [];
    private $_active                = false;
    private $active_badges          = false;

    public function __construct()
    {

        // $this->types = Array(
        //     'percent_total_amount' => __('Percentage of cart total amount', 'aco-product-labels-for-woocommerce'),
        //     'percent_product_price' => __('Percentage of product price', 'aco-product-labels-for-woocommerce'),
        //     'fixed_product_price' => __('Fixed price of product price', 'aco-product-labels-for-woocommerce'),
        //     'fixed_cart_amount' => __('Fixed price of cart total amount', 'aco-product-labels-for-woocommerce'),
        //     'cart_quantity' => __('Quantity based badge', 'aco-product-labels-for-woocommerce')
        // );

    }

    /**
     *
     * Ensures only one instance of ACOPLW is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main ACOPLW instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->_active;
    }

    // Badges
    public function acoplwBadge ( $productThumb, $product, $textstatus = false ) {

        global $post;
        if ( is_a ( $product, 'WC_Product' ) ) {
            $productID = $product->get_ID();
        } elseif ( false === $product && isset( $post->ID ) ) {
            $productID = $post->ID;
        } else {
            $productID = $product;
        }

        // Load active badges
        $this->load_badges();

        if ( $this->active_badges == null )
            return ( !$textstatus ) ? $productThumb : '';

        foreach ( $this->active_badges as $k => $badge ) {  

            $badgeID = $badge['id'];

            /* 
            * Checking Dynamic Pricing Settings
            * ver @ 3.2.3
            */
            $preview_options    = get_post_meta ( $badgeID, 'badge_preview_options', true ) ? get_post_meta ( $badgeID, 'badge_preview_options', true ) : [];
            $dynamicPR          = function_exists ('AWDP') ? ( array_key_exists( 'pricing_rule', $preview_options ) ? $preview_options['pricing_rule'] : false ) : false;
            $wdp_filecheck      = defined('AWDP_FILE') ? realpath(plugin_dir_path(AWDP_FILE)) . DIRECTORY_SEPARATOR . 'includes\class-awdp-plwsupport.php' : ''; 
            if ( $dynamicPR && $wdp_filecheck && file_exists ( $wdp_filecheck ) ) {
                include_once($wdp_filecheck); // including dynamic pricing file
                $selectedRule   = array_key_exists ( 'selected_rule', $preview_options ) ? $preview_options['selected_rule'] : '';
                $awdp       = new AWDP_plwSupport();
                if ( !$awdp->plw_check ( $selectedRule, $productID ) ) {
                    continue;
                }
            } else {
                // Get Product List
                if ( !$this->check_in_product_list ( $productID, $badgeID ) ) {
                    continue;
                }
            }

            if ( array_key_exists ( $badgeID, $this->pScheduleStatus ) && $this->pScheduleStatus[$badgeID] ) {
                if ( !$this->onSaleScheduleList( $productID, $badgeID ) ){
                    continue;
                }
            }

            $this->acoplwSaleBadge ( $productThumb, $productID, $badgeID );

        } 
        
        // Get all badges
        $acoplwActiveBadges = array_key_exists ( $productID, $this->acoplwBadges ) ? $this->acoplwBadges[$productID] : ''; 
        if ( $acoplwActiveBadges ) { 
            $badge = '';
            foreach ( $acoplwActiveBadges as $acoplwActiveBadge ) {
                $badge = $badge . $acoplwActiveBadge;
            }
            // $productThumb = '<span class="acoplw-badge">' . $badge . $productThumb . '</span>';
            $productThumb = $textstatus ? '<span class="acoplw-badge acoplw-textBlock">' . $badge . '</span>'  : '<span class="acoplw-badge">' . $badge . $productThumb . '</span>';
        } 
        
        // Return
        return $productThumb;

    }

    // Listing Page Loop
    public function acoplwBadgeElem () {

        global $product;
        $productID      = $product->get_ID();
        $productThumb   = '';

        $useJqueryPos   = get_option('acoplw_jquery_status') ? get_option('acoplw_jquery_status') : '';

        // Load active badges
        $this->load_badges();

        /*
        * jQuery positioning
        * ver 3.1.8
        */
        $badgeListingHide       = $useJqueryPos ? 'acoplw-badge-listing-hide' : '';

        foreach ( $this->active_badges as $k => $badge ) {  

            $badgeID = array_key_exists ( 'id', $badge ) ? $badge['id'] : '';

            /* 
            * Checking Dynamic Pricing Settings
            * ver @ 3.2.3
            */
            $preview_options    = get_post_meta ( $badgeID, 'badge_preview_options', true ) ? get_post_meta ( $badgeID, 'badge_preview_options', true ) : [];
            $dynamicPR          = function_exists ('AWDP') ? ( array_key_exists( 'pricing_rule', $preview_options ) ? $preview_options['pricing_rule'] : false ) : false;
            $wdp_filecheck      = defined('AWDP_FILE') ? realpath(plugin_dir_path(AWDP_FILE)) . DIRECTORY_SEPARATOR . 'includes\class-awdp-plwsupport.php' : ''; 
            if ( $dynamicPR && $wdp_filecheck && file_exists ( $wdp_filecheck ) ) {
                include_once($wdp_filecheck); // including dynamic pricing file
                $selectedRule   = array_key_exists ( 'selected_rule', $preview_options ) ? $preview_options['selected_rule'] : '';
                $awdp       = new AWDP_plwSupport();
                if ( !$awdp->plw_check ( $selectedRule, $productID ) ) {
                    continue;
                }
            } else {
                // Get Product List
                if ( !$this->check_in_product_list ( $productID, $badgeID ) ) {
                    continue;
                }
            }

            if ( array_key_exists ( $badgeID, $this->pScheduleStatus ) && $this->pScheduleStatus[$badgeID] ) {
                if ( !$this->onSaleScheduleList( $productID, $badgeID ) ){
                    continue;
                }
            }
            
            $this->acoplwSaleBadge ( $productThumb, $productID, $badgeID );
            
        } 

        // Get all badges
        $acoplwActiveBadges = array_key_exists ( $productID, $this->acoplwBadges ) ? $this->acoplwBadges[$productID] : ''; 
        if ( $acoplwActiveBadges ) { 

            $badge = '';
            foreach ( $acoplwActiveBadges as $acoplwActiveBadge ) { 
                $badge = $badge . $acoplwActiveBadge;
            }
            // $productThumb = '<span class="acoplw-badge">' . $badge . $productThumb . '</span>';
            // $productThumb = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
            $productThumb = '<span class="acoplw-badge acoplw-textBlock acoplw-elemBlock '.$badgeListingHide.'">' . $badge . '</span>';
            // $productThumb .= $useJqueryPos ? '</span>' : '';

        } 

        // Return
        echo $productThumb;

    }

    // Badge Detail 
    public function acoplwBadgeDetail () { 

        global $post;
        
        if ( !isset( $post->ID ) || '' == $post->ID ) {
            return;
        }
        $productID = $post->ID;
        $productThumb = '';
        $textstatus = true;
        $hiddenWrap = 'acoplw-hidden-wrap';

        // Load active badges
        $this->load_badges();

        if ( $this->active_badges == null )
            return ( !$textstatus ) ? $productThumb : '';

        foreach ( $this->active_badges as $k => $badge ) {  

            $badgeID = $badge['id'];

            /* 
            * Checking Dynamic Pricing Settings
            * ver @ 3.2.3
            */
            $preview_options    = get_post_meta ( $badgeID, 'badge_preview_options', true ) ? get_post_meta ( $badgeID, 'badge_preview_options', true ) : [];
            $dynamicPR          = function_exists ('AWDP') ? ( array_key_exists( 'pricing_rule', $preview_options ) ? $preview_options['pricing_rule'] : false ) : false;
            $wdp_filecheck      = defined('AWDP_FILE') ? realpath(plugin_dir_path(AWDP_FILE)) . DIRECTORY_SEPARATOR . 'includes\class-awdp-plwsupport.php' : ''; 
            if ( $dynamicPR && $wdp_filecheck && file_exists ( $wdp_filecheck ) ) {
                include_once($wdp_filecheck); // including dynamic pricing file
                $selectedRule   = array_key_exists ( 'selected_rule', $preview_options ) ? $preview_options['selected_rule'] : '';
                $awdp       = new AWDP_plwSupport();
                if ( !$awdp->plw_check ( $selectedRule, $productID ) ) {
                    continue;
                }
            } else {
                // Get Product List
                if ( !$this->check_in_product_list ( $productID, $badgeID ) ) {
                    continue;
                }
            }

            if ( array_key_exists ( $badgeID, $this->pScheduleStatus ) && $this->pScheduleStatus[$badgeID] ) {
                if ( !$this->onSaleScheduleList( $productID, $badgeID ) ){
                    continue;
                }
            }

            // Checking if timer is enabled
            $badgeOptions       = $this->active_badges; 
            $badgeOptions       = array_key_exists ( $badgeID, $badgeOptions ) ? $badgeOptions[$badgeID] : ''; 
            $enableTimer        = array_key_exists ( 'enableTimer', $badgeOptions ) ? $badgeOptions['enableTimer'] : '';
            if ( $enableTimer ) {
                continue;
            }

            $this->acoplwSaleBadge ( $productThumb, $productID, $badgeID );

        } 
        
        // Get all badges
        $acoplwActiveBadges = array_key_exists ( $productID, $this->acoplwBadges ) ? $this->acoplwBadges[$productID] : ''; 
        if ( $acoplwActiveBadges ) { 
            $badge = '';
            foreach ( $acoplwActiveBadges as $acoplwActiveBadge ) {
                $badge = $badge . $acoplwActiveBadge;
            }
            $productThumb = $textstatus ? '<div class="'.$hiddenWrap.'" style="display:none"><span class="acoplw-badge acoplw-textBlock">' . $badge . '</span></div>' : '<div class="'.$hiddenWrap.'" style="display:none"><span class="acoplw-badge">' . $badge . $productThumb . '</span></div>';
        } 
        
        // Detail page badge
        echo $productThumb;

    }

    // Show Badge
    public function acoplwSaleBadge ( $productThumb, $productID, $badgeID ) {

        if ( $this->active_badges != false && sizeof($this->active_badges) >= 1 && !is_cart() && !is_checkout() ) { 

            $customStyle            = '';
            $badge                  = '';
            $saleperc               = '';
            $wdpDiscLabel           = '';
            $category               = '';
            $dynmFlag               = false;

            // Custom Class
            $acoCustomClass         = 'acoplw-badge-'.$badgeID;

            $badgeOptions           = $this->active_badges; 
            $badgeOptions           = array_key_exists ( $badgeID, $badgeOptions ) ? $badgeOptions[$badgeID] : ''; 

            $label                  = ( array_key_exists ( 'label', $badgeOptions ) && !empty ( $badgeOptions['label'] ) ) ? $badgeOptions['label'] : 'Sale';
            $labelColor             = array_key_exists ( 'labelColor', $badgeOptions ) ? $badgeOptions['labelColor'] : '';
            $fontSize               = array_key_exists ( 'fontSize', $badgeOptions ) ? $badgeOptions['fontSize'] : '';
            $lineHeight             = array_key_exists ( 'lineHeight', $badgeOptions ) ? $badgeOptions['lineHeight'] : '';

            $badgeStyle             = array_key_exists ( 'badgeStyle', $badgeOptions ) ? $badgeOptions['badgeStyle'] : '';
            $badgeType              = array_key_exists ( 'badgeType', $badgeOptions ) ? $badgeOptions['badgeType'] : '';
            $badgeColor             = array_key_exists ( 'badgeColor', $badgeOptions ) ? $badgeOptions['badgeColor'] : '';
            $badgeWidth             = ( array_key_exists ( 'badgeWidth', $badgeOptions ) && $badgeOptions['badgeWidth'] != '' ) ? (int)$badgeOptions['badgeWidth'] : 60;
            $badgeHeight            = ( array_key_exists ( 'badgeHeight', $badgeOptions ) && $badgeOptions['badgeWidth'] != '' ) ? (int)$badgeOptions['badgeHeight'] : 30;
            $borderTopLeft          = array_key_exists ( 'borderTopLeft', $badgeOptions ) ? $badgeOptions['borderTopLeft'] : '';
            $borderTopRight         = array_key_exists ( 'borderTopRight', $badgeOptions ) ? $badgeOptions['borderTopRight'] : '';
            $borderBottomLeft       = array_key_exists ( 'borderBottomLeft', $badgeOptions ) ? $badgeOptions['borderBottomLeft'] : '';
            $borderBottomRight      = array_key_exists ( 'borderBottomRight', $badgeOptions ) ? $badgeOptions['borderBottomRight'] : '';

            $opacity                = array_key_exists ( 'opacity', $badgeOptions ) ? $badgeOptions['opacity'] : '';
            $rotationX              = array_key_exists ( 'rotationX', $badgeOptions ) ? $badgeOptions['rotationX'] : '';
            $rotationY              = array_key_exists ( 'rotationY', $badgeOptions ) ? $badgeOptions['rotationY'] : '';
            $rotationZ              = array_key_exists ( 'rotationZ', $badgeOptions ) ? $badgeOptions['rotationZ'] : '';
            $flipHorizontal         = array_key_exists ( 'flipHorizontal', $badgeOptions ) ? $badgeOptions['flipHorizontal'] : '';
            $flipVertical           = array_key_exists ( 'flipVertical', $badgeOptions ) ? $badgeOptions['flipVertical'] : '';
            $badgePosition          = array_key_exists ( 'badgePosition', $badgeOptions ) ? $badgeOptions['badgePosition'] : '';
            $badgePositionHorizontal = array_key_exists ( 'badgePositionHorizontal', $badgeOptions ) ? $badgeOptions['badgePositionHorizontal'] : '';
            $posTop                 = ( array_key_exists ( 'posTop', $badgeOptions ) && $badgeOptions['posTop'] ) ? $badgeOptions['posTop'] : 0;
            $posBottom              = ( array_key_exists ( 'posBottom', $badgeOptions ) && $badgeOptions['posBottom'] )  ? $badgeOptions['posBottom'] : 0;
            $posLeft                = ( array_key_exists ( 'posLeft', $badgeOptions ) && $badgeOptions['posLeft'] )  ? $badgeOptions['posLeft'] : 0;
            $posRight               = ( array_key_exists ( 'posRight', $badgeOptions ) && $badgeOptions['posRight'] )  ? $badgeOptions['posRight'] : 0;

            $checkSale              = array_key_exists ( 'saleBadge', $badgeOptions ) ? $badgeOptions['saleBadge'] : '';

            $advBadgePrimaryColor   = array_key_exists ( 'advBadgePrimaryColor', $badgeOptions ) ? $badgeOptions['advBadgePrimaryColor'] : '';
            $advBadgePrimaryColorHex = array_key_exists ( 'advBadgePrimaryColorHex', $badgeOptions ) ? $badgeOptions['advBadgePrimaryColorHex'] : '';
            $advBadgeSecondaryColor = array_key_exists ( 'advBadgeSecondaryColor', $badgeOptions ) ? $badgeOptions['advBadgeSecondaryColor'] : '';
            $advBadgeSecondaryColorHex = array_key_exists ( 'advBadgeSecondaryColorHex', $badgeOptions ) ? $badgeOptions['advBadgeSecondaryColorHex'] : '';
            $advtoptext             = array_key_exists ( 'advtoptext', $badgeOptions ) ? $badgeOptions['advtoptext'] : '';
            $advTopTextColor        = array_key_exists ( 'advTopTextColor', $badgeOptions ) ? $badgeOptions['advTopTextColor'] : '';
            $advTopTextColorHex     = array_key_exists ( 'advTopTextColorHex', $badgeOptions ) ? $badgeOptions['advTopTextColorHex'] : '';
            $advbottomtext          = array_key_exists ( 'advbottomtext', $badgeOptions ) ? $badgeOptions['advbottomtext'] : '';
            $advBottomTextColor     = array_key_exists ( 'advBottomTextColor', $badgeOptions ) ? $badgeOptions['advBottomTextColor'] : '';
            $advBottomTextColorHex  = array_key_exists ( 'advBottomTextColorHex', $badgeOptions ) ? $badgeOptions['advBottomTextColorHex'] : '';
            $advBadgeCat            = array_key_exists ( 'advBadgeCat', $badgeOptions ) ? $badgeOptions['advBadgeCat'] : '';
            $enableDynamicValue     = array_key_exists ( 'enableDynamicValue', $badgeOptions ) ? $badgeOptions['enableDynamicValue'] : '';

            $enableTimer            = array_key_exists ( 'enableTimer', $badgeOptions ) ? $badgeOptions['enableTimer'] : '';
            $timerDayLabel          = ( array_key_exists ( 'timerDayLabel', $badgeOptions ) && $badgeOptions['timerDayLabel'] ) ? $badgeOptions['timerDayLabel'] : 'Days';
            $timerHrsLabel          = ( array_key_exists ( 'timerHrsLabel', $badgeOptions ) && $badgeOptions['timerHrsLabel'] ) ? $badgeOptions['timerHrsLabel'] : 'Hrs';
            $timerMinLabel          = ( array_key_exists ( 'timerMinLabel', $badgeOptions ) && $badgeOptions['timerMinLabel'] ) ? $badgeOptions['timerMinLabel'] : 'Mins';
            $timerSecLabel          = ( array_key_exists ( 'timerSecLabel', $badgeOptions ) && $badgeOptions['timerSecLabel'] ) ? $badgeOptions['timerSecLabel'] : 'Secs';
            $enableProductTimer     = array_key_exists ( 'enableProductTimer', $badgeOptions ) ? $badgeOptions['enableProductTimer'] : '';

            $timerPosRight          = ( array_key_exists ( 'timerPosRight', $badgeOptions ) && $badgeOptions['timerPosRight'] ) ? $badgeOptions['timerPosRight'] : 0;
            $timerPosLeft           = ( array_key_exists ( 'timerPosLeft', $badgeOptions ) && $badgeOptions['timerPosLeft'] ) ? $badgeOptions['timerPosLeft'] : 0;
            $timerPosBottom         = ( array_key_exists ( 'timerPosBottom', $badgeOptions ) && $badgeOptions['timerPosBottom'] ) ? $badgeOptions['timerPosBottom'] : 0;
            $timerPosTop            = ( array_key_exists ( 'timerPosTop', $badgeOptions ) && $badgeOptions['timerPosTop'] ) ? $badgeOptions['timerPosTop'] : 0;

            $timerPositionHorizontal = array_key_exists ( 'timerPositionHorizontal', $badgeOptions ) ? $badgeOptions['timerPositionHorizontal'] : '';
            $timerPosition          = array_key_exists ( 'timerPosition', $badgeOptions ) ? $badgeOptions['timerPosition'] : '';
            $timerOpacity           = array_key_exists ( 'timerOpacity', $badgeOptions ) ? $badgeOptions['timerOpacity'] : '';
            $timerlineHeight        = array_key_exists ( 'timerlineHeight', $badgeOptions ) ? $badgeOptions['timerlineHeight'] : '';
            $timerfontSize          = array_key_exists ( 'timerfontSize', $badgeOptions ) ? $badgeOptions['timerfontSize'] : '';
            $timerLabelColor        = array_key_exists ( 'timerLabelColor', $badgeOptions ) ? $badgeOptions['timerLabelColor'] : '';
            $timerLabelColorHex     = array_key_exists ( 'timerLabelColorHex', $badgeOptions ) ? $badgeOptions['timerLabelColorHex'] : '';
            $timerBorderColor       = array_key_exists ( 'timerBorderColor', $badgeOptions ) ? $badgeOptions['timerBorderColor'] : '';
            $timerBorderColorHex    = array_key_exists ( 'timerBorderColorHex', $badgeOptions ) ? $badgeOptions['timerBorderColorHex'] : '';
            $timerStart             = array_key_exists ( 'timerStart', $badgeOptions ) ? $badgeOptions['timerStart'] : '';
            $timerEnd               = array_key_exists ( 'timerEnd', $badgeOptions ) ? $badgeOptions['timerEnd'] : '';
            $timerPrefixLabel       = array_key_exists ( 'timerPrefixLabel', $badgeOptions ) ? $badgeOptions['timerPrefixLabel'] : '';
            $timerPrefixfontSize    = array_key_exists ( 'timerPrefixfontSize', $badgeOptions ) ? $badgeOptions['timerPrefixfontSize'] : '';
            $timerPrefixlineHeight  = array_key_exists ( 'timerPrefixlineHeight', $badgeOptions ) ? $badgeOptions['timerPrefixlineHeight'] : '';

            $useJqueryPos           = get_option('acoplw_jquery_status') ? get_option('acoplw_jquery_status') : '';

            $CalcSixVal             = ( $badgeWidth <= 60 ) ? 0.167 : ( ( $badgeWidth <= 90 ) ? 0.22 : ( ( $badgeWidth > 90 ) ? 0.25 : 0 ) );

            $CalcFiveValOne         = ( $badgeWidth < 85 ) ? 1.5 : ( ( $badgeWidth > 85 ) ? 1.41 : 0 );
            $CalcFiveValTwo         = ( $badgeWidth <= 40 ) ? 0 : ( ( $badgeWidth < 60 ) ? 0.11 : ( ( $badgeWidth < 85 ) ? 0.167 : ( ( $badgeWidth > 85 ) ? 0.26 : 0 ) ) );
            $CalcFiveValThree       = ( $badgeWidth <= 40 ) ? 0.45 : ( ( $badgeWidth < 60 ) ? 0.43 : ( ( $badgeWidth < 85 ) ? 0.42 : ( ( $badgeWidth > 85 ) ? 0.31 : 0 ) ) );

            $bsSixWidth             = $badgeWidth != '' ? $badgeWidth + 30 : 90;

            $bsSixTop               = $badgeWidth != '' ? $badgeWidth * $CalcSixVal : 15;

            $bsFiveWidth            = $badgeWidth != '' ? $badgeWidth * $CalcFiveValOne : 100; 
            $bsFiveTop              = $badgeWidth != '' ? $badgeWidth * $CalcFiveValTwo : '';
            $bsFiveLeft             = ( $badgeWidth != '' && $badgePositionHorizontal == 'bpthree' ) ? -$badgeWidth * $CalcFiveValThree . "px" : 'auto';
            $bsFiveRight            = ( $badgeWidth != '' && $badgePositionHorizontal == 'bpfour' ) ? -$badgeWidth * $CalcFiveValThree . "px" : 'auto';

            $preview_options        = get_post_meta ( $badgeID, 'badge_preview_options', true ) ? get_post_meta ( $badgeID, 'badge_preview_options', true ) : [];

            /* 
            * Dynamic Values 
            * ver 3.1.5
            */
            if ( strpos($label,'{day}') !== false || strpos($label,'{month}') !== false || strpos($label,'{year}') !== false || strpos($label,'{salepercentage}') !== false || strpos($label,'{wdpdiscount}') !== false || strpos($label,'{category}') !== false ) {
                
                // Get wordpress timezone settings
                $gmt_offset         = get_option('gmt_offset');
                $timezone_string    = get_option('timezone_string');

                $dayFormat          = get_option('acoplw_detail_dayFormat') ? ( get_option('acoplw_detail_dayFormat') == 'form_full' ? 'l' : ( get_option('acoplw_detail_dayFormat') == 'form_short' ? 'D' : 'jS' )  ) : 'l';
                $monthFormat        = get_option('acoplw_detail_monthFormat') ? ( get_option('acoplw_detail_monthFormat') == 'form_full' ? 'F' : ( get_option('acoplw_detail_monthFormat') == 'form_short' ? 'M' : 'n' )  ) : 'F';
                $yearFormat         = get_option('acoplw_detail_yearFormat') ? ( get_option('acoplw_detail_yearFormat') == 'form_full' ? 'Y' : 'y' ) : 'Y';

                if ($timezone_string) {
                    $datenow    = new DateTime(current_time('mysql'), new DateTimeZone($timezone_string));
                } else {
                    $min        = 60 * get_option('gmt_offset');
                    $sign       = $min < 0 ? "-" : "+";
                    $absmin     = abs($min);
                    $tz         = sprintf("%s%02d%02d", $sign, $absmin / 60, $absmin % 60);
                    $datenow    = new DateTime(current_time('mysql'), new DateTimeZone($tz));
                }

                global $product;
                if ( ! is_object( $product) ) $product = wc_get_product( get_the_ID() ); 
                if ( is_a ( $product, 'WC_Product' ) ) { 
                    if( $product->is_on_sale() && $checkSale ) {
                        if ( $product->is_type( 'variable' ) ) { 
                            // The active price min and max
                            $acoplw_sale_price 		= $product->get_variation_sale_price('max'); 
                            $acoplw_regular_price 	= $product->get_variation_regular_price('max'); 
                        } else {
                            $acoplw_sale_price 		= $product->get_sale_price();
                            $acoplw_regular_price 	= $product->get_regular_price();
                        }
                        if ( $acoplw_sale_price && $acoplw_regular_price ) {
                            $acoplw_percentage = 100 - round ( ( $acoplw_sale_price / $acoplw_regular_price ) * 100 );
                            $saleperc = $acoplw_percentage.'%';
                        }
                    }
                }

                // WDP Discount Value
                if ( isset ( $preview_options['pricing_rule'] ) && isset ( $preview_options['selected_rule'] ) ) {
                    $wdpRule        = $preview_options['selected_rule'];
                    $wdpDiscount    = get_post_meta ( $wdpRule, 'discount_value', true ) ? get_post_meta ( $wdpRule, 'discount_value', true ) : '';
                    $wdpDiscType    = get_post_meta ( $wdpRule, 'discount_type', true ) ? get_post_meta ( $wdpRule, 'discount_type', true ) : '';
                    if ( $wdpDiscType === 'fixed_product_price' || $wdpDiscType === 'percent_product_price' ) {
                        $wdpDiscLabel = $wdpDiscType === 'fixed_product_price' ? $wdpDiscount . ' OFF' : $wdpDiscount . '%';
                    }
                }

                // Category
                if ( strpos($label, '{category}') !== false ) {
                    $cat_list   = wp_get_post_terms($productID,'product_cat',array('fields'=>'names')); 
                    $category   = !empty ( $cat_list ) ? $cat_list[0] : '';
                }

                $datenow    = $datenow->format('Y-m-d H:i:s');
                $day        = date($dayFormat);
                $month      = date($monthFormat);
                $year       = date($yearFormat);

                $label      = str_replace('{day}', $day, $label); 
                $label      = str_replace('{month}', $month, $label); 
                $label      = str_replace('{year}', $year, $label); 
                $label      = str_replace('{salepercentage}', $saleperc, $label);
                $label      = str_replace('{wdpdiscount}', $wdpDiscLabel, $label);
                $label      = str_replace('{category}', $category, $label);

                $dynmFlag   = true;
            }

            /*
            * Badge name string translation using WPML.
            * ver 3.2.11
            */
            if (defined('ICL_SITEPRESS_VERSION')) {
                // WPML is active, register and translate the string
                do_action('wpml_register_single_string', 'aco-product-labels-for-woocommerce', 'badge_label_'.$label, $label);
                $label = apply_filters('wpml_translate_single_string', $label, 'aco-product-labels-for-woocommerce', 'badge_label_'.$label);
            }

            /*
            * borderRadiusExclude, badgeHW
            * ver 3.1.1
            */
            $borderRadiusExclude    = array ( 'bsfive', 'bseight', 'bsnine', 'bsfourteen', 'bsseventeen', 'bssixteen', 'bsten', 'bsfifteen' );
            $transformExclude       = array ( 'bssixteen' );
            $heightExclude          = array ( 'bsfourteen' );
            $badgeHW                = array ( 'bseight', 'bsseventeen', 'bsseven' );
            // $badgeTrnsVert          = array ( 'bsseven', 'bseight', 'bsten' );
            $badgeTrnsVert          = array ( 'bsseven', 'bseight', 'bsten', 'bssix', 'bsfive', 'bsfourteen' );
            $badgeTrnsRot           = array ( 'bssix', 'bsfive' );
            $badgeTrnsRotVal        = ( ( $badgePositionHorizontal == 'bpthree' && $badgePosition == 'bptwo' ) || ( $badgePositionHorizontal == 'bpfour' && $badgePosition == 'bpone' ) ) ? 315 : 45;

            /*
            * jQuery positioning
            * ver 3.1.8
            */
            $badgeListingHide       = $useJqueryPos ? 'acoplw-badge-listing-hide' : '';

            if ( $badgeType == 'image' ) { // Image Badges

                $badgeCSSClass = 'acoplw-badge-icon acoplw-badge-image '. $acoCustomClass;
                $badgeCSSClass .= ( $badgePositionHorizontal == 'bpthree' ) ? ' acoplwLeftAlign' : ' acoplwRightAlign';

                $css = "opacity:".($opacity / 100).";width:".$badgeWidth."px;";
                $css .= "transform:rotateX(". ( $rotationX * 3.6 )."deg) rotateY(". ( $rotationY * 3.6 ) ."deg) rotateZ(". ( $rotationZ * 3.6 ) ."deg);";

                $css .= ( $badgePosition == 'bpone' ) ? "top:".$posTop."px;bottom:auto;" : '';
                $css .= ( $badgePosition == 'bptwo' ) ? "bottom:".$posBottom."px;top:auto;" : '';
                $css .= ( $badgePositionHorizontal == 'bpthree' ) ? "left:".$posLeft."px;right:auto;" : '';
                $css .= ( $badgePositionHorizontal == 'bpfour' ) ? "right:".$posRight."px;left:auto;" : '';
                
                /**
                 * Adds the badge path to the upload directory if it exists.
                 * Version 4.0.0
                 */
                
                $upload_dir     = wp_upload_dir();
                $acoplwUpload   = $upload_dir['basedir'].'/acoplw-badges';

                $imgFolders = array_filter(glob($acoplwUpload . '/*'), 'is_dir');
                $imgFolder = false;
                foreach($imgFolders as $folder) {
                    if (file_exists($folder . '/' . $badgeStyle . '.png')) {
                        $imgFolder = $folder;
                        break;
                    }
                }

                if ($imgFolder) {
                    $imgSrc = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $imgFolder) . '/' . $badgeStyle . '.png';
                }else{
                    $imgSrc = ACOPLW_URL.'assets/images/badges/'.$badgeStyle.'.png';
                }

                // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                $badge = '<span class="'.$badgeCSSClass.' '.$badgeListingHide.'" style="'.$css.'"><img src="'.$imgSrc.'" title="'.$label.'"/></span>';
                // $badge .= $useJqueryPos ? '</span>' : '';

                $this->acoplwBadges[$productID][$badgeID] = $badge;
                $this->customStyles[$badgeID] = $customStyle;
                
            } else if ( $badgeType == 'upload' ) { // Custom Uploads

                $badgeCSSClass = 'acoplw-badge-icon acoplw-badge-image '. $acoCustomClass;
                $badgeCSSClass .= ( $badgePositionHorizontal == 'bpthree' ) ? ' acoplwLeftAlign' : ' acoplwRightAlign';

                $css = "opacity:".($opacity / 100).";width:".$badgeWidth."px;";
                $css .= "transform:rotateX(". ( $rotationX * 3.6 )."deg) rotateY(". ( $rotationY * 3.6 ) ."deg) rotateZ(". ( $rotationZ * 3.6 ) ."deg);";

                $css .= ( $badgePosition == 'bpone' ) ? "top:".$posTop."px;bottom:auto;" : '';
                $css .= ( $badgePosition == 'bptwo' ) ? "bottom:".$posBottom."px;top:auto;" : '';
                $css .= ( $badgePositionHorizontal == 'bpthree' ) ? "left:".$posLeft."px;right:auto;" : '';
                $css .= ( $badgePositionHorizontal == 'bpfour' ) ? "right:".$posRight."px;left:auto;" : '';

                $imgSrc = wp_get_attachment_url( $badgeStyle );

                // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                $badge = '<span class="'.$badgeCSSClass.' '.$badgeListingHide.'" style="'.$css.'"><img src="'.$imgSrc.'" title="'.$label.'"/></span>';
                // $badge .= $useJqueryPos ? '</span>' : '';

                $this->acoplwBadges[$productID][$badgeID] = $badge;
                $this->customStyles[$badgeID] = $customStyle;

            } else if ( $badgeType == 'timer' ) {

                if ( $enableTimer ) {

                    // Get Custom CSS
                    $css = "opacity:".($timerOpacity / 100).";";
                    $css .= $timerLabelColor ? "color:rgba(".$timerLabelColor['r'].", ".$timerLabelColor['g'].", ".$timerLabelColor['b'].", ".$timerLabelColor['a'].")" : "";

                    $css .= ( $timerPosTop && $timerPosition != 'bptwo' ) ? "top:".$timerPosTop."px;bottom:auto;" : ( ( $timerPosition == 'bpone' ) ? ( $timerPosTop ? "top:".$timerPosTop."px;bottom:auto;" : "top:0px;bottom:auto;" ) : '' );
                    $css .= ( $timerPosBottom && $timerPosition != 'bpone' ) ? "bottom:".$timerPosBottom."px;top:auto;" : ( ( $timerPosition == 'bptwo' ) ? ( $timerPosBottom ? "bottom:".$timerPosTop."px;top:auto;" : "bottom:0px;top:auto;" ) : '' );
                    $css .= ( $timerPosLeft && $timerPositionHorizontal != 'bpfour' ) ? "left:".$timerPosLeft."px;right:auto;" : '';
                    $css .= ( $timerPosRight && $timerPositionHorizontal != 'bpthree' ) ? "right:".$timerPosRight."px;left:auto;" : '';
                    $css .= "line-height:".$lineHeight."px;font-size:".$fontSize."px;";

                    // Timer Block CSS
                    $blockcss  = $timerLabelColor ? "color:rgba(".$timerLabelColor['r'].", ".$timerLabelColor['g'].", ".$timerLabelColor['b'].", ".$timerLabelColor['a'].");" : "";
                    $blockcss .= $timerBorderColor ? "border-color:rgba(".$timerBorderColor['r'].", ".$timerBorderColor['g'].", ".$timerBorderColor['b'].", ".$timerBorderColor['a'].");" : "";

                    $tmvalcss  = $timerBorderColor ? "border-color:rgba(".$timerBorderColor['r'].", ".$timerBorderColor['g'].", ".$timerBorderColor['b'].", ".$timerBorderColor['a'].");" : "";
                    $tmvalcss .= $timerLabelColor ? "color:rgba(".$timerLabelColor['r'].", ".$timerLabelColor['g'].", ".$timerLabelColor['b'].", ".$timerLabelColor['a'].");line-height:".$timerlineHeight."px;font-size:".$timerfontSize."px;" : "line-height:".$timerlineHeight."px;font-size:".$timerfontSize."px;";

                    $tmlblcss  = $timerLabelColor ? "color:rgba(".$timerLabelColor['r'].", ".$timerLabelColor['g'].", ".$timerLabelColor['b'].", ".$timerLabelColor['a'].");line-height:".$timerlineHeight."px;" : "line-height:".$timerlineHeight."px;";

                    // wordpress timezone settings
                    $gmt_offset = get_option('gmt_offset');
                    $timezone_string = get_option('timezone_string');
                    if( $timezone_string ) { 
                        $datenow = new DateTime(current_time('mysql'), new DateTimeZone($timezone_string));
                    } else { 
                        $min    = 60 * get_option('gmt_offset'); 
                        $sign   = $min < 0 ? "-" : "+";
                        $absmin = abs($min); 
                        $tz     = sprintf("%s%02d%02d", $sign, $absmin/60, $absmin%60); 
                        $datenow = new DateTime(current_time('mysql'), new DateTimeZone($tz)); 
                    }
                    // $datenow->setTimezone(new DateTimeZone('+000')); // Converting to UTC+000 (moment isoString timezone)
                    $datenow    = $datenow->format('Y-m-d H:i:s');
                    $day        = date("l");
                    // End Time

                    $acoplwtimer = '';

                    $start_date = date_format(date_create($timerStart),"Y-m-d H:i:s");
                    $end_date   = date_format(date_create($timerEnd),"Y-m-d H:i:s");

                    $badgeVerticalClass = preg_match( "/vertical/i", $badgeStyle ) ? ' acoplwStyleVertical' : '';

                    if ( $timerEnd != '' && ( ( strtotime($datenow) >= strtotime($start_date) ) && ( strtotime($datenow) <= strtotime($end_date) ) ) ) {
                        if ( $badgeStyle == 'horizontal003' ) {
                            $time = '<div class="acoplwTimer acoplwTimerStyle'. $badgeStyle.'" data-time="'.date_format(date_create($timerEnd),"d F Y G:i:s eP").'">
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwDays" style="'.$tmvalcss.'"></span>
                                        <span class="timerLabel" style="'.$tmlblcss.'">'.$timerDayLabel.'</span>
                                    </div>
                                </div>
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwHours" style="'.$tmvalcss.'"></span>
                                        <span class="timerLabel" style="'.$tmlblcss.'">'.$timerHrsLabel.'</span>
                                    </div>
                                </div>
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwMinutes" style="'.$tmvalcss.'"></span>
                                        <span class="timerLabel" style="'.$tmlblcss.'">'.$timerMinLabel.'</span>
                                    </div>
                                </div>
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwSeconds" style="'.$tmvalcss.'"></span>
                                        <span class="timerLabel" style="'.$tmlblcss.'">'.$timerSecLabel.'</span>
                                    </div>
                                </div>
                            </div>';
                        } else {
                            $time = '<div class="acoplwTimer acoplwTimerStyle'. $badgeStyle.'" data-time="'.date_format(date_create($timerEnd),"d F Y G:i:s eP").'">
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwDays" style="'.$tmvalcss.'"></span>
                                    <span class="timerLabel">'.$timerDayLabel.'</span>
                                </div>
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwHours" style="'.$tmvalcss.'"></span>
                                    <span class="timerLabel">'.$timerHrsLabel.'</span>
                                </div>
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwMinutes" style="'.$tmvalcss.'"></span>
                                    <span class="timerLabel">'.$timerMinLabel.'</span>
                                </div>
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwSeconds" style="'.$tmvalcss.'"></span>
                                    <span class="timerLabel">'.$timerSecLabel.'</span>
                                </div>
                            </div>';
                        }
                        $acoplwtimer = ( $badgeStyle == 'horizontal008' && $timerPrefixLabel ) ? '<div class="acoplw-timer-wrap acoplw-timer-listing"><span class="acoplwTimerPrefixLabel">' . $timerPrefixLabel . '</span>' . $time . '</div>' : '<div class="acoplw-timer-wrap acoplw-timer-listing">' . $time . '</div>';
                    }

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="acoplw-badge-icon acoplw-badge-timer'.$badgeVerticalClass.' '.$badgeListingHide.' '.$acoCustomClass.'" style="'.$css.'">'.$acoplwtimer.'</span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                }
                
                $this->acoplwBadges[$productID][$badgeID] = $badge;
                // $this->acoplwTimers[$productID][$badgeID] = $badge; // Active Timers
                $this->customStyles[$badgeID] = $customStyle;

            } else if ( $badgeType == 'advanced' ) { // Advanced Badges

                $badgeCSSClass = 'acoplw-badge-icon acoplw-badge-advanced acoplw-'.$advBadgeCat.' '.$acoCustomClass;
                $badgeCSSClass .= ' acoplw-'.$badgeStyle;
                $badgeCSSClass .= ( $badgePositionHorizontal == 'bpthree' ) ? ' acoplwLeftAlign' : ' acoplwRightAlign';
                $customClass = ( $advBadgeCat == 'advTypeOne' || $advBadgeCat == 'advTypeTwo' ) ? 'acoplw-'.get_post_field( "post_name", $badgeID ).'-custom' : '';

                $css = "opacity:".($opacity / 100).";";
                // $css .= ( $badgeStyle == 'bsone' && $badgeHeight ) ? "height:".$badgeHeight."px;" : ( ( $badgeStyle == 'bseight' && $badgeWidth ) ? "height:".$badgeWidth."px;" : '' );
                $css .= ( $advBadgeCat == 'advTypeTwo' && $advBadgePrimaryColor ) ? "background:rgba(".$advBadgePrimaryColor['r'].", ".$advBadgePrimaryColor['g'].", ".$advBadgePrimaryColor['b'].", ".$advBadgePrimaryColor['a'].");" : '';
                // $css .= ( $advBadgeCat == 'advTypeOne' ) ? '' : "transform:rotateX(". ( $rotationX * 3.6 )."deg) rotateY(". ( $rotationY * 3.6 ) ."deg) rotateZ(". ( $rotationZ * 3.6 ) ."deg);";

                $css .= ( $badgePosition == 'bpone' ) ? "top:".$posTop."px;bottom:auto;" : '';
                $css .= ( $badgePosition == 'bptwo' ) ? "bottom:".$posTop."px;top:auto;" : '';
                $css .= ( $badgePositionHorizontal == 'bpthree' ) ? "left:".$posLeft."px;right:auto;" : '';
                $css .= ( $badgePositionHorizontal == 'bpfour' ) ? "right:".$posRight."px;left:auto;" : '';

                $textcss = "color:rgba(".$labelColor['r'].", ".$labelColor['g'].", ".$labelColor['b'].", ".$labelColor['a'].");font-size:".$fontSize."px;";
                // $textcss .= ( $flipHorizontal && $flipHorizontal ) ? 'transform: scaleX(-1) scaleY(-1);' : ( ( $flipHorizontal ) ? 'transform: scaleX(-1);' : ( ( $flipHorizontal ) ? 'transform: scaleY(-1);' : '' ) );
                $textcss .= ( ( $advBadgeCat == 'advTypeOne' || $advBadgeCat == 'advTypeTwo' ) && $advBadgeSecondaryColor ) ? "background:rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", ".$advBadgeSecondaryColor['a'].");" : '';

                $primcss = ( ( $advBadgeCat == 'advTypeOne' || $advBadgeCat == 'advTypeTwo' ) && $advTopTextColor ) ? "color:rgba(".$advTopTextColor['r'].", ".$advTopTextColor['g'].", ".$advTopTextColor['b'].", ".$advTopTextColor['a'].");" : '';
                $secncss = ( ( $advBadgeCat == 'advTypeOne' || $advBadgeCat == 'advTypeTwo' ) && $advBottomTextColor ) ? "color:rgba(".$advBottomTextColor['r'].", ".$advBottomTextColor['g'].", ".$advBottomTextColor['b'].", ".$advBottomTextColor['a'].");" : '';

                $textBlockWrap = ( $advBadgeCat == 'advTypeFour' && $advBadgePrimaryColor ) ? "background:rgba(".$advBadgePrimaryColor['r'].", ".$advBadgePrimaryColor['g'].", ".$advBadgePrimaryColor['b'].", ".$advBadgePrimaryColor['a'].");" : '';
                $clippingMask = ( $advBadgeCat == 'advTypeFour' && $advBadgeSecondaryColor ) ? "background:rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", ".$advBadgeSecondaryColor['a'].");" : '';

                if ( $advBadgeCat == 'advTypeOne' && $advBadgePrimaryColor ) {
                    $customStyle = ".".$customClass.":before { background:rgba(".$advBadgePrimaryColor['r'].", ".$advBadgePrimaryColor['g'].", ".$advBadgePrimaryColor['b'].", ".$advBadgePrimaryColor['a'].") !important; }";
                } else if ( $advBadgeCat == 'advTypeTwo' && $advBadgeSecondaryColor ) {
                    $customStyle = ".".$customClass." .acoplw-blockTextWrap:before { border-color: rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", .8) rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", .8) rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", .8) transparent !important; } .".$customClass." .acoplw-blockTextWrap:after { border-color: rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", .8) transparent rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", .8) rgba(".$advBadgeSecondaryColor['r'].", ".$advBadgeSecondaryColor['g'].", ".$advBadgeSecondaryColor['b'].", .8) !important; }";
                } 

                if ( $css ) {
                    $customStyle .= ".".$customClass." { ".$css." }";
                }

                if ( $advBadgeCat == 'advTypeOne' ) {

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass. ' ' .$customClass.' '.$badgeListingHide.'" style="'.$css.'">
                            <svg viewBox="0 0 210 210">
                                <g stroke="none" fill="none">
                                    <path d="M22,104.5 C22,58.9365081 58.9365081,22 104.5,22 C150.063492,22 187,58.9365081 187,104.5" id="acoplw-top"></path>
                                    <path d="M22,104.5 C22,150.063492 58.9365081,187 104.5,187 C150.063492,187 187,150.063492 187,104.5" id="acoplw-bottom"></path>
                                </g>
                                <circle cx="105" cy="105" r="62" stroke="currentColor" stroke-width="1" fill="none"/>
                                <text width="200" font-size="30" fill="currentColor" style="'.$primcss.'">
                                    <textPath startOffset="50%" text-anchor="middle" alignment-baseline="middle" xlink:href="#acoplw-top">
                                        '.$advtoptext.'
                                    </textPath>
                                </text>
                                <text width="200" font-size="30" fill="currentColor" style="'.$secncss.'">
                                    <textPath startOffset="50%" text-anchor="middle" alignment-baseline="middle" xlink:href="#acoplw-bottom">
                                        '.$advbottomtext.'
                                    </textPath>
                                </text>
                            </svg>
                            <span class="acoplw-blockTextADV" style="'.$textcss.'">'.$label.'</span>
                        </span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                } else if ( $advBadgeCat == 'advTypeFour' ) {

                    if ( $enableDynamicValue ) {
                        global $product;
                        if ( ! is_object( $product) ) $product = wc_get_product( get_the_ID() ); 
                        if ( is_a ( $product, 'WC_Product' ) ) {
                            if( $product->is_on_sale() ) {
                                if ( $product->is_type( 'variable' ) ) { 
									// The active price min and max
									$acoplw_sale_price 		= $product->get_variation_sale_price('max'); 
									$acoplw_regular_price 	= $product->get_variation_regular_price('max'); 
								} else {
									$acoplw_sale_price 		= $product->get_sale_price();
									$acoplw_regular_price 	= $product->get_regular_price();
								}
                                if ( $acoplw_sale_price && $acoplw_regular_price ) {
                                    $acoplw_percentage = 100 - round ( ( $acoplw_sale_price / $acoplw_regular_price ) * 100 );
                                    $label = $acoplw_percentage.'<span class="acoplw-blockTextADVSmall">% '.__('OFF', 'aco-product-labels-for-woocommerce').'</span>';
                                }
                            }
                        }
                    }

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass.' '.$badgeListingHide.'" style="'.$css.'">
                            <span class="acoplw-blockTextWrap" style="'.$textBlockWrap.'">
                                <span class="acoplw-blockTextADV" style="'.$textcss.'">'.$label.'</span>
                            </span>
                            <span class="acoplw-clippingMask" style="'.$clippingMask.'"></span>
                        </span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                } else {

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass. ' ' .$customClass.' '.$badgeListingHide.' '.$acoCustomClass.'" style="'.$css.'">
                            <span class="acoplw-blockTextWrap">
                                <span class="acoplw-blockTextADV" style="'.$textcss.'">'.$label.'</span>
                            </span>
                        </span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                }

                $this->acoplwBadges[$productID][$badgeID] = $badge;
                $this->customStyles[$badgeID] = $customStyle;
                
            } else { 
                
                /* 
                ***** CSS Badges *****
                */

                // $textcss = "color:rgba(".$labelColor['r'].", ".$labelColor['g'].", ".$labelColor['b'].", ".$labelColor['a'].");font-size:".$fontSize."px;line-height:".$lineHeight."px;";

                $borderLeftDisable  = ( ( $badgeStyle == 'bstwo' || $badgeStyle == 'bsthree' || $badgeStyle == 'bsfour' ) && $badgePositionHorizontal == 'bpfour' ) ? true : false;
                $borderRightDisable = ( ( $badgeStyle == 'bstwo' || $badgeStyle == 'bsthree' || $badgeStyle == 'bsfour' ) && $badgePositionHorizontal == 'bpthree') ? true : false;

                $borderTopLeft      = $borderLeftDisable ? '3' : $borderTopLeft;
                $borderBottomLeft   = $borderLeftDisable ? '3' : $borderBottomLeft;
                $borderTopRight     = $borderRightDisable ? '3' : $borderTopRight;
                $borderBottomRight  = $borderRightDisable ? '3' : $borderBottomRight;

                $badgeCSSClass  = 'acoplw-badge-icon acoplw-'.$badgeStyle;
                $badgeCSSClass .= $dynmFlag ? ' acoplw-dynamic-label' : '';
                $badgeCSSClass .= ( $badgePositionHorizontal == 'bpthree' ) ? ' acoplwLeftAlign' : ' acoplwRightAlign';
                $customClass    = ( $badgeStyle == 'bstwo' || $badgeStyle == 'bsthree' || $badgeStyle == 'bsfour' || $badgeStyle == 'bsnine' || $badgeStyle == 'bsten' || $badgeStyle == 'bstwelve' || $badgeStyle == 'bsfourteen' || $badgeStyle == 'bssixteen' || $badgeStyle == 'bsseventeen' ) ? 'acoplw-'.get_post_field( "post_name", $badgeID ).'-custom' : '';
                
                $textcss  = "color:rgba(".$labelColor['r'].", ".$labelColor['g'].", ".$labelColor['b'].", ".$labelColor['a'].");";
                $textcss .= ( !in_array ( $badgeStyle, $badgeTrnsVert ) ) ? ( ( $flipHorizontal && $flipVertical ) ? 'transform: scaleX(-1) scaleY(-1);' : ( ( $flipHorizontal ) ? 'transform: scaleX(-1);' : ( ( $flipVertical ) ? 'transform: scaleY(-1);' : '' ) ) ) : ( in_array ( $badgeStyle, $badgeTrnsRot ) ? ( ( $flipHorizontal && $flipVertical ) ? 'transform: scaleX(-1) scaleY(-1) rotate('.$badgeTrnsRotVal.'deg); top: auto;' : ( ( $flipHorizontal ) ? 'transform: scaleX(-1) rotate('.$badgeTrnsRotVal.'deg); top: auto;' : ( ( $flipVertical ) ? 'transform: scaleY(-1) rotate('.$badgeTrnsRotVal.'deg); top: auto;' : '' ) ) ) : ( ( $flipHorizontal && $flipVertical ) ? 'transform: scaleX(-1) scaleY(-1) translateY(-50%); top: auto;' : ( ( $flipHorizontal ) ? 'transform: scaleX(-1) translateY(-50%); top: auto;' : ( ( $flipVertical ) ? 'transform: scaleY(-1) translateY(-50%); top: auto;' : '' ) ) ) );

                $textcss .= ( $badgeStyle == 'bsfive' ) ? ( "background:rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].");width:" . $bsFiveWidth . "px;top:" . $bsFiveTop . "px;left:" . $bsFiveLeft . ";right:" . $bsFiveRight . ";" ) : '';

                $textcss .= ( $badgeStyle == 'bssix' ) ? ( "width:" . $bsSixWidth . "px;top:" . $bsSixTop . "px" ) : '';

                $css  = "opacity:".($opacity / 100).";font-size:".$fontSize."px;";
                $css .= ( !in_array ( $badgeStyle, $heightExclude ) ) ? "line-height:".$lineHeight."px;" : '';
                $css .= ( $badgeStyle == 'bsfifteen' ) ? "width:100%;" : "width:".$badgeWidth."px;";
                $css .= ( !in_array ( $badgeStyle, $heightExclude ) ) ? ( ( $badgeStyle == 'bsone' && $badgeHeight ) ? "height:".$badgeHeight."px;" : ( ( in_array ( $badgeStyle, $badgeHW ) && $badgeWidth ) ? "height:".$badgeWidth."px;" : '' ) ) : '';
                $css .= ( !in_array ( $badgeStyle, $borderRadiusExclude ) ) ? "border-radius: ".$borderTopLeft."px ".$borderTopRight."px ".$borderBottomRight."px ".$borderBottomLeft."px;" : '';
                
                $css .= ( !in_array ( $badgeStyle, $transformExclude ) ) ? "transform:rotateX(". ( $rotationX * 3.6 )."deg) rotateY(". ( $rotationY * 3.6 ) ."deg) rotateZ(". ( $rotationZ * 3.6 ) ."deg);" : '';

                $css .= ( $badgePosition == 'bpone' ) ? "top:".$posTop."px;bottom:auto;" : '';
                $css .= ( $badgePosition == 'bptwo' ) ? "bottom:".$posTop."px;top:auto;" : '';
                $css .= ( $badgeStyle == 'bsfifteen' ) ? "left:0px;" : ( ( $badgePositionHorizontal == 'bpthree' ) ? "left:".$posLeft."px;right:auto;" : '' );
                $css .= ( $badgeStyle == 'bsfifteen' ) ? "right:0px;" : ( ( $badgePositionHorizontal == 'bpfour' ) ? "right:".$posRight."px;left:auto;" : '' );

                $css .= ( $badgeStyle == 'bsfive' || $badgeStyle == 'bssix' ) ? "height:".$badgeWidth."px;" : "background:rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].");" ;

                $blockonecss = ( $badgeStyle == 'bssix' ) ? ( ( $badgePositionHorizontal == 'bpthree' ) ? ( "border-right: none; border-left: ".$badgeWidth."px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a']."); border-bottom: ".$badgeWidth."px solid transparent;" ) : ( "border-left: none; border-right: ".$badgeWidth."px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a']."); border-bottom: ".$badgeWidth."px solid transparent;") ) : '' ;

                /*
                * Border Width Calculations
                * 3.1.1
                */
                $BRNine_one     = $badgeWidth ? $badgeWidth * 0.75 : 45;
                $BRNine_two     = $badgeWidth ? $badgeWidth * 0.25 : 15;
                $BRNine_three   = $badgeWidth ? $badgeWidth * 0.5 : 30;
                $BRTen_one      = $badgeWidth ? $badgeWidth * 1.083 : 65;
                $BRTen_two      = $badgeWidth ? $badgeWidth * 0.42 : 25;
                $BRTwelve_one   = $badgeWidth ? $badgeWidth * .29 : 17.32;
                $BRTwelve_two   = $badgeWidth ? $badgeWidth * .58 : 34.65;
                $BRTwelve_three = $badgeWidth ? $badgeWidth * .5 : 30;
                $BRSeventeen_one  = $badgeWidth ? $badgeWidth * .7 : 42;
                $BRSeventeen_two  = $badgeWidth ? $badgeWidth * .4 : 24;

                $LSeventeen      = $badgeWidth ? $badgeWidth * .12 : 6;
                $RSeventeen      = $badgeWidth ? $badgeWidth * .12 : 6;

                /*
                * Width auto for badges bsone, bstwo, bsthree, bsfour 
                * ver 3.1.8
                */
                $css .= ( $dynmFlag && ( $badgeStyle == 'bsfour' || $badgeStyle == 'bsthree' || $badgeStyle == 'bstwo' || $badgeStyle == 'bsone' || $badgeStyle == 'bsfourteen' || $badgeStyle == 'bssixteen' ) ) || ( ( array_key_exists ( 'badgeWidth', $badgeOptions ) && $badgeOptions['badgeWidth'] == '' ) && ( $badgeStyle == 'bsfour' || $badgeStyle == 'bsthree' || $badgeStyle == 'bstwo' || $badgeStyle == 'bsone' || $badgeStyle == 'bsfourteen' || $badgeStyle == 'bssixteen' ) ) ? 'width:auto' : '';

                if ( $badgeStyle == 'bstwo' ) {
                    $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":after { background:rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; }";
                } else if ( $badgeStyle == 'bsthree' ) {
                    if ( $badgePositionHorizontal == 'bpthree' ) {
                        $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":before { border-left: 15px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-right: none; }";
                    } else {
                        $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":before { border-right: 15px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-left: none; }";
                    }
                } else if ( $badgeStyle == 'bsfour' ) {
                    $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":before { border-color:rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-left-color: transparent !important; }";
                } else if ( $badgeStyle == 'bsnine' ) {
                    $customStyle = ".".$customClass."{ display:inline-block; height:".$BRNine_one."px; } .".$customClass.":after { border-top:".$BRNine_two."px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-right: ".$BRNine_three."px solid transparent !important; border-left: ".$BRNine_three."px solid transparent !important; top:".$BRNine_one."px; }";
                } else if ( $badgeStyle == 'bsten' ) {
                    $customStyle = ".".$customClass."{display:inline-block;height:".$BRTen_one."px; border-radius: 3px 3px ".$BRTen_two."px ".$BRTen_two."px;}";
                } else if ( $badgeStyle == 'bstwelve' ) {
                    $customStyle = ".".$customClass."{display:inline-block; height: ".$BRTwelve_two."px;} .".$customClass.":before { top: -".$BRTwelve_one."px; border-bottom: ".$BRTwelve_one."px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-left: ".$BRTwelve_three."px solid transparent; border-right: ".$BRTwelve_three."px solid transparent; } .".$customClass.":after { bottom: -".$BRTwelve_one."px; border-top: ".$BRTwelve_one."px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-left: ".$BRTwelve_three."px solid transparent; border-right: ".$BRTwelve_three."px solid transparent; }";
                } else if ( $badgeStyle == 'bsfourteen' ) {
                    $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":before, .".$customClass.":after { background:rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; }";
                } else if ( $badgeStyle == 'bssixteen' ) {
                    $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":before, .".$customClass.":after { background: rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-color:rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important;}";
                } else if ( $badgeStyle == 'bsseventeen' ) {
                    $customStyle = ".".$customClass."{display:inline-block;} .".$customClass.":before, .".$customClass.":after { border-bottom: ".$BRSeventeen_one."px solid rgba(".$badgeColor['r'].", ".$badgeColor['g'].", ".$badgeColor['b'].", ".$badgeColor['a'].") !important; border-left: ".$BRSeventeen_two."px solid transparent !important; border-right:".$BRSeventeen_two."px solid transparent !important; top:".$BRSeventeen_one."px; } .".$customClass.":before { left:-".$LSeventeen."px !important; } .".$customClass.":after { right:-".$RSeventeen."px !important; }";
                } 
                
                if ( $css ) {
                    $customStyle .= ".".$customClass." { ".$css." }";
                }

                // Badge View
                if ( $badgeStyle == 'bsfive' ) {

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass. ' ' .$customClass.' '.$acoCustomClass.'" style="'.$css.'"><span class="acoplw-blockOne" style="'.$blockonecss.'"></span><span class="acoplw-blockTwo"></span><span class="acoplw-blockText" style="'.$textcss.'">'.$label.'</span></span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                } else if ( $badgeStyle == 'bssix' ) {

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass. ' ' .$customClass.' '.$badgeListingHide.' '.$acoCustomClass.'" style="'.$css.'"><span class="acoplw-blockOne" style="'.$blockonecss.'"></span><span class="acoplw-blockTwo"></span><span class="acoplw-blockText" style="'.$textcss.'">'.$label.'</span></span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                } else if ( $badgeStyle == 'bseleven' ) {

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass.' '.$badgeListingHide.' '.$acoCustomClass.'" style="'.$css.'">
                                <span class="acoplw-blockwrap">
                                    <span class="acoplw-firstblock"></span>
                                    <span class="acoplw-secondblock"></span>
                                    <span class="acoplw-thirdblock"></span>
                                </span>
                                <span class="acoplw-blockText" style="'.$textcss.'">'.$label.'</span>
                            </span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                } else {

                    // $badge = $useJqueryPos ? '<span class="'.$badgeListingHide.'">' : '';
                    $badge = '<span class="'.$badgeCSSClass. ' ' .$customClass.' '.$badgeListingHide.' '.$acoCustomClass.'" style="'.$css.'"><span class="acoplw-blockText" style="'.$textcss.'">'.$label.'</span></span>';
                    // $badge .= $useJqueryPos ? '</span>' : '';

                }
                // End Badge View

                $this->acoplwBadges[$productID][$badgeID] = $badge;
                $this->customStyles[$badgeID] = $customStyle;

            } 

        }

    }

    public function acoplwBadgeWCBlock ( $html, $data, $product ) {

        global $post; 
        $productID      = $product->get_ID();
        $productThumb   = '';
        $textstatus     = true;

        // Load active badges
        $this->load_badges(); 

        if ( $this->active_badges == null )
            return $html;

        foreach ( $this->active_badges as $k => $badge ) {  

            $badgeID = $badge['id'];

            /* 
            * Checking Dynamic Pricing Settings
            * ver @ 3.2.3
            */
            $preview_options    = get_post_meta ( $badgeID, 'badge_preview_options', true ) ? get_post_meta ( $badgeID, 'badge_preview_options', true ) : [];
            $dynamicPR          = function_exists ('AWDP') ? ( array_key_exists( 'pricing_rule', $preview_options ) ? $preview_options['pricing_rule'] : false ) : false;
            $wdp_filecheck      = defined('AWDP_FILE') ? realpath(plugin_dir_path(AWDP_FILE)) . DIRECTORY_SEPARATOR . 'includes\class-awdp-plwsupport.php' : ''; 
            if ( $dynamicPR && $wdp_filecheck && file_exists ( $wdp_filecheck ) ) {
                include_once($wdp_filecheck); // including dynamic pricing file
                $selectedRule   = array_key_exists ( 'selected_rule', $preview_options ) ? $preview_options['selected_rule'] : '';
                $awdp       = new AWDP_plwSupport();
                if ( !$awdp->plw_check ( $selectedRule, $productID ) ) {
                    continue;
                }
            } else {
                // Get Product List
                if ( !$this->check_in_product_list ( $productID, $badgeID ) ) {
                    continue;
                }
            }

            if ( array_key_exists ( $badgeID, $this->pScheduleStatus ) && $this->pScheduleStatus[$badgeID] ) {
                if ( !$this->onSaleScheduleList( $productID, $badgeID ) ){
                    continue;
                }
            } 

            $this->acoplwSaleBadge ( $productThumb, $productID, $badgeID );

        } 
        
        // Get all badges
        $acoplwActiveBadges = array_key_exists ( $productID, $this->acoplwBadges ) ? $this->acoplwBadges[$productID] : ''; 
        if ( $acoplwActiveBadges ) { 
            $badge = '';
            foreach ( $acoplwActiveBadges as $acoplwActiveBadge ) {
                $badge = $badge . $acoplwActiveBadge;
            }
            $productThumb = '<span class="acoplw-badge acoplw-textBlock">' . $badge . '</span>';
        } 

        return "<li class=\"wc-block-grid__product\"> 
				<a href=\"{$data->permalink}\" class=\"wc-block-grid__product-link\">
                    {$productThumb}
					{$data->image}
					{$data->title}
				</a>
				{$data->badge}
				{$data->price}
				{$data->rating}
				{$data->button}
			</li>";

    }

    // Load Active Badges
    public function load_badges()
    {

        if ( $this->active_badges === false ) {

            // Get wordpress timezone settings
            $gmt_offset         = get_option('gmt_offset');
            $timezone_string    = get_option('timezone_string');

            if ($timezone_string) {
                $datenow    = new DateTime(current_time('mysql'), new DateTimeZone($timezone_string));
            } else {
                $min        = 60 * get_option('gmt_offset');
                $sign       = $min < 0 ? "-" : "+";
                $absmin     = abs($min);
                $tz         = sprintf("%s%02d%02d", $sign, $absmin / 60, $absmin % 60);
                $datenow    = new DateTime(current_time('mysql'), new DateTimeZone($tz));
            }

            // Converting to UTC+000 (moment isoString timezone)
            $datenow->setTimezone(new DateTimeZone('+000'));
            $datenow        = $datenow->format('Y-m-d H:i:s');
            $stop_date      = date('Y-m-d H:i:s', strtotime($datenow . ' +1 day'));

            $day            = date("l");
            $crntTime       = gmdate('H:i');
            $current_time   = strtotime($crntTime);  

            $acoplw_badge_args = array(
                'post_type' => ACOPLW_POST_TYPE,
                'fields' => 'ids',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'badge_status',
                        'value' => 1,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => 'badge_start_date',
                        'value' => $datenow,
                        'compare' => '<=',
                        'type' => 'DATETIME'
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'badge_end_date',
                            'value' => $datenow,
                            'compare' => '>=',
                            'type' => 'DATETIME'
                        ),
                        array(
                            'key' => 'badge_end_date',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'badge_end_date',
                            'value' => '',
                            'compare' => '=',
                        ),
                    )
                )
            ); 

            $acoplw_badge_rules     = get_posts($acoplw_badge_args); 
            $acoplw_active_badges   = $check_rules = array();

            // Multi Lang
            $checkML                = call_user_func ( array ( new ACOPLW_ML(), 'is_default_lan' ), '' );
            $currentLang            = !$checkML ? call_user_func ( array ( new ACOPLW_ML(), 'current_language' ), '' ) : '';

            if ( $acoplw_badge_rules ) {

                foreach ( $acoplw_badge_rules as $acoplwID ) {

                    $pschedule          = get_post_meta($acoplwID, 'badge_use_pschedule', true);
                    
                    // $label_options      = get_post_meta($acoplwID, 'badge_label_options', true);
                    // $style_options      = get_post_meta($acoplwID, 'badge_style_options', true);
                    // $position_options   = get_post_meta($acoplwID, 'badge_position_options', true);
                    // $preview_options    = get_post_meta($acoplwID, 'badge_preview_options', true);

                    $label_options      = get_post_meta($acoplwID, 'badge_label_options', true) ? get_post_meta($acoplwID, 'badge_label_options', true) : [];
                    $style_options      = get_post_meta($acoplwID, 'badge_style_options', true) ? get_post_meta($acoplwID, 'badge_style_options', true) : [];
                    $position_options   = get_post_meta($acoplwID, 'badge_position_options', true) ? get_post_meta($acoplwID, 'badge_position_options', true) : [];
                    $preview_options    = get_post_meta($acoplwID, 'badge_preview_options', true) ? get_post_meta($acoplwID, 'badge_preview_options', true) : [];
                    $advBadgeOptions    = get_post_meta($acoplwID, 'badge_advance_options', true) ? get_post_meta($acoplwID, 'badge_advance_options', true) : [];
                    $advBadgeOptions    = maybe_unserialize($advBadgeOptions);
                    $scheduleOptions    = get_post_meta($acoplwID, 'badge_schedule_options', true) ? get_post_meta($acoplwID, 'badge_schedule_options', true) : [];
                    $scheduleOptions    = maybe_unserialize($scheduleOptions);
                    $timer_options      = get_post_meta($acoplwID, 'badge_timer_options', true) ? get_post_meta($acoplwID, 'badge_timer_options', true) : [];
                    $timer_options      = maybe_unserialize($timer_options);
                    $timerStart         = get_post_meta($acoplwID, 'badge_timing_start_date', true) ? get_post_meta($acoplwID, 'badge_timing_start_date', true) : '';
                    $timerEnd           = get_post_meta($acoplwID, 'badge_timing_end_date', true) ? get_post_meta($acoplwID, 'badge_timing_end_date', true) : '';

                    $onSaleProducts     = array_key_exists ( 'assignAll', $preview_options ) ? $preview_options['assignAll'] : '';

                    // WeekDay
                    $weekday_selection  = get_post_meta($acoplwID, 'badge_weekday_schedules', true) ? get_post_meta($acoplwID, 'badge_weekday_schedules', true) : [];
                    $weekday_selection  = maybe_unserialize($weekday_selection);
                    $weekday_status     = ( !empty ( $weekday_selection ) && array_key_exists ( 'scheduleWeekday', $weekday_selection ) ) ? $weekday_selection['scheduleWeekday'] : false;
                    $weekday_days       = ( !empty ( $weekday_selection ) && array_key_exists ( 'scheduleDays', $weekday_selection ) ) ? unserialize ( $weekday_selection['scheduleDays'] ) : false;
                    $weekday_starttime  = ( !empty ( $weekday_selection ) && array_key_exists ( 'startTime', $weekday_selection ) ) ? strtotime ( $weekday_selection['startTime'] ) : false;
                    $weekday_endtime    = ( !empty ( $weekday_selection ) && array_key_exists ( 'endTime', $weekday_selection ) ) ? strtotime ( $weekday_selection['endTime'] ) : false;

                    if ( $pschedule && $onSaleProducts ) { // WC Sale Schedule

                        if ( !in_array( $acoplwID, $check_rules ) ) {

                            $this->pScheduleStatus[$acoplwID] = true;
                            $check_rules[] = $acoplwID; // remove repeated entry - single rule

                            // Multi Lang
                            if ( $currentLang ) { 

                                $langLabel          = array_key_exists ( 'badgeLabelLang', $label_options ) ? $label_options['badgeLabelLang'] : [];
                                $MLBadgeLabel       = !empty ( $langLabel ) ? ( array_key_exists ( $currentLang, $langLabel ) ? $langLabel[$currentLang] : $label_options['badgeLabel'] ) : ( ( $label_options['badgeLabel'] != '' ) ? $label_options['badgeLabel'] : get_the_title ( $acoplwID ) );

                                $advLabel           = array_key_exists ( 'advLangOptions', $advBadgeOptions ) ? $advBadgeOptions['advLangOptions'] : [];
                                $advLabel           = array_key_exists ( 'advLangOptions', $advBadgeOptions ) ? $advBadgeOptions['advLangOptions'] : [];
                                $advtoptext         = !empty ( $advLabel ) ? ( array_key_exists ( $currentLang, $advLabel ) ? $advLabel[$currentLang]['advtoptext'] : $advBadgeOptions['advtoptext'] ) : $advBadgeOptions['advtoptext'];
                                $advbottomtext      = !empty ( $advLabel ) ? ( array_key_exists ( $currentLang, $advLabel ) ? $advLabel[$currentLang]['advbottomtext'] : $advBadgeOptions['advbottomtext'] ) : $advBadgeOptions['advbottomtext'];
                
                                $timerLabel         = array_key_exists ( 'timerLangOptions', $timer_options ) ? $timer_options['timerLangOptions'] : [];
                                $timerDayLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerDayLabel'] : $timer_options['timerDayLabel'] ) : $timer_options['timerDayLabel']; 
                                $timerHrsLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerHrsLabel'] : $timer_options['timerHrsLabel'] ) : $timer_options['timerHrsLabel']; 
                                $timerMinLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerMinLabel'] : $timer_options['timerMinLabel'] ) : $timer_options['timerMinLabel'];
                                $timerSecLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerSecLabel'] : $timer_options['timerSecLabel'] ) : $timer_options['timerSecLabel']; 

                            } else {

                                $MLBadgeLabel       = array_key_exists ( 'badgeLabel', $label_options ) ? $label_options['badgeLabel'] : 'Sale';

                                $advtoptext         = array_key_exists ( 'advtoptext', $advBadgeOptions ) ? $advBadgeOptions['advtoptext'] : '';
                                $advbottomtext      = array_key_exists ( 'advbottomtext', $advBadgeOptions ) ? $advBadgeOptions['advbottomtext'] : '';

                                $timerDayLabel      = array_key_exists ( 'timerDayLabel', $timer_options ) ? $timer_options['timerDayLabel'] : 'Day'; 
                                $timerHrsLabel      = array_key_exists ( 'timerHrsLabel', $timer_options ) ? $timer_options['timerHrsLabel'] : 'Hrs'; 
                                $timerMinLabel      = array_key_exists ( 'timerMinLabel', $timer_options ) ? $timer_options['timerMinLabel'] : 'Min'; 
                                $timerSecLabel      = array_key_exists ( 'timerSecLabel', $timer_options ) ? $timer_options['timerSecLabel'] : 'Sec';

                            } 

                            $acoplw_active_badges[$acoplwID] = array(

                                'id'                        => $acoplwID,

                                'label'                     => $MLBadgeLabel,
                                'labelColor'                => array_key_exists ( 'badgeLabelColor', $label_options ) ? $label_options['badgeLabelColor'] : '',
                                'fontSize'                  => array_key_exists ( 'fontSize', $label_options ) ? $label_options['fontSize'] : '',
                                'lineHeight'                => array_key_exists ( 'lineHeight', $label_options ) ? $label_options['lineHeight'] : '',

                                'badgeStyle'                => array_key_exists ( 'badgeStyle', $style_options ) ? $style_options['badgeStyle'] : '',
                                'badgeType'                 => array_key_exists ( 'badgeType', $style_options ) ? $style_options['badgeType'] : '',
                                'badgeColor'                => array_key_exists ( 'badgeColor', $style_options ) ? $style_options['badgeColor'] : '',
                                'badgeWidth'                => array_key_exists ( 'badgeWidth', $style_options ) ? $style_options['badgeWidth'] : '',
                                'badgeHeight'               => array_key_exists ( 'badgeHeight', $style_options ) ? $style_options['badgeHeight'] : '',
                                'borderTopLeft'             => array_key_exists ( 'borderTopLeft', $style_options ) ? $style_options['borderTopLeft'] : '',
                                'borderTopRight'            => array_key_exists ( 'borderTopRight', $style_options ) ? $style_options['borderTopRight'] : '',
                                'borderBottomLeft'          => array_key_exists ( 'borderBottomLeft', $style_options ) ? $style_options['borderBottomLeft'] : '',
                                'borderBottomRight'         => array_key_exists ( 'borderBottomRight', $style_options ) ? $style_options['borderBottomRight'] : '',

                                'opacity'                   => array_key_exists ( 'opacity', $position_options ) ? $position_options['opacity'] : '',
                                'rotationX'                 => array_key_exists ( 'rotationX', $position_options ) ? $position_options['rotationX'] : '',
                                'rotationY'                 => array_key_exists ( 'rotationY', $position_options ) ? $position_options['rotationY'] : '',
                                'rotationZ'                 => array_key_exists ( 'rotationZ', $position_options ) ? $position_options['rotationZ'] : '',
                                'flipHorizontal'            => array_key_exists ( 'flipHorizontal', $position_options ) ? $position_options['flipHorizontal'] : '',
                                'flipVertical'              => array_key_exists ( 'flipVertical', $position_options ) ? $position_options['flipVertical'] : '',
                                'badgePosition'             => array_key_exists ( 'badgePosition', $position_options ) ? $position_options['badgePosition'] : '',
                                'badgePositionHorizontal'   => array_key_exists ( 'badgePositionHorizontal', $position_options ) ? $position_options['badgePositionHorizontal'] : '',
                                'posTop'                    => array_key_exists ( 'posTop', $position_options ) ? $position_options['posTop'] : '',
                                'posBottom'                 => array_key_exists ( 'posBottom', $position_options ) ? $position_options['posBottom'] : '',
                                'posLeft'                   => array_key_exists ( 'posLeft', $position_options ) ? $position_options['posLeft'] : '',
                                'posRight'                  => array_key_exists ( 'posRight', $position_options ) ? $position_options['posRight'] : '',

                                'saleBadge'                 => array_key_exists ( 'assignAll', $preview_options ) ? $preview_options['assignAll'] : '',

                                'advBadgePrimaryColor'      => array_key_exists ( 'advBadgePrimaryColor', $advBadgeOptions ) ? $advBadgeOptions['advBadgePrimaryColor'] : '',
                                'advBadgePrimaryColorHex'   => array_key_exists ( 'advBadgePrimaryColorHex', $advBadgeOptions ) ? $advBadgeOptions['advBadgePrimaryColorHex'] : '',
                                'advBadgeSecondaryColor'    => array_key_exists ( 'advBadgeSecondaryColor', $advBadgeOptions ) ? $advBadgeOptions['advBadgeSecondaryColor'] : '',
                                'advBadgeSecondaryColorHex' => array_key_exists ( 'advBadgeSecondaryColorHex', $advBadgeOptions ) ? $advBadgeOptions['advBadgeSecondaryColorHex'] : '',
                                'advtoptext'                => $advtoptext,
                                'advTopTextColor'           => array_key_exists ( 'advTopTextColor', $advBadgeOptions ) ? $advBadgeOptions['advTopTextColor'] : '',
                                'advTopTextColorHex'        => array_key_exists ( 'advTopTextColorHex', $advBadgeOptions ) ? $advBadgeOptions['advTopTextColorHex'] : '',
                                'advbottomtext'             => $advbottomtext,
                                'advBottomTextColor'        => array_key_exists ( 'advBottomTextColor', $advBadgeOptions ) ? $advBadgeOptions['advBottomTextColor'] : '',
                                'advBottomTextColorHex'     => array_key_exists ( 'advBottomTextColorHex', $advBadgeOptions ) ? $advBadgeOptions['advBottomTextColorHex'] : '',
                                'advBadgeCat'               => array_key_exists ( 'advBadgeCat', $advBadgeOptions ) ? $advBadgeOptions['advBadgeCat'] : '',
                                'enableDynamicValue'        => array_key_exists ( 'enableDynamicValue', $advBadgeOptions ) ? $advBadgeOptions['enableDynamicValue'] : '',

                                'enableTimer'               => array_key_exists ( 'enableTimer', $timer_options ) ? $timer_options['enableTimer'] : '',
                                'timerDayLabel'             => $timerDayLabel,
                                'timerHrsLabel'             => $timerHrsLabel,
                                'timerMinLabel'             => $timerMinLabel,
                                'timerSecLabel'             => $timerSecLabel,
                                'enableProductTimer'        => array_key_exists ( 'enableProductTimer', $timer_options ) ? $timer_options['enableProductTimer'] : '',
                                'timerPosRight'             => array_key_exists ( 'timerPosRight', $timer_options ) ? $timer_options['timerPosRight'] : '',
                                'timerPosLeft'              => array_key_exists ( 'timerPosLeft', $timer_options ) ? $timer_options['timerPosLeft'] : '',
                                'timerPosBottom'            => array_key_exists ( 'timerPosBottom', $timer_options ) ? $timer_options['timerPosBottom'] : '',
                                'timerPosTop'               => array_key_exists ( 'timerPosTop', $timer_options ) ? $timer_options['timerPosTop'] : '',
                                'timerPositionHorizontal'   => array_key_exists ( 'timerPositionHorizontal', $timer_options ) ? $timer_options['timerPositionHorizontal'] : '',
                                'timerPosition'             => array_key_exists ( 'timerPosition', $timer_options ) ? $timer_options['timerPosition'] : '',
                                'timerOpacity'              => array_key_exists ( 'timerOpacity', $timer_options ) ? $timer_options['timerOpacity'] : '',
                                'timerlineHeight'           => array_key_exists ( 'timerlineHeight', $timer_options ) ? $timer_options['timerlineHeight'] : '',
                                'timerfontSize'             => array_key_exists ( 'timerfontSize', $timer_options ) ? $timer_options['timerfontSize'] : '',
                                'timerLabelColor'           => array_key_exists ( 'timerLabelColor', $timer_options ) ? $timer_options['timerLabelColor'] : '',
                                'timerLabelColorHex'        => array_key_exists ( 'timerLabelColorHex', $timer_options ) ? $timer_options['timerLabelColorHex'] : '',
                                'timerBorderColor'          => array_key_exists ( 'timerBorderColor', $timer_options ) ? $timer_options['timerBorderColor'] : '',
                                'timerBorderColorHex'       => array_key_exists ( 'timerBorderColorHex', $timer_options ) ? $timer_options['timerBorderColorHex'] : '',
                                'timerPrefixLabel'          => array_key_exists ( 'timerPrefixLabel', $timer_options ) ? $timer_options['timerPrefixLabel'] : '',
                                'timerPrefixfontSize'       => array_key_exists ( 'timerPrefixfontSize', $timer_options ) ? $timer_options['timerPrefixfontSize'] : '',
                                'timerPrefixlineHeight'     => array_key_exists ( 'timerPrefixlineHeight', $timer_options ) ? $timer_options['timerPrefixlineHeight'] : '',
                                'timerStart'                => $timerStart,
                                'timerEnd'                  => $timerEnd,

                            );

                        }

                    } else {

                        if ( !empty ( $scheduleOptions ) && array_key_exists ( 'enableDPSchdule', $scheduleOptions ) && $scheduleOptions['enableDPSchdule'] ) { // Checking if WDP Schedule is active
                            $wdpRuleID = $scheduleOptions['selectedDPRule'];
                            $schedules = unserialize ( get_post_meta ( $wdpRuleID, 'discount_schedules', true ) );
                        } else {
                            $schedules = unserialize ( get_post_meta ( $acoplwID, 'badge_schedules', true ) ); 
                        }

                        $this->pScheduleStatus[$acoplwID] = false;

                        if ( $schedules ) {

                            foreach ( $schedules as $schedule ) {

                                $acoplw_start_date = $schedule['start_date'];
                                $acoplw_end_start = $schedule['end_date'] ? $schedule['end_date'] : $stop_date;

                                /* 
                                * @ver 3.1.8
                                * Added WeekDays Check
                                */

                                if ( ( ( $acoplw_start_date <= $datenow ) && ( $acoplw_end_start >= $datenow ) && !in_array( $acoplwID, $check_rules ) ) && ( $weekday_status != 1 || ( $weekday_status == 1 && ( in_array ( $day, $weekday_days ) && ( $current_time >= $weekday_starttime && $current_time <= $weekday_endtime ) ) || ( empty ( $weekday_days ) || ( !empty( $weekday_days ) && ( $weekday_days[0] == 'null' || $weekday_days[0] == '' ) ) ) ) ) ) {

                                    $check_rules[] = $acoplwID; // remove repeated entry - single rule

                                    // Multi Lang
                                    if ( $currentLang ) { 

                                        $langLabel          = array_key_exists ( 'badgeLabelLang', $label_options ) ? $label_options['badgeLabelLang'] : [];
                                        $MLBadgeLabel       = !empty ( $langLabel ) ? ( array_key_exists ( $currentLang, $langLabel ) ? $langLabel[$currentLang] : $label_options['badgeLabel'] ) : ( ( $label_options['badgeLabel'] != '' ) ? $label_options['badgeLabel'] : get_the_title ( $acoplwID ) );

                                        $advLabel           = array_key_exists ( 'advLangOptions', $advBadgeOptions ) ? $advBadgeOptions['advLangOptions'] : [];
                                        $advLabel           = array_key_exists ( 'advLangOptions', $advBadgeOptions ) ? $advBadgeOptions['advLangOptions'] : [];
                                        $advtoptext         = !empty ( $advLabel ) ? ( ( array_key_exists ( $currentLang, $advLabel ) && array_key_exists ( 'advtoptext', $advLabel[$currentLang] ) )  ? $advLabel[$currentLang]['advtoptext'] : $advBadgeOptions['advtoptext'] ) : $advBadgeOptions['advtoptext'];
                                        $advbottomtext      = !empty ( $advLabel ) ? ( array_key_exists ( $currentLang, $advLabel ) ? $advLabel[$currentLang]['advbottomtext'] : $advBadgeOptions['advbottomtext'] ) : $advBadgeOptions['advbottomtext'];
                        
                                        $timerLabel         = array_key_exists ( 'timerLangOptions', $timer_options ) ? $timer_options['timerLangOptions'] : [];
                                        $timerDayLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerDayLabel'] : $timer_options['timerDayLabel'] ) : $timer_options['timerDayLabel']; 
                                        $timerHrsLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerHrsLabel'] : $timer_options['timerHrsLabel'] ) : $timer_options['timerHrsLabel']; 
                                        $timerMinLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerMinLabel'] : $timer_options['timerMinLabel'] ) : $timer_options['timerMinLabel'];
                                        $timerSecLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerSecLabel'] : $timer_options['timerSecLabel'] ) : $timer_options['timerSecLabel']; 

                                    } else {

                                        $MLBadgeLabel       = array_key_exists ( 'badgeLabel', $label_options ) ? $label_options['badgeLabel'] : 'Sale';

                                        $advtoptext         = array_key_exists ( 'advtoptext', $advBadgeOptions ) ? $advBadgeOptions['advtoptext'] : '';
                                        $advbottomtext      = array_key_exists ( 'advbottomtext', $advBadgeOptions ) ? $advBadgeOptions['advbottomtext'] : '';

                                        $timerDayLabel      = array_key_exists ( 'timerDayLabel', $timer_options ) ? $timer_options['timerDayLabel'] : '';
                                        $timerHrsLabel      = array_key_exists ( 'timerHrsLabel', $timer_options ) ? $timer_options['timerHrsLabel'] : ''; 
                                        $timerMinLabel      = array_key_exists ( 'timerMinLabel', $timer_options ) ? $timer_options['timerMinLabel'] : ''; 
                                        $timerSecLabel      = array_key_exists ( 'timerSecLabel', $timer_options ) ? $timer_options['timerSecLabel'] : '';

                                    } 

                                    $acoplw_active_badges[$acoplwID] = array(

                                        'id'                        => $acoplwID,

                                        'label'                     => $MLBadgeLabel,
                                        'labelColor'                => array_key_exists ( 'badgeLabelColor', $label_options ) ? $label_options['badgeLabelColor'] : '',
                                        'fontSize'                  => array_key_exists ( 'fontSize', $label_options ) ? $label_options['fontSize'] : '',
                                        'lineHeight'                => array_key_exists ( 'lineHeight', $label_options ) ? $label_options['lineHeight'] : '',

                                        'badgeStyle'                => array_key_exists ( 'badgeStyle', $style_options ) ? $style_options['badgeStyle'] : '',
                                        'badgeType'                 => array_key_exists ( 'badgeType', $style_options ) ? $style_options['badgeType'] : '',
                                        'badgeColor'                => array_key_exists ( 'badgeColor', $style_options ) ? $style_options['badgeColor'] : '',
                                        'badgeWidth'                => array_key_exists ( 'badgeWidth', $style_options ) ? $style_options['badgeWidth'] : '',
                                        'badgeHeight'               => array_key_exists ( 'badgeHeight', $style_options ) ? $style_options['badgeHeight'] : '',
                                        'borderTopLeft'             => array_key_exists ( 'borderTopLeft', $style_options ) ? $style_options['borderTopLeft'] : '',
                                        'borderTopRight'            => array_key_exists ( 'borderTopRight', $style_options ) ? $style_options['borderTopRight'] : '',
                                        'borderBottomLeft'          => array_key_exists ( 'borderBottomLeft', $style_options ) ? $style_options['borderBottomLeft'] : '',
                                        'borderBottomRight'         => array_key_exists ( 'borderBottomRight', $style_options ) ? $style_options['borderBottomRight'] : '',

                                        'opacity'                   => array_key_exists ( 'opacity', $position_options ) ? $position_options['opacity'] : '',
                                        'rotationX'                 => array_key_exists ( 'rotationX', $position_options ) ? $position_options['rotationX'] : '',
                                        'rotationY'                 => array_key_exists ( 'rotationY', $position_options ) ? $position_options['rotationY'] : '',
                                        'rotationZ'                 => array_key_exists ( 'rotationZ', $position_options ) ? $position_options['rotationZ'] : '',
                                        'flipHorizontal'            => array_key_exists ( 'flipHorizontal', $position_options ) ? $position_options['flipHorizontal'] : '',
                                        'flipVertical'              => array_key_exists ( 'flipVertical', $position_options ) ? $position_options['flipVertical'] : '',
                                        'badgePosition'             => array_key_exists ( 'badgePosition', $position_options ) ? $position_options['badgePosition'] : '',
                                        'badgePositionHorizontal'   => array_key_exists ( 'badgePositionHorizontal', $position_options ) ? $position_options['badgePositionHorizontal'] : '',
                                        'posTop'                    => array_key_exists ( 'posTop', $position_options ) ? $position_options['posTop'] : '',
                                        'posBottom'                 => array_key_exists ( 'posBottom', $position_options ) ? $position_options['posBottom'] : '',
                                        'posLeft'                   => array_key_exists ( 'posLeft', $position_options ) ? $position_options['posLeft'] : '',
                                        'posRight'                  => array_key_exists ( 'posRight', $position_options ) ? $position_options['posRight'] : '',

                                        'saleBadge'                 => array_key_exists ( 'assignAll', $preview_options ) ? $preview_options['assignAll'] : '',
                                        
                                        'advBadgePrimaryColor'      => array_key_exists ( 'advBadgePrimaryColor', $advBadgeOptions )  ? $advBadgeOptions['advBadgePrimaryColor'] : '',
                                        'advBadgePrimaryColorHex'   => array_key_exists ( 'advBadgePrimaryColorHex', $advBadgeOptions )  ? $advBadgeOptions['advBadgePrimaryColorHex'] : '',
                                        'advBadgeSecondaryColor'    => array_key_exists ( 'advBadgeSecondaryColor', $advBadgeOptions )  ? $advBadgeOptions['advBadgeSecondaryColor'] : '',
                                        'advBadgeSecondaryColorHex' => array_key_exists ( 'advBadgeSecondaryColorHex', $advBadgeOptions )  ? $advBadgeOptions['advBadgeSecondaryColorHex'] : '',
                                        'advtoptext'                => $advtoptext,
                                        'advTopTextColor'           => array_key_exists ( 'advTopTextColor', $advBadgeOptions )  ? $advBadgeOptions['advTopTextColor'] : '',
                                        'advTopTextColorHex'        => array_key_exists ( 'advTopTextColorHex', $advBadgeOptions )  ? $advBadgeOptions['advTopTextColorHex'] : '',
                                        'advbottomtext'             => $advbottomtext,
                                        'advBottomTextColor'        => array_key_exists ( 'advBottomTextColor', $advBadgeOptions )  ? $advBadgeOptions['advBottomTextColor'] : '',
                                        'advBottomTextColorHex'     => array_key_exists ( 'advBottomTextColorHex', $advBadgeOptions )  ? $advBadgeOptions['advBottomTextColorHex'] : '',
                                        'advBadgeCat'               => array_key_exists ( 'advBadgeCat', $advBadgeOptions )  ? $advBadgeOptions['advBadgeCat'] : '',
                                        'enableDynamicValue'        => array_key_exists ( 'enableDynamicValue', $advBadgeOptions )  ? $advBadgeOptions['enableDynamicValue'] : '',

                                        'enableTimer'               => array_key_exists ( 'enableTimer', $timer_options )  ? $timer_options['enableTimer'] : '',
                                        'timerDayLabel'             => $timerDayLabel,
                                        'timerHrsLabel'             => $timerHrsLabel,
                                        'timerMinLabel'             => $timerMinLabel,
                                        'timerSecLabel'             => $timerSecLabel,
                                        'enableProductTimer'        => array_key_exists ( 'enableProductTimer', $timer_options )  ? $timer_options['enableProductTimer'] : '',
                                        'timerPosRight'             => array_key_exists ( 'timerPosRight', $timer_options )  ? $timer_options['timerPosRight'] : '',
                                        'timerPosLeft'              => array_key_exists ( 'timerPosLeft', $timer_options )  ? $timer_options['timerPosLeft'] : '',
                                        'timerPosBottom'            => array_key_exists ( 'timerPosBottom', $timer_options )  ? $timer_options['timerPosBottom'] : '',
                                        'timerPosTop'               => array_key_exists ( 'timerPosTop', $timer_options )  ? $timer_options['timerPosTop'] : '',
                                        'timerPositionHorizontal'   => array_key_exists ( 'timerPositionHorizontal', $timer_options )  ? $timer_options['timerPositionHorizontal'] : '',
                                        'timerPosition'             => array_key_exists ( 'timerPosition', $timer_options )  ? $timer_options['timerPosition'] : '',
                                        'timerOpacity'              => array_key_exists ( 'timerOpacity', $timer_options )  ? $timer_options['timerOpacity'] : '',
                                        'timerlineHeight'           => array_key_exists ( 'timerlineHeightNew', $timer_options )  ? $timer_options['timerlineHeightNew'] : '',
                                        'timerfontSize'             => array_key_exists ( 'timerfontSizeNew', $timer_options )  ? $timer_options['timerfontSizeNew'] : '',
                                        'timerLabelColor'           => array_key_exists ( 'timerLabelColorNew', $timer_options )  ? $timer_options['timerLabelColorNew'] : '',
                                        'timerLabelColorHex'        => array_key_exists ( 'timerLabelColorHexNew', $timer_options )  ? $timer_options['timerLabelColorHexNew'] : '',
                                        'timerBorderColor'          => array_key_exists ( 'timerBorderColor', $timer_options )  ? $timer_options['timerBorderColor'] : '',
                                        'timerBorderColorHex'       => array_key_exists ( 'timerBorderColorHex', $timer_options )  ? $timer_options['timerBorderColorHex'] : '',
                                        'timerPrefixLabel'          => array_key_exists ( 'timerPrefixLabel', $timer_options )  ? $timer_options['timerPrefixLabel'] : '',
                                        'timerPrefixfontSize'       => array_key_exists ( 'timerPrefixfontSize', $timer_options )  ? $timer_options['timerPrefixfontSize'] : '',
                                        'timerPrefixlineHeight'     => array_key_exists ( 'timerPrefixlineHeight', $timer_options )  ? $timer_options['timerPrefixlineHeight'] : '',
                                        'timerStart'                => $timerStart,
                                        'timerEnd'                  => $timerEnd,

                                    );

                                }

                            }

                        }

                    }

                }

            }
            
            $this->active_badges = $acoplw_active_badges;

        }

    }

    public function check_in_product_list ( $productID, $badgeID )
    {

        $productListSelected    = get_post_meta( $badgeID, 'badge_selected_list', true );
        $preview_options        = get_post_meta( $badgeID, 'badge_preview_options', true ) ? get_post_meta( $badgeID, 'badge_preview_options', true ) : [];

        $productsOnSale         = ( !empty ( $preview_options ) && array_key_exists ( 'assignAll', $preview_options ) ) ? $preview_options['assignAll'] : '';
        $outOfStock             = ( !empty ( $preview_options ) && array_key_exists ( 'outOfStock', $preview_options ) ) ? $preview_options['outOfStock'] : ''; 
        $recentProducts         = ( !empty ( $preview_options ) && array_key_exists ( 'recentProducts', $preview_options ) ) ? $preview_options['recentProducts'] : ''; 
        $stockProducts          = ( !empty ( $preview_options ) && array_key_exists ( 'stockProducts', $preview_options ) ) ? $preview_options['stockProducts'] : ''; 

        $customPLStatus         = array_key_exists ( 'custom_pl', $preview_options ) ? $preview_options['custom_pl'] : false;
        $customPL               = array_key_exists ( 'customPL', $preview_options ) ? $preview_options['customPL'] : [];

        if ( $productsOnSale == true ) {

            return $this->check_product_on_sale( $productID );

        } else if ( $outOfStock == true ) {

            return $this->check_product_stock( $productID );

        } else if ( $recentProducts == true ) {

            return $this->check_recent_products( $productID );

        } else if ( $stockProducts == true ) {

            return $this->check_stock_products( $productID );

        } else { 
            
            if ( ( '' == $productListSelected || 0 == $productListSelected  ) && !$customPLStatus ) {

                return true;
    
            } else if ( $customPLStatus ) { 
    
                // Custom Product List
                // $pro_id     = ( $product->get_parent_id() == 0 ) ? $product->get_id() : $product->get_parent_id(); 
                $prodIDs    = [];   
                
                if ( !empty ( $customPL ) ) {
    
                    $plw_tax_query = $plw_prod_ids = $prodIDs = []; $taxcnt = 1;
                    foreach ( $customPL as $singlePL ) { 
                        foreach ( $singlePL['rules'] as $val ) {
                            if ( is_array ( $val ) && $val['rule']['value'] ) {
                                if ( $val['rule']['item'] == 'product_selection') {
                                    $plw_prod_ids = array_merge ( $plw_prod_ids, $val['rule']['value'] );
                                } else {
                                    if ( $taxcnt === 1 ) { $plw_tax_query = array('relation' => 'OR'); }
                                    $taxoperator = ( $val['rule']['condition'] === 'notin' ) ? 'NOT IN' : 'IN'; 
                                    $plw_tax_query[] = array(
                                        'taxonomy'  => $val['rule']['item'],
                                        'field'     => 'term_id',
                                        'terms'     => $val['rule']['value'],
                                        'operator'  => $taxoperator
                                    );
                                    $taxcnt++;
                                }
                            }
                        } 
                    }
    
                    if ( !empty($plw_tax_query) ) {

                        /*
                        *  Fixing Category/Tag filter issue in search result page
                        *  ver 1.5.5
                        **/

                        if( apply_filters('acoplw_taxonomy_filter',true) ) {
                            $args = array(
                                'post_type'         => ACOPLW_PRODUCTS,
                                'fields'            => 'ids',
                                'post_status'       => 'publish',
                                'posts_per_page'    => -1,
                                'tax_query'         => $plw_tax_query
                            );
                            $prodIDs    = get_posts ( $args );
                        } else {

                            global $wpdb;
                            $termId         = implode(',', array_map('intval', $plw_tax_query[0]['terms']));
                            $taxRelation    = $plw_tax_query['relation'];
                            $taxoperator    = $plw_tax_query[0]['operator'];
                            $taxonomy       = $plw_tax_query[0]['taxonomy'];
                       
                            $results = $wpdb->get_results( $wpdb->prepare(
                                    "SELECT p.ID
                                        FROM {$wpdb->prefix}posts p
                                        INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
                                        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                        INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
                                        WHERE p.post_type = 'product'
                                        AND p.post_status = 'publish'
                                        AND p.ID {$taxoperator} (
                                            SELECT tr1.object_id
                                            FROM {$wpdb->prefix}term_relationships tr1
                                            INNER JOIN {$wpdb->prefix}term_taxonomy tt1 ON tr1.term_taxonomy_id = tt1.term_taxonomy_id
                                            WHERE tt1.taxonomy = %s
                                            AND tt1.term_id IN ($termId)
                                        )",
                                        $taxonomy) );
                                      
                                      
                                $productIDs = array();
                                $i = 0;
                            foreach ($results as $result) {
                                $productIDs[]  = $result->ID; 
                                $i++;
                            }
                                $prodIDs = $productIDs;
                        }
                    }
                    $prodIDs	= !empty ( $plw_prod_ids ) ? array_merge ( $plw_prod_ids, $prodIDs ) : $prodIDs; 
    
                    return isset($prodIDs) && in_array($productID, $prodIDs);
    
                } else {
    
                    return false; // Return false if selection is empty
                    
                }
    
            } else {

                $this->set_product_list();
                return isset($this->product_lists[$productListSelected]) &&
                    in_array($productID, $this->product_lists[$productListSelected]);

            }

        }

    }

    // Products On Sale
    public function check_product_on_sale( $productID )
    {

        if ( false == $this->products_on_sale ) {
            
            global $wpdb;
            
            $acoplw_onsale_prods = $wpdb->get_results( "
                SELECT posts.ID as id, posts.post_parent as parent_id
                FROM {$wpdb->posts} AS posts
                INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
                INNER JOIN {$wpdb->postmeta} as meta ON posts.ID = meta.post_id
                WHERE posts.post_type IN ( 'product', 'product_variation' )
                AND posts.post_status = 'publish'
                AND lookup.onsale = 1 
                AND meta.meta_key LIKE '_stock_status'
                AND meta.meta_value IN ( 'instock', 'onbackorder' )
                AND posts.post_parent NOT IN (
                    SELECT ID FROM `$wpdb->posts` as posts
                    WHERE posts.post_type = 'product'
                    AND posts.post_parent = 0
                    AND posts.post_status != 'publish'
                )
                GROUP BY posts.ID
                " 
            );

            $prods_onSale = wp_parse_id_list( array_merge( wp_list_pluck( $acoplw_onsale_prods, 'id' ), array_diff( wp_list_pluck( $acoplw_onsale_prods, 'parent_id' ), array( 0 ) ) ) );
            
            $this->products_on_sale = $prods_onSale;

        }

        $onSaleIDs = $this->products_on_sale; 

        return in_array ( $productID, $onSaleIDs ) ? true : false;

    }

    // Out Of Stock 
    public function check_product_stock ( $productID )
    {

        // if ( false == $this->out_of_stock ) {

        //     $product = wc_get_product( $productID );
            
        //     global $wpdb;
            
        //     $acoplw_outoftock_prods = $wpdb->get_results( "
        //         SELECT posts.ID as id, posts.post_parent as parent_id
        //         FROM {$wpdb->posts} AS posts
        //         INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
        //         WHERE posts.post_type IN ( 'product', 'product_variation' )
        //         AND posts.post_status = 'publish'
        //         AND lookup.stock_status = 'outofstock'
        //         AND posts.post_parent NOT IN (
        //             SELECT ID FROM `$wpdb->posts` as posts
        //             WHERE posts.post_type = 'product'
        //             AND posts.post_parent = 0
        //             AND posts.post_status != 'publish'
        //         )
        //         GROUP BY posts.ID
        //         " 
        //     ); 

        //     $prods_outOfStock = wp_parse_id_list( array_merge( wp_list_pluck( $acoplw_outoftock_prods, 'id' ), array_diff( wp_list_pluck( $acoplw_outoftock_prods, 'parent_id' ), array( 0 ) ) ) );
            
        //     $this->out_of_stock = $prods_outOfStock;

        // }

        // $onSaleIDs = $this->out_of_stock; 

        /*
        * @ver 3.1.8
        * Fix - Out of stcok badge on variation
        */
        if ( $productID ) {

            $product = wc_get_product( $productID );

            if ( !$product ) return false;

            return ( !$product->is_in_stock() ) ? true : false;
        
        } else {

            return false;
            
        }

    }
  
    // Recent Products

    /*
     *  Added a hook for removing variation products in the recent product.
     *  ver 3.2.11
     **/
    public function check_recent_products ( $productID, $include_variations = true )
    {

        $include_variations = apply_filters('check_recent_variations', $include_variations);

        if ( false == $this->recent_products ) {

            $product        = wc_get_product( $productID );
            $daycount       = get_option('acoplw_recent_count') ? 'P'.get_option('acoplw_recent_count').'D' : 'P30D';
            
            $acoplw_date    = new DateTime("now");
            $acoplw_date->sub(new DateInterval($daycount));
            $acowply_day    = $acoplw_date->format('Y-m-d H:i:s');

            global $wpdb;

            $post_types = array('product');
            if ($include_variations) {
                $post_types[] = 'product_variation';
            }
            
            $acoplw_recent_prods = $wpdb->get_results( "
                SELECT posts.ID as id, posts.post_parent as parent_id
                FROM {$wpdb->posts} AS posts
                WHERE posts.post_type IN ('" . implode("','", $post_types) . "')
                AND posts.post_status = 'publish'
                AND posts.post_date >= '$acowply_day'
                GROUP BY posts.ID
                " 
            ); 

            $prods_recent = wp_parse_id_list( array_merge( wp_list_pluck( $acoplw_recent_prods, 'id' ), array_diff( wp_list_pluck( $acoplw_recent_prods, 'parent_id' ), array( 0 ) ) ) );
            
            $this->recent_products = $prods_recent;

        }

        return in_array ( $productID, $this->recent_products ) ? true : false;

    }


    // Limited Stock Products
    public function check_stock_products ( $productID )
    {

        if ( false == $this->stock_products ) {

            $product        = wc_get_product( $productID );
            $acoplw_stock   = get_option('acoplw_stock_count') ? get_option('acoplw_stock_count') : 10;

            global $wpdb;
            
            $acoplw_stock_prods = $wpdb->get_results( "
                SELECT posts.ID as id, posts.post_parent as parent_id
                FROM {$wpdb->posts} AS posts
                INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = posts.ID
                WHERE posts.post_type IN ( 'product', 'product_variation' )
                AND posts.post_status = 'publish'
                AND pm.meta_key IN ('_stock')
                AND pm.meta_value <= $acoplw_stock AND pm.meta_value > 0
                GROUP BY posts.ID
                " 
            ); 

            $prods_stock = wp_parse_id_list( array_merge( wp_list_pluck( $acoplw_stock_prods, 'id' ), array_diff( wp_list_pluck( $acoplw_stock_prods, 'parent_id' ), array( 0 ) ) ) );
            
            $this->stock_products = $prods_stock;

        }

        return in_array ( $productID, $this->stock_products ) ? true : false;

    }

    // Product List
    public function set_product_list()
    {

        if (false == $this->product_lists) {

            $checkML                = call_user_func ( array ( new ACOPLW_ML(), 'is_default_lan' ), '' );
            $currentLang            = !$checkML ? call_user_func ( array ( new ACOPLW_ML(), 'current_language' ), '' ) : 'default';

            if ( false === ( $product_lists = get_transient(ACOPLW_PRODUCTS_TRANSIENT_KEY) ) || get_transient(ACOPLW_PRODUCTS_LANG_TRANSIENT_KEY) != $currentLang ) {
                
                $post_type = ACOPLW_PRODUCT_LIST;
                global $wpdb;

                $product_lists = array();
                $lists = array_values ( array_diff ( array_filter ( $wpdb->get_col ( $wpdb->prepare ( 
                            "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
                            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                            WHERE pm.meta_key = '%s' 
                            AND p.post_status = '%s' 
                            AND p.post_type = '%s'", 'badge_selected_list', 'publish', ACOPLW_POST_TYPE ) ) ), array("null") ) );

                $post_ids = array_map ( function($value) { return (int)$value; }, $lists );

                foreach ($post_ids as $id) {

                    $list_type      = get_post_meta($id, 'list_type', true); 
                    $other_config   = get_post_meta($id, 'product_list_config', true) ? get_post_meta($id, 'product_list_config', true) : [];

                    $product_lists[$id] = array();

                    if ( 'dynamic_request' == $list_type ) {

                        $tax_rules          = array_key_exists ( 'rules', $other_config ) ? ($other_config['rules']) : [];
                        $tax_rules          = ($tax_rules && is_array($tax_rules) && !empty($tax_rules)) ? $tax_rules : false;
                        $excludedProducts   = ($other_config['excludedProducts']);
                        $tax_query          = [];

                        $args = array(
                            'post_type'         => ACOPLW_PRODUCTS,
                            'fields'            => 'ids',
                            'post_status'       => 'publish',
                            'posts_per_page'    => -1,
                        );

                        if ( $excludedProducts ) {
                            $args['post__not_in'] = $excludedProducts;
                        }

                        if ( false !== $tax_rules ) { 

                            if ( isset($tax_rules[0]['rules']) && is_array($tax_rules[0]['rules']) ) {
                                $selected_tax = array_filter($tax_rules[0]['rules']);
                                if ( ( sizeof ( $selected_tax ) ) > 1 ) {
                                    $tax_query = array(
                                        'relation' => ('or' == strtolower($other_config['taxRelation'])) ? 'OR' : 'AND'
                                    );
                                }
                                foreach ( $selected_tax as $tr ) { 
                                    $taxoperator = ( $tr['rule']['condition'] === 'notin' ) ? 'NOT IN' : 'IN'; 
                                    $tax_query[] = array(
                                        'taxonomy'  => $tr['rule']['item'],
                                        'field'     => 'term_id',
                                        'terms'     => $tr['rule']['value'],
                                        'operator'  => $taxoperator
                                    );
                                }
                                $args['tax_query'] = $tax_query;
                            }

                        }

                        $get_variations     = $this->acoplwGetVariations ( get_posts ( $args ) );
                        $product_lists[$id] = $get_variations ? array_merge ( get_posts ( $args ), $get_variations ) : get_posts ( $args );

                    } else { 

                        if ( array_key_exists ( 'selectedProducts', $other_config ) ) {
                            $get_variations     = $this->acoplwGetVariations ( $other_config['selectedProducts'] );
                            $product_lists[$id] = $get_variations ? array_merge ( $other_config['selectedProducts'], $get_variations ) : $other_config['selectedProducts'];
                        } else {
                            $product_lists[$id] = [];
                        }

                    }

                    if ( $product_lists[$id] && class_exists('SitePress') ) { // Get WPML Product ids @@ 3.6.2
                        $wpmlPosts = [];
                        foreach ( $product_lists[$id] as $product_list_id ) { 
                            $transID = apply_filters( 'wpml_object_id', $product_list_id, 'product' );
                            if ( $transID ) {
                                $wpmlPosts[] = $transID;
                            }
                        }
                        $product_lists[$id] = array_values ( array_unique ( array_merge ( $product_lists[$id], $wpmlPosts ) ) );
                    }
                    
                }

                set_transient(ACOPLW_PRODUCTS_TRANSIENT_KEY, $product_lists, 7 * 24 * HOUR_IN_SECONDS);
                set_transient(ACOPLW_PRODUCTS_LANG_TRANSIENT_KEY, $currentLang, 7 * 24 * HOUR_IN_SECONDS);

            }

            $this->product_lists = $product_lists;
            
        }

    }

    public function onSaleScheduleList( $productID, $badgeID ) {

        $listitems = $this->products_on_sale;

        if ( false == $this->product_schedule_onsale ) {

            // if ( false === ( $acoplw_products_onsale = get_transient ( ACOPLW_PRODUCTS_SCHEDULE_TRANSIENT_KEY ) ) ) {

                $timezone_string = get_option('timezone_string');
                if ($timezone_string) {
                    $datenow = new DateTime(current_time('mysql'), new DateTimeZone($timezone_string));
                } else {
                    $min = 60 * get_option('gmt_offset');
                    $sign = $min < 0 ? "-" : "+";
                    $absmin = abs($min);
                    $tz = sprintf("%s%02d%02d", $sign, $absmin / 60, $absmin % 60);
                    $datenow = new DateTime(current_time('mysql'), new DateTimeZone($tz));
                }
                // Converting to UTC+000 (moment isoString timezone)
                $datenow->setTimezone(new DateTimeZone('+000'));
                $datenow = strtotime($datenow->format('Y-m-d'));

                $acoplw_sale_args = array(
                    'post_type' => ACOPLW_PRODUCTS,
                    'fields' => 'ids',
                    'post_status' => 'publish',
                    'include' => $listitems,
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => '_sale_price_dates_from',
                                'value' => $datenow,
                                'compare' => '<=',
                                'type' => 'NUMERIC'
                            ),
                            array(
                                'key' => '_sale_price_dates_to',
                                'value' => $datenow,
                                'compare' => '>=',
                                'type' => 'NUMERIC'
                            )
                        ),
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => '_sale_price_dates_from',
                                'value' => $datenow,
                                'compare' => '<=',
                                'type' => 'NUMERIC'
                            ),
                            array(
                                'key' => '_sale_price_dates_to',
                                'value' => '',
                                'compare' => '=',
                            ),
                        )
                    )
                );

                $acoplw_products_onsale = get_posts($acoplw_sale_args); 

                // set_transient ( ACOPLW_PRODUCTS_SCHEDULE_TRANSIENT_KEY, $acoplw_products_onsale, 7 * 24 * HOUR_IN_SECONDS );

            // } // Loop End

            $this->product_schedule_onsale = $acoplw_products_onsale;

            if ( in_array ( $productID, $this->product_schedule_onsale ) ) {

                return true;

            } else {

                return false;

            }
        
        }

    }

    public function customStyles()
    {

        $styles = $this->customStyles;
        $wc_badge = get_option('acoplw_wc_badge_status');
        
        if ( $styles ) {

            $result = '<style>.products .acoplw-badge-icon{visibility:visible;} ';
            $result .= $wc_badge ? '.onsale,.ast-onsale-card{display:none !important;} ' : '';
            foreach ( $styles as $style ) {
                $result = $result.$style;
            }
            $result .= '</style>';
            echo $result;

        } else if ( $wc_badge ) {

            $result = '<style>.products .acoplw-badge-icon{visibility:visible;} ';
            $result .= $wc_badge ? '.onsale,.ast-onsale-card{display:none !important;} ' : '';
            $result .= '</style>';
            echo $result;

        }

    }

    // Detail Page Timer
    public function acoplwCountdownTimer () {

        $timerCheck = get_option('acoplw_detail_timer_status') ? get_option('acoplw_detail_timer_status') : 0;
        $timer = ''; 

        if ( $timerCheck ) { 

            // Load active badges
            $this->load_badges();
            $productID = get_the_ID();

            if ( $this->active_badges == null ) return '';

            $badgeOptionsActive = $this->active_badges; 

            foreach ( $this->active_badges as $k => $badge ) { 

                $badgeID        = array_key_exists ( 'id', $badge ) ? $badge['id'] : '';
                $badgeOptions   = array_key_exists ( $badgeID, $badgeOptionsActive ) ? $badgeOptionsActive[$badgeID] : '';

                /* 
                * Checking Dynamic Pricing Settings
                * ver @ 3.2.3
                */
                // $preview_options    = get_post_meta ( $badgeID, 'badge_preview_options', true ) ? get_post_meta ( $badgeID, 'badge_preview_options', true ) : [];
                // $dynamicPR          = function_exists ('AWDP') ? ( array_key_exists( 'pricing_rule', $preview_options ) ? $preview_options['pricing_rule'] : false ) : false;
                // $wdp_filecheck      = realpath(plugin_dir_path(AWDP_FILE)) . DIRECTORY_SEPARATOR . 'includes\class-awdp-plwsupport.php'; 
                // if ( $dynamicPR && file_exists ( $wdp_filecheck ) ) {
                //     include_once($wdp_filecheck); // including dynamic pricing file
                //     $selectedRule   = array_key_exists ( 'selected_rule', $preview_options ) ? $preview_options['selected_rule'] : '';
                //     $awdp       = new AWDP_plwSupport();
                //     if ( !$awdp->plw_check ( $selectedRule, $productID ) ) {
                //         continue;
                //     }
                // } else {
                    // Get Product List
                    if ( !$this->check_in_product_list ( $productID, $badgeID ) ) {
                        continue;
                    }
                // }
                
                $enableTimer        = array_key_exists ( 'enableTimer', $badgeOptions ) ? $badgeOptions['enableTimer'] : '';
                $enableProductTimer = array_key_exists ( 'enableProductTimer', $badgeOptions ) ? $badgeOptions['enableProductTimer'] : ''; 

                if ( $enableTimer && $enableProductTimer ) { 

                    $badgeStyle                 = array_key_exists ( 'badgeStyle', $badgeOptions ) ? $badgeOptions['badgeStyle'] : '';
                    $badgeType                  = array_key_exists ( 'badgeType', $badgeOptions ) ? $badgeOptions['badgeType'] : '';

                    $timerDayLabel              = ( array_key_exists ( 'timerDayLabel', $badgeOptions ) && $badgeOptions['timerDayLabel'] ) ? $badgeOptions['timerDayLabel'] : 'Days';
                    $timerHrsLabel              = ( array_key_exists ( 'timerHrsLabel', $badgeOptions ) && $badgeOptions['timerHrsLabel'] ) ? $badgeOptions['timerHrsLabel'] : 'Hrs';
                    $timerMinLabel              = ( array_key_exists ( 'timerMinLabel', $badgeOptions ) && $badgeOptions['timerMinLabel'] ) ? $badgeOptions['timerMinLabel'] : 'Mins';
                    $timerSecLabel              = ( array_key_exists ( 'timerSecLabel', $badgeOptions ) && $badgeOptions['timerSecLabel'] ) ? $badgeOptions['timerSecLabel'] : 'Secs';

                    $timerPosRight              = ( array_key_exists ( 'timerPosRight', $badgeOptions ) && $badgeOptions['timerPosRight'] ) ? $badgeOptions['timerPosRight'] : 0;
                    $timerPosLeft               = ( array_key_exists ( 'timerPosLeft', $badgeOptions ) && $badgeOptions['timerPosLeft'] ) ? $badgeOptions['timerPosLeft'] : 0;
                    $timerPosBottom             = ( array_key_exists ( 'timerPosBottom', $badgeOptions ) && $badgeOptions['timerPosBottom'] ) ? $badgeOptions['timerPosBottom'] : 0;
                    $timerPosTop                = ( array_key_exists ( 'timerPosTop', $badgeOptions ) && $badgeOptions['timerPosTop'] ) ? $badgeOptions['timerPosTop'] : 0;

                    $timerPositionHorizontal    = array_key_exists ( 'timerPositionHorizontal', $badgeOptions ) ? $badgeOptions['timerPositionHorizontal'] : '';
                    $timerPosition              = array_key_exists ( 'timerPosition', $badgeOptions ) ? $badgeOptions['timerPosition'] : '';
                    $timerOpacity               = array_key_exists ( 'timerOpacity', $badgeOptions ) ? $badgeOptions['timerOpacity'] : '';
                    $timerlineHeight            = array_key_exists ( 'timerlineHeight', $badgeOptions ) ? $badgeOptions['timerlineHeight'] : '';
                    $timerfontSize              = array_key_exists ( 'timerfontSize', $badgeOptions ) ? $badgeOptions['timerfontSize'] : '';
                    $timerLabelColor            = array_key_exists ( 'timerLabelColor', $badgeOptions ) ? $badgeOptions['timerLabelColor'] : '';
                    $timerLabelColorHex         = array_key_exists ( 'timerLabelColorHex', $badgeOptions ) ? $badgeOptions['timerLabelColorHex'] : '';
                    $timerBorderColor           = array_key_exists ( 'timerBorderColor', $badgeOptions ) ? $badgeOptions['timerBorderColor'] : '';
                    $timerBorderColorHex        = array_key_exists ( 'timerBorderColorHex', $badgeOptions ) ? $badgeOptions['timerBorderColorHex'] : '';
                    $timerStart                 = array_key_exists ( 'timerStart', $badgeOptions ) ? $badgeOptions['timerStart'] : '';
                    $timerEnd                   = array_key_exists ( 'timerEnd', $badgeOptions ) ? $badgeOptions['timerEnd'] : '';
                    $timerPrefixLabel           = array_key_exists ( 'timerPrefixLabel', $badgeOptions ) ? $badgeOptions['timerPrefixLabel'] : '';
                    $timerPrefixfontSize        = array_key_exists ( 'timerPrefixfontSize', $badgeOptions ) ? $badgeOptions['timerPrefixfontSize'] : '';
                    $timerPrefixlineHeight      = array_key_exists ( 'timerPrefixlineHeight', $badgeOptions ) ? $badgeOptions['timerPrefixlineHeight'] : '';

                    // Get Custom CSS
                    $css = "opacity:".($timerOpacity / 100).";";
                    $css .= $timerLabelColor ? "color:rgba(".$timerLabelColor['r'].", ".$timerLabelColor['g'].", ".$timerLabelColor['b'].", ".$timerLabelColor['a'].");" : "";

                    $css .= ( $timerPosition == 'bpone' ) ? "top:".$timerPosTop."px;bottom:auto;" : '';
                    $css .= ( $timerPosition == 'bptwo' ) ? "bottom:".$timerPosBottom."px;top:auto;" : '';
                    $css .= ( $timerPositionHorizontal == 'bpthree' ) ? "left:".$timerPosLeft."px;right:auto;" : '';
                    $css .= ( $timerPositionHorizontal == 'bpfour' ) ? "right:".$timerPosRight."px;left:auto;" : '';

                    // Timer Block Styles
                    $blockcss = $timerLabelColor ? "color:rgba(".$timerLabelColor['r'].", ".$timerLabelColor['g'].", ".$timerLabelColor['b'].", ".$timerLabelColor['a'].");" : "";
                    $blockcss .= $timerBorderColor ? "border-color:rgba(".$timerBorderColor['r'].", ".$timerBorderColor['g'].", ".$timerBorderColor['b'].", ".$timerBorderColor['a'].");" : "";

                    // wordpress timezone settings
                    $gmt_offset = get_option('gmt_offset');
                    $timezone_string = get_option('timezone_string');
                    if( $timezone_string ) { 
                        $datenow = new DateTime(current_time('mysql'), new DateTimeZone($timezone_string));
                    } else { 
                        $min    = 60 * get_option('gmt_offset'); 
                        $sign   = $min < 0 ? "-" : "+";
                        $absmin = abs($min); 
                        $tz     = sprintf("%s%02d%02d", $sign, $absmin/60, $absmin%60); 
                        $datenow = new DateTime(current_time('mysql'), new DateTimeZone($tz)); 
                    }
                    // $datenow->setTimezone(new DateTimeZone('+000')); // Converting to UTC+000 (moment isoString timezone)
                    $datacurrent    = $datenow->format('d F Y G:i:s eP');
                    $datenow        = $datenow->format('Y-m-d H:i:s');
                    $day            = date("l");
                    // End Time

                    $acoplwtimer = '';

                    $start_date = date_format(date_create($timerStart),"Y-m-d H:i:s");
                    $end_date = date_format(date_create($timerEnd),"Y-m-d H:i:s");

                    $badgeVerticalClass = preg_match( "/vertical/i", $badgeStyle ) ? ' acoplwStyleVertical' : '';

                    if ( $timerEnd != '' && ( ( strtotime($datenow) >= strtotime($start_date) ) && ( strtotime($datenow) <= strtotime($end_date) ) ) ) {
                        if ( $badgeStyle == 'horizontal003' ) {
                            $time = '<div class="acoplwTimer acoplwTimerStyle'. $badgeStyle.'" current-time="'.$datacurrent.'" data-time="'.date_format(date_create($timerEnd),"d F Y G:i:s eP").'">
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwDays"></span>
                                        <span class="timerLabel">'.$timerDayLabel.'</span>
                                    </div>
                                </div>
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwHours"></span>
                                        <span class="timerLabel">'.$timerHrsLabel.'</span>
                                    </div>
                                </div>
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwMinutes"></span>
                                        <span class="timerLabel">'.$timerMinLabel.'</span>
                                    </div>
                                </div>
                                <div class="acoplwTimerBlock">
                                    <div class="semiCircle semiCircleOne"></div>
                                    <div class="semiCircle semiCircleTwo"></div>
                                    <div class="semiCircle semiCircleThree"></div>
                                    <div class="semiCircle semiCircleFour"></div>
                                    <div class="semiCircleCover"></div>
                                    <div class="semiCircleText">
                                        <span class="timerValue acoplwSeconds"></span>
                                        <span class="timerLabel">'.$timerSecLabel.'</span>
                                    </div>
                                </div>
                            </div>';
                        } else {
                            $time = '<div class="acoplwTimer acoplwTimerStyle'. $badgeStyle.'" data-time="'.date_format(date_create($timerEnd),"d F Y G:i:s eP").'">
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwDays"></span>
                                    <span class="timerLabel">'.$timerDayLabel.'</span>
                                </div>
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwHours"></span>
                                    <span class="timerLabel">'.$timerHrsLabel.'</span>
                                </div>
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwMinutes"></span>
                                    <span class="timerLabel">'.$timerMinLabel.'</span>
                                </div>
                                <div class="acoplwTimerBlock" style="'.$blockcss.'">
                                    <span class="timerValue acoplwSeconds"></span>
                                    <span class="timerLabel">'.$timerSecLabel.'</span>
                                </div>
                            </div>';
                        }
                        $acoplwtimer = ( $badgeStyle == 'horizontal008' && $timerPrefixLabel ) ? '<div class="acoplw-timer-wrap"><span class="acoplwTimerPrefixLabel">' . $timerPrefixLabel . '</span>' . $time . '</div>' : '<div class="acoplw-timer-wrap">' . $time . '</div>';
                    }

                    $timer = '<span class="acoplw-badge-timer-detail'.$badgeVerticalClass.'" style="'.$css.'">'.$acoplwtimer.'</span>';

                }

            }

        }

        // $this->acoplwBadges[$productID][$badgeID] = $badge;
        // $this->customStyles[$badgeID] = $customStyle;

        return $timer;

    }

    
    // Get variations 
    public function acoplwGetVariations ( $productID, $list = false ) {

        if ( $productID ) {
            if ( ( !is_array ( $productID ) && array_key_exists ( $productID, $this->productvariations ) ) || ( $list && array_key_exists ( $list, $this->productvariations ) ) ) {
                return $this->productvariations[$productID];
            } else {
                global $wpdb;
                $productID      = is_array ( $productID ) ? implode(',', $productID) : $productID; 
                $PLVariations   = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_parent IN ($productID) AND post_type = 'product_variation'");

                if ( $PLVariations ) {
                    if ( !is_array ( $productID ) ) $this->productvariations[$productID] = $PLVariations;
                    else if ( $list ) $this->productvariations[$list] = $PLVariations;

                    return $PLVariations;
                } 
            }
        }

        return false;

    }

    /** 
     * Cloning is forbidden.
     * @since 1.0.0
    **/
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /** 
     * Unserializing instances of this class is forbidden.
     * @since 1.0.0
    **/
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

}
