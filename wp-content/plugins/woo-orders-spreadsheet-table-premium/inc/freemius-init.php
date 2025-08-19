<?php

defined( 'ABSPATH' ) || exit;
if ( !function_exists( 'wpsewco_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wpsewco_fs() {
        global $wpsewco_fs;
        if ( !isset( $wpsewco_fs ) ) {
            if ( !defined( 'WP_FS__PRODUCT_4674_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_4674_MULTISITE', true );
            }
            $wpsewco_fs = fs_dynamic_init( array(
                'id'             => '4674',
                'slug'           => 'woo-orders-spreadsheet-table',
                'type'           => 'plugin',
                'public_key'     => 'pk_63a2e95eebb8c13033837c14e3573',
                'is_premium'     => true,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                    'slug'       => 'wpsewco_welcome_page',
                    'first-path' => 'admin.php?page=wpsewco_welcome_page',
                    'support'    => false,
                ),
                'is_live'        => true,
            ) );
        }
        return $wpsewco_fs;
    }

    // Init Freemius.
    wpsewco_fs();
    // Signal that SDK was initiated.
    do_action( 'wpsewco_fs_loaded' );
}