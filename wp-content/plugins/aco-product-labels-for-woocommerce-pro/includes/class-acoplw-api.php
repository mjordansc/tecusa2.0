<?php

if (!defined('ABSPATH'))
    exit;

class ACOPLW_Api
{

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
    private $_active = false;

    public function __construct()
    {
        add_action('rest_api_init', function () {

            register_rest_route('acoplw/v1', '/badges/', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_badges'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/badges/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_badges'),
                'permission_callback' => array($this, 'get_permission'),
                // 'args' => ['id']
            ));

            register_rest_route('acoplw/v1', '/badges/', array(
                'methods' => 'POST',
                'callback' => array($this, 'post_badge'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/delete/', array(
                'methods' => 'POST',
                'callback' => array($this, 'action_delete'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/duplicate/', array(
                'methods' => 'POST',
                'callback' => array($this, 'action_duplicate'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/statusChange/', array(
                'methods' => 'POST',
                'callback' => array($this, 'status_change'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/productlist/', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_list'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/productlist/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_list'),
                'permission_callback' => array($this, 'get_permission'),
                // 'args' => ['id']
            ));

            register_rest_route('acoplw/v1', '/productlist/', array(
                'methods' => 'POST',
                'callback' => array($this, 'post_list'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/settings/', array(
                'methods' => 'POST',
                'callback' => array($this, 'pluginSettings'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/settings/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'pluginSettings'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/licenseactivation/', array(
                'methods' => 'POST',
                'callback' => array($this, 'licenseActivation'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/data/products', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_products'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/productsearch', array(
                'methods' => 'GET',
                'callback' => array($this, 'products_search'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/productlistsearch', array(
                'methods' => 'GET',
                'callback' => array($this, 'product_list_search'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/taxsearch', array(
                'methods' => 'GET',
                'callback' => array($this, 'taxonomy_search'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/getpreviewthumb/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_thumb'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('acoplw/v1', '/resetpreview', array(
                'methods' => 'GET',
                'callback' => array($this, 'reset_thumb'),
                'permission_callback' => array($this, 'get_permission')
            ));

            /**
            * Retrieves badges from the external API.
            * Version 4.0.0
            */
            register_rest_route('acoplw/v1', '/acoplwBadgeApi', array(
                'methods' => 'GET',
                'callback' => array($this, 'acoplw_api_badges'),
                'permission_callback' => array($this, 'get_permission')
            ));

           /**
            * Retrieves badges from the acoplw-badges folder inside the upload directory.
            * Version 4.0.0
            */
            register_rest_route('acoplw/v1', '/uploadbadges', array(
                'methods' => 'GET',
                'callback' => array($this, 'acoplw_upload_image'),
                'permission_callback' => array($this, 'get_permission')
            ));

           /**
            * Retrieves badges from the database.
            * Version 4.0.0
            */
            register_rest_route('acoplw/v1', '/acoplwuploadimage', array(
                'methods' => 'GET',
                'callback' => array($this, 'acoplw_api_uploadImage'),
                'permission_callback' => array($this, 'get_permission')
            ));

            /**
             * API for downloading badges.
             * Version 4.0.0
             */
            register_rest_route('acoplw/v1', '/downloadbadges/', array(
                'methods' => 'POST',
                'callback' => array($this, 'download_badge'),
                'permission_callback' => array($this, 'get_permission')
            ));

        });
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

    public function action_delete($data)
    {
        $data = $data->get_params();
        if ($data['id']) {
            $pt = get_post_type($data['id']);

            if ($pt == ACOPLW_POST_TYPE && wp_delete_post($data['id'], true)) {
                return admin_url('admin.php?page=acoplw_badges_ui');
            } else if ($pt == ACOPLW_PRODUCT_LIST && wp_delete_post($data['id'], true)) {
                return admin_url('admin.php?page=acoplw_product_lists_ui');
            }
        }
    }

    /**
     * Calls the API to retrieve badges.
     * Version: 4.0.0
     */

    public function acoplw_api_badges() {

        $license_key           = get_option('acoplw_plugin_license_key');
        $site_url              = home_url();
        $cached_api_data       = get_transient(ACOPLW_API_TRANSIENT_KEY);
        // $previous_license_key  = get_transient(ACOPLW_LICENSE_TRANSIENT_KEY);

        if ( $cached_api_data !== false ) {
            // Return cached data instantly
            return new WP_REST_Response($cached_api_data, 200);
        }
        $response    = wp_remote_get(ACOPLW_CDN_URL.'/aco-productBadges.php?license=' . urlencode($license_key) . '&url=' . urlencode($site_url));

        if (is_wp_error($response)) {
            return new WP_REST_Response(array(
                'message' => 'Failed to fetch data from the API.',
                'error' => $response->get_error_message()
            ), 500);
        }

        $data = wp_remote_retrieve_body($response);

        if (empty($data)) {
            return new WP_REST_Response(array(
                'message' => 'No data received from the API.'
            ), 404);
        }

        $decoded_data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_REST_Response(array(
                'message' => 'Failed to decode JSON response.',
                'error' => json_last_error_msg()
            ), 500);
        }

        set_transient(ACOPLW_API_TRANSIENT_KEY, $decoded_data, 24 * HOUR_IN_SECONDS);
        // set_transient(ACOPLW_LICENSE_TRANSIENT_KEY, $license_key, 24 * HOUR_IN_SECONDS);

        return new WP_REST_Response($decoded_data, 200);
    }

    /**
     * Download badge callback.
     * Version 4.0.0
     */
    public function download_badge($data)
    {
        $params = $data->get_params();
        $file   = isset($params['file']) ? $params['file'] : '';
        $folder = isset($params['folder']) ? str_replace(' ', '-', strtolower($params['folder'])) : '';

        $upload_dir = wp_upload_dir();
        $wp_badges_dir = $upload_dir['basedir'] . '/acoplw-badges';
        
        if (!file_exists($wp_badges_dir)) {
            if (!mkdir($wp_badges_dir, 0755)) {
                return new WP_REST_Response(array(
                    'message' => 'Main Folder Permission'
                ), 500);
            }
        }

        $folder_path = $wp_badges_dir . '/' . $folder;

        if (!file_exists($folder_path)) {
            if (!mkdir($folder_path, 0755)) {
                return new WP_REST_Response(array(
                    'message' => 'Sub Folder Permission'
                ), 500);
            }
        }
        $image_url = ACOPLW_CDN_URL . "/badges/{$folder}/{$file}.png";
        $image_path = $folder_path . '/' . basename($file, '.'.pathinfo($file, PATHINFO_EXTENSION)) . '.png';

        if (file_exists($image_path)) {
            return new WP_REST_Response(array(
                'message' => 'The image already exists.',
                'image_url' => $image_url
            ), 200);
        }

        $image_data = file_get_contents($image_url);

        if ($image_data === false) {
            return new WP_REST_Response(array(
                'message' => 'Network Error'
            ), 500);
        }

        $save_result = file_put_contents($image_path, $image_data);

        if ($save_result === false) {
            return new WP_REST_Response(array(
                'message' => 'File Save Error'
            ), 500);
        }

        // Storing images in a single array
        $existing_data = get_option('acoplw-APIimg', []);
        if (!is_array($existing_data)) {
            $existing_data = [];
        }

        $image_entry = $file; 
        if (!in_array($image_entry, $existing_data)) {
            $existing_data[] = $image_entry;
        }

        update_option('acoplw-APIimg', $existing_data);
    
        return new WP_REST_Response(array(
            'message' => 'Image downloaded successfully.',
            'imageInfo' => $file,
            'filePath'  => $folder,
            'status' => 'success'
        ), 200);
      
    }
    

    /**
     * uploadbadges API callback.
     * Version 4.0.0
     */
    public function acoplw_upload_image() {
        $uploads_dir = wp_upload_dir();
        $badge_dir = $uploads_dir['basedir'] . '/acoplw-badges';

        if (!is_dir($badge_dir)) {
            return new WP_REST_Response('Directory not found', 404);
        }

        $folders = [];
        $existing_data = get_option('acoplw-APIimg', []);

        $badge_folders = array_filter(glob($badge_dir . '/*'), 'is_dir');
    
        foreach ($badge_folders as $folder_path) {
            $folder_name = basename($folder_path);
            $files = array_diff(scandir($folder_path), array('..', '.'));

            $file_names = array_map(function($file) {
                return pathinfo($file, PATHINFO_FILENAME);
            }, $files);

            $folders[] = [
                'folder' => $folder_name,
                'files' => array_values($file_names),
            ];
        }

        // Remove badges from the option if they no longer exist
        $updated_data = array_filter($existing_data, function($badge) use ($folders) {
            foreach ($folders as $folder) {
                if (in_array($badge, $folder['files'])) {
                    return true;
                }
            }
            return false;
        });

        if ($updated_data !== $existing_data) {
            update_option('acoplw-APIimg', $updated_data);
        }

        return new WP_REST_Response($folders, 200);
    }

    /**
     * acoplw_api_uploadImage API callback.
     * Version 4.0.0
     */

    public function acoplw_api_uploadImage() {
        $existing_data = get_option('acoplw-APIimg', []);
        if (!empty($existing_data)) {
            return new WP_REST_Response($existing_data, 200);
        }
        return new WP_REST_Response([], 200);
    }

    public function action_duplicate($data) 
    {
        $data = $data->get_params();
        $id = $data['id']; 
        if ( $id ) {

            $title = get_the_title($id).' - Copy';;
            $author_id = get_post_field ( 'post_author', $id );
            $badge_args = array (
                'post_title' => $title,
                'post_status' => 'publish',
                'post_type' => ACOPLW_POST_TYPE,
                'post_author' => $author_id
            );
            $newBadgeID = wp_insert_post ( $badge_args );

            // Copy post metadata
            $data = get_post_custom ( $id );
            foreach ( $data as $key => $values ) {
                foreach ( $values as $value ) { 
                    if ( $key === 'badge_label_options' || $key === 'badge_style_options' || $key === 'badge_position_options' || $key === 'badge_preview_options' || $key === 'badge_schedules' ) {
                        $value = unserialize ( $value );
                    }
                    if ( metadata_exists ( 'post', $newBadgeID, $key ) ) { 
                        update_post_meta ( $newBadgeID, $key, $value );
                    } else {
                        add_post_meta ( $newBadgeID, $key, $value );
                    }
                }
            }

            // Get all badges
            $all_listings = get_posts ( array ( 'fields' => 'ids','posts_per_page' => -1, 'post_type' => ACOPLW_POST_TYPE ) );
            $result = array();

            foreach ($all_listings as $listID) {
                $date1 = get_post_meta($listID, 'badge_start_date', true);
                $date2 = get_post_meta($listID, 'badge_end_date', true);
                $style_options = get_post_meta($listID, 'badge_style_options', true);
                $label_options = get_post_meta($listID, 'badge_label_options', true);

                if (!isset($date2) || $date2 == ''){
                    $badge_schedule = 'Starts '.date_format(date_create($date1), 'jS M Y');
                } else if (date_format(date_create($date1), 'j M Y') == date_format(date_create($date2), 'j M Y')){
                    $badge_schedule = date_format(date_create($date1), 'jS M Y');
                } else if (date_format(date_create($date1), 'M Y') == date_format(date_create($date2), 'M Y')){
                    $badge_schedule = date_format(date_create($date1), 'jS') . ' - '. date_format(date_create($date2), 'jS M Y');
                } else if (date_format(date_create($date1), 'Y') == date_format(date_create($date2), 'Y')){
                    $badge_schedule = date_format(date_create($date1), 'jS M') . ' - '. date_format(date_create($date2), 'jS M Y');
                } else {
                    $badge_schedule = date_format(date_create($date1), 'j M Y') . ' - '. date_format(date_create($date2), 'j M Y');
                }
                $result[] = Array(
                    'badgeID' => $listID,
                    'badgeTitle' => get_the_title($listID),
                    'badgeStatus' => get_post_meta($listID, 'badge_status', true),
                    'badgeDate' => get_the_date('d M Y', $listID),
                    'badgeLabel' => $label_options['badgeLabel'],
                    'badgeLabelColor' => $label_options['badgeLabelColor'],
                    'badgeStyle' => $style_options['badgeStyle'],
                    'badgeColor' => $style_options['badgeColor'],
                    'badgeType' => $style_options['badgeType'],
                    'uploadURL' => ( $style_options['badgeType'] == 'upload' && $style_options['badgeStyle'] != '' ) ? wp_get_attachment_url( $style_options['badgeStyle'] ) : '',
                );
            }
            return new WP_REST_Response($result, 200);

        }
    }

    public function status_change($data)
    {
        $data = $data->get_params();
        $wdp_status = ( $data['status'] );
        $id = $data['id'];

        /*
        * Dynamic Labels Text
        * @ ver 3.1.8
        */
        $dayFormat          = get_option('acoplw_detail_dayFormat') ? ( get_option('acoplw_detail_dayFormat') == 'form_full' ? 'l' : ( get_option('acoplw_detail_dayFormat') == 'form_short' ? 'D' : 'jS' )  ) : 'l';
        $monthFormat        = get_option('acoplw_detail_monthFormat') ? ( get_option('acoplw_detail_monthFormat') == 'form_full' ? 'F' : ( get_option('acoplw_detail_monthFormat') == 'form_short' ? 'M' : 'n' )  ) : 'F';
        $yearFormat         = get_option('acoplw_detail_yearFormat') ? ( get_option('acoplw_detail_yearFormat') == 'form_full' ? 'Y' : 'y' ) : 'Y';
        $dynamicLabels      = array ( 'day' => date($dayFormat), 'month' => date($monthFormat), 'year' => date($yearFormat), 'sale' => '50%' );

        if ($id) { 

            update_post_meta($id, 'badge_status', $wdp_status);

            // Get all badges
            $all_listings = get_posts ( array ( 'fields' => 'ids','posts_per_page' => -1, 'post_type' => ACOPLW_POST_TYPE ) );
            $result = array();

            foreach ($all_listings as $listID) {
                $date1 = get_post_meta($listID, 'badge_start_date', true);
                $date2 = get_post_meta($listID, 'badge_end_date', true);
                $style_options = get_post_meta($listID, 'badge_style_options', true);
                $label_options = get_post_meta($listID, 'badge_label_options', true);

                if (!isset($date2) || $date2 == ''){
                    $badge_schedule = 'Starts '.date_format(date_create($date1), 'jS M Y');
                } else if (date_format(date_create($date1), 'j M Y') == date_format(date_create($date2), 'j M Y')){
                    $badge_schedule = date_format(date_create($date1), 'jS M Y');
                } else if (date_format(date_create($date1), 'M Y') == date_format(date_create($date2), 'M Y')){
                    $badge_schedule = date_format(date_create($date1), 'jS') . ' - '. date_format(date_create($date2), 'jS M Y');
                } else if (date_format(date_create($date1), 'Y') == date_format(date_create($date2), 'Y')){
                    $badge_schedule = date_format(date_create($date1), 'jS M') . ' - '. date_format(date_create($date2), 'jS M Y');
                } else {
                    $badge_schedule = date_format(date_create($date1), 'j M Y') . ' - '. date_format(date_create($date2), 'j M Y');
                }
                $result[] = Array(
                    'badgeID'           => $listID,
                    'badgeTitle'        => get_the_title($listID),
                    'badgeStatus'       => get_post_meta($listID, 'badge_status', true),
                    'badgeDate'         => get_the_date('d M Y', $listID),
                    'badgeLabel'        => $label_options['badgeLabel'],
                    'badgeLabelColor'   => $label_options['badgeLabelColor'],
                    'badgeStyle'        => $style_options['badgeStyle'],
                    'badgeColor'        => $style_options['badgeColor'],
                    'badgeType'         => $style_options['badgeType'],
                    'uploadURL'         => ( $style_options['badgeType'] == 'upload' && $style_options['badgeStyle'] != '' ) ? wp_get_attachment_url( $style_options['badgeStyle'] ) : '',
                    'dynamicLabels'     => $dynamicLabels
                );
            }
            return new WP_REST_Response($result, 200);

        }
    }

    public function licenseActivation($data) 
    {

        $data = $data->get_params();
        $license = trim(sanitize_text_field($data[0]));
        $api_params = array(
            'edd_action' => 'activate_license',
            'license' => $license,
            'item_id' => ACOPLW_ITEM_ID, // The ID of the item in EDD
            'url' => home_url()
        );
        $homeURL = ( ( strpos ( get_home_url(), 'localhost' ) !== false || strpos ( get_home_url(), '127.0.0.1' ) !== false ) ) ? 'localhost' : get_home_url(); 

        //Saving license key
        if ( false === get_option('acoplw_plugin_license_key') )
            add_option('acoplw_plugin_license_key', $license, '', 'yes');
        else
            update_option('acoplw_plugin_license_key', $license);

        // Call the custom API.
        $response = wp_remote_post(ACOPLW_STORE_URL, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

        // make sure the response came back okay
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) { 
            if (is_wp_error($response)) {
                $temp = $response->get_error_message();
                if (empty($temp)) {
                    $message = $response->get_error_message();
                } else {
                    $message = __('An error occurred, please try again.');
                }
            }
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response)); 

            if ( false === $license_data->success ) { 
                switch ($license_data->error) {
                    case 'expired' :
                        $message = sprintf(
                            __('Your license key expired on %s.'), date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
                        );
                        break;
                    case 'revoked' :
                        $message = __('Your license key has been disabled.');
                        break;
                    case 'missing' :
                        $message = __('Invalid license.');
                        break;
                    case 'invalid' :
                        $message = __('Invalid license.');
                        break;
                    case 'site_inactive' :
                        $message = __('Your license is not active for this URL.');
                        break;
                    case 'item_name_mismatch' :
                        $message = sprintf(__('This appears to be an invalid license key for %s.'), ACOPLW_PLUGIN_NAME);
                        break;
                    case 'no_activations_left':
                        $message = __('Your license key has reached its activation limit.');
                        break;
                    default :
                        $message = __('An error occurred, please try again.');
                        break;
                }
            } else if ( true === $license_data->success ) {
                $message = __('Your license key has been activated.');
                if ( $license_data->license == 'valid' ) {
                    if ( false === get_option('acoplw_plugin_license_url') )
                        add_option('acoplw_plugin_license_url', $homeURL, '', 'yes');
                    else
                        update_option('acoplw_plugin_license_url', $homeURL);
                }
            }
        }

        if ( $license_data->expires != 'lifetime' && $license_data->expires != '' ) {
            $expiryDate = date_create ( $license_data->expires );
            $expiryDate = date_format ( $expiryDate, "Y-m-d" );
        } else {
            $expiryDate = $license_data->expires;
        }

        if ( false === get_option('acoplw_plugin_license_status') )
            add_option('acoplw_plugin_license_status', $license_data->license, '', 'yes');
        else
            update_option('acoplw_plugin_license_status', $license_data->license);

        if ( false === get_option('acoplw_plugin_license_expiry') )
            add_option('acoplw_plugin_license_expiry', $expiryDate, '', 'yes');
        else
            update_option('acoplw_plugin_license_expiry', $expiryDate);

        $result['message'] = $message;
        $result['response'] = '';
        $result['licenseactive'] = get_option('acoplw_plugin_license_status') ? get_option('acoplw_plugin_license_status') : 0;
        $result['licensekey'] = get_option('acoplw_plugin_license_key') ? get_option('acoplw_plugin_license_key') : '';
        $result['licenseexpiry'] = get_option('acoplw_plugin_license_expiry') ? get_option('acoplw_plugin_license_expiry') : '';
        $result['licenseurl'] = get_option('acoplw_plugin_license_url') ? get_option('acoplw_plugin_license_url') : '';
        $result['status'] = $license_data->license;

        return new WP_REST_Response($result, 200);

    }

    public function pluginSettings($data)
    {
            
        $data = $data->get_params();
        $homeURL = ( ( strpos ( get_home_url(), 'localhost' ) !== false || strpos ( get_home_url(), '127.0.0.1' ) !== false ) ) ? 'localhost' : get_home_url(); 

        if( ! $data['id'] ) {

            $dplist             = $data['dp_list_status'] ? $data['dp_list_status'] : 0;
            $enable_title_hook  = $data['enable_title_hook'] ? $data['enable_title_hook'] : 0;
            $enable_loop_hook   = $data['enable_loop_hook'] ? $data['enable_loop_hook'] : 0;
            $enable_shop_hook   = $data['enable_shop_hook'] ? $data['enable_shop_hook'] : 0;
            $wcbadge            = $data['hide_wc_badge'] ? $data['hide_wc_badge'] : 0;
            $wrapperclass       = $data['wrapperclass'] ? $data['wrapperclass'] : '';
            $recentCount        = $data['recentCount'] ? $data['recentCount'] : '';
            $stockCount         = $data['stockCount'] ? $data['stockCount'] : '';
            $badgeDetail        = $data['enableBadgeDetail'] ? $data['enableBadgeDetail'] : 0;
            $timerEnable        = $data['timerEnable'] ? $data['timerEnable'] : 0;
            $enableJquery       = $data['enableJquery'] ? $data['enableJquery'] : 0;
            $timerPosition      = $data['timerPosition'] ? $data['timerPosition'] : '';
            $dayFormat          = $data['dayFormat'] ? $data['dayFormat'] : '';
            $monthFormat        = $data['monthFormat'] ? $data['monthFormat'] : '';
            $yearFormat         = $data['yearFormat'] ? $data['yearFormat'] : '';
            $licensekey         = $data['licensekey'] ? $data['licensekey'] : '';

            if ( false === get_option('acoplw_dp_list_status') )
                add_option('acoplw_dp_list_status', $dplist, '', 'yes');
            else
                update_option('acoplw_dp_list_status', $dplist);

            if ( false === get_option('acoplw_wc_badge_status') )
                add_option('acoplw_wc_badge_status', $wcbadge, '', 'yes');
            else
                update_option('acoplw_wc_badge_status', $wcbadge);

            if ( false === get_option('acoplw_wrapper_class') )
                add_option('acoplw_wrapper_class', $wrapperclass, '', 'yes');
            else
                update_option('acoplw_wrapper_class', $wrapperclass);

            if ( false === get_option('acoplw_recent_count') )
                add_option('acoplw_recent_count', $recentCount, '', 'yes');
            else
                update_option('acoplw_recent_count', $recentCount);

            if ( false === get_option('acoplw_stock_count') )
                add_option('acoplw_stock_count', $stockCount, '', 'yes');
            else
                update_option('acoplw_stock_count', $stockCount);
            
            if ( false === get_option('acoplw_enable_title_hook') )
                add_option('acoplw_enable_title_hook', $enable_title_hook, '', 'yes');
            else
                update_option('acoplw_enable_title_hook', $enable_title_hook);
            
            if ( false === get_option('acoplw_enable_loop_hook') )
                add_option('acoplw_enable_loop_hook', $enable_loop_hook, '', 'yes');
            else
                update_option('acoplw_enable_loop_hook', $enable_loop_hook);

            if ( false === get_option('acoplw_enable_shop_hook') )
                add_option('acoplw_enable_shop_hook', $enable_shop_hook, '', 'yes');
            else
                update_option('acoplw_enable_shop_hook', $enable_shop_hook);

            if ( false === get_option('acoplw_detail_timer_status') )
                add_option('acoplw_detail_timer_status', $timerEnable, '', 'yes');
            else
                update_option('acoplw_detail_timer_status', $timerEnable);

            if ( false === get_option('acoplw_jquery_status') )
                add_option('acoplw_jquery_status', $enableJquery, '', 'yes');
            else
                update_option('acoplw_jquery_status', $enableJquery);

            if ( false === get_option('acoplw_detail_timer_position') )
                add_option('acoplw_detail_timer_position', $timerPosition, '', 'yes');
            else
                update_option('acoplw_detail_timer_position', $timerPosition);

            if ( false === get_option('acoplw_detail_dayFormat') )
                add_option('acoplw_detail_dayFormat', $dayFormat, '', 'yes');
            else
                update_option('acoplw_detail_dayFormat', $dayFormat);

            if ( false === get_option('acoplw_detail_monthFormat') )
                add_option('acoplw_detail_monthFormat', $monthFormat, '', 'yes');
            else
                update_option('acoplw_detail_monthFormat', $monthFormat);

            if ( false === get_option('acoplw_detail_yearFormat') )
                add_option('acoplw_detail_yearFormat', $yearFormat, '', 'yes');
            else
                update_option('acoplw_detail_yearFormat', $yearFormat);

            if ( false === get_option('acoplw_plugin_license_key') )
                add_option('acoplw_plugin_license_key', $licensekey, '', 'yes');
            else
                update_option('acoplw_plugin_license_key', $licensekey);

            if ( false === get_option('acoplw_detail_page_badge') )
                add_option('acoplw_detail_page_badge', $badgeDetail, '', 'yes');
            else
                update_option('acoplw_detail_page_badge', $badgeDetail);

        }

        if ( $homeURL != get_option('acoplw_plugin_license_url') ) {
            $licenseStatus = 'invalid';
            if ( false != get_option('acoplw_plugin_license_status') ) update_option ( 'acoplw_plugin_license_status', $licenseStatus );
        } 

        /*
        * ver @ 3.2.3
        * enable loop hook for new users
        */
        $activebadges = get_posts ( array ( 'fields' => 'ids','posts_per_page' => -1, 'post_type' => ACOPLW_POST_TYPE ) );
        if ( sizeof ( $activebadges ) === 0 && ( false === get_option('acoplw_enable_loop_hook') ) ) {
            add_option('acoplw_enable_loop_hook', 1, '', 'yes');
        }

        $result['dp_list_status']       = get_option('acoplw_dp_list_status') && function_exists('AWDP') ? get_option('acoplw_dp_list_status') : 0;
        $result['enable_title_hook']    = get_option('acoplw_enable_title_hook') ? get_option('acoplw_enable_title_hook') : 0;
        $result['hide_wc_badge']        = get_option('acoplw_wc_badge_status') ? get_option('acoplw_wc_badge_status') : 0;
        $result['wrapperclass']         = get_option('acoplw_wrapper_class') ? get_option('acoplw_wrapper_class') : '';
        $result['recentCount']          = get_option('acoplw_recent_count') ? get_option('acoplw_recent_count') : '';
        // $result['enable_loop_hook']     = get_option('acoplw_enable_loop_hook') ? get_option('acoplw_enable_loop_hook') : 0;
        // $result['enableBadgeDetail']    = get_option('acoplw_detail_page_badge') ? get_option('acoplw_detail_page_badge') : 0;
        $result['enable_loop_hook']     = get_option('acoplw_enable_loop_hook') ? get_option('acoplw_enable_loop_hook') : 0;
        $result['enable_shop_hook']     = get_option('acoplw_enable_shop_hook') ? get_option('acoplw_enable_shop_hook') : 0;
        $result['enableBadgeDetail']    = ( false === get_option('acoplw_detail_page_badge') ) ? 1 : ( get_option('acoplw_detail_page_badge') ? get_option('acoplw_detail_page_badge') : 0 );
        $result['timerEnable']          = get_option('acoplw_detail_timer_status') ? get_option('acoplw_detail_timer_status') : 0;
        $result['enableJquery']         = get_option('acoplw_jquery_status') ? get_option('acoplw_jquery_status') : 0;
        $result['timerPosition']        = get_option('acoplw_detail_timer_position') ? get_option('acoplw_detail_timer_position') : '';
        $result['dayFormat']            = get_option('acoplw_detail_dayFormat') ? get_option('acoplw_detail_dayFormat') : '';
        $result['monthFormat']          = get_option('acoplw_detail_monthFormat') ? get_option('acoplw_detail_monthFormat') : '';
        $result['yearFormat']           = get_option('acoplw_detail_yearFormat') ? get_option('acoplw_detail_yearFormat') : '';
        $result['licensekey']           = get_option('acoplw_plugin_license_key') ? get_option('acoplw_plugin_license_key') : '';
        $result['licenseexpiry']        = get_option('acoplw_plugin_license_expiry') ? get_option('acoplw_plugin_license_expiry') : '';
        $result['licenseurl']           = get_option('acoplw_plugin_license_url') ? get_option('acoplw_plugin_license_url') : '';
        $result['licenseactive']        = get_option('acoplw_plugin_license_status') ? get_option('acoplw_plugin_license_status') : 0;
        $result['dp_plugin_active']     = function_exists('AWDP') ? true : false; // Checking if dynamic pricing plugin is active

        return new WP_REST_Response($result, 200);
    }

    public function post_badge($data)
    {
        $this->delete_transient();
        $data = $data->get_params(); 

        if ($data['id']) { // Update
            $my_post = array(
                'ID' => $data['id'],
                'post_title' => $data['badgeTitle'] ? wp_strip_all_tags($data['badgeTitle']) : 'Woo Badge',
                'post_content' => '',
            );
            wp_update_post($my_post);
            $this->badge_update_meta($data, $data['id']); 
            return $data['id'];
            
        } else { // Create
            $my_post = array(
                'post_type' => ACOPLW_POST_TYPE,
                'post_title' => $data['badgeTitle'] ? wp_strip_all_tags($data['badgeTitle']) : 'Woo Badge',
                'post_content' => '',
                'post_status' => 'publish',
            );
            $id = wp_insert_post($my_post);
            $this->badge_update_meta($data, $id);
            return $id;
        }
    }

    public function delete_transient()
    {
        delete_transient(ACOPLW_PRODUCTS_TRANSIENT_KEY);
    }

    public function badge_update_meta($data, $id)
    {
        $schedule_array     = [];
        $timerLangLabel     = [];
        $advLangLabel       = [];
        $key                = 0;

        $checkML            = call_user_func ( array ( new ACOPLW_ML(), 'is_default_lan' ), '' );
        $currentLang        = !$checkML ? call_user_func ( array ( new ACOPLW_ML(), 'current_language' ), '' ) : '';

        $label_options      = get_post_meta( $id, 'badge_label_options', true ) ? get_post_meta( $id, 'badge_label_options', true ) : [];
        $dataBadgeLabel     = isset ( $data['badgeLabel'] ) ? $data['badgeLabel'] : '';
        $langOptions        = array_key_exists ( 'badgeLabelLang', $label_options ) ? $label_options['badgeLabelLang'] : [];

        $timer_options      = get_post_meta( $id, 'badge_timer_options', true ) ? get_post_meta( $id, 'badge_timer_options', true ) : [];
        $timerDayLabel      = isset ( $data['timerDayLabel'] ) ? $data['timerDayLabel'] : '';
        $timerHrsLabel      = isset ( $data['timerHrsLabel'] ) ? $data['timerHrsLabel'] : '';
        $timerMinLabel      = isset ( $data['timerMinLabel'] ) ? $data['timerMinLabel'] : '';
        $timerSecLabel      = isset ( $data['timerSecLabel'] ) ? $data['timerSecLabel'] : '';
        $timerLangOptions   = array_key_exists ( 'timerLangOptions', $label_options ) ? $label_options['timerLangOptions'] : [];

        $advBadgeOptions    = get_post_meta( $id, 'badge_advance_options', true ) ? get_post_meta( $id, 'badge_advance_options', true ) : [];
        $advtoptext         = isset ( $data['advtoptext'] ) ? $data['advtoptext'] : '';
        $advbottomtext      = isset ( $data['advbottomtext'] ) ? $data['advbottomtext'] : '';
        $advLangOptions     = array_key_exists ( 'advLangOptions', $label_options ) ? $label_options['advLangOptions'] : [];

        if ( $currentLang ) {
            // Main Label
            if ( $langOptions && !array_key_exists ( $currentLang, $langOptions ) ) { 
                $langOptions[$currentLang] = $dataBadgeLabel;
            } else if ( $langOptions && array_key_exists ( $currentLang, $langOptions ) ) { 
                $langOptions[$currentLang] = $dataBadgeLabel;
            }
            // Advanced Labels
            if ( $advLangOptions && !array_key_exists ( $currentLang, $advLangOptions ) ) { 
                $advLangLabel['advtoptext']     = isset ( $data['advtoptext'] ) ? $data['advtoptext'] : '';
                $advLangLabel['advbottomtext']  = isset ( $data['advbottomtext'] ) ? $data['advbottomtext'] : '';
                $advLangOptions[$currentLang]   = $advLangLabel;
            } else if ( $advLangOptions && array_key_exists ( $currentLang, $advLangOptions ) ) { 
                $advLangLabel['advtoptext']     = isset ( $data['advtoptext'] ) ? $data['advtoptext'] : '';
                $advLangLabel['advbottomtext']  = isset ( $data['advbottomtext'] ) ? $data['advbottomtext'] : '';
                $advLangOptions[$currentLang]   = $advLangLabel;
            }
            // Timer Labels
            if ( $timerLangOptions && !array_key_exists ( $currentLang, $timerLangOptions ) ) { 
                $timerLangLabel['timerDayLabel']    = isset ( $data['timerDayLabel'] ) ? $data['timerDayLabel'] : ''; 
                $timerLangLabel['timerHrsLabel']    = isset ( $data['timerHrsLabel'] ) ? $data['timerHrsLabel'] : '';
                $timerLangLabel['timerMinLabel']    = isset ( $data['timerMinLabel'] ) ? $data['timerMinLabel'] : '';
                $timerLangLabel['timerSecLabel']    = isset ( $data['timerSecLabel'] ) ? $data['timerSecLabel'] : '';
                $timerLangOptions[$currentLang]     = $timerLangLabel;
            } else if ( $timerLangOptions && array_key_exists ( $currentLang, $timerLangOptions ) ) { 
                $timerLangLabel['timerDayLabel']    = isset ( $data['timerDayLabel'] ) ? $data['timerDayLabel'] : ''; 
                $timerLangLabel['timerHrsLabel']    = isset ( $data['timerHrsLabel'] ) ? $data['timerHrsLabel'] : '';
                $timerLangLabel['timerMinLabel']    = isset ( $data['timerMinLabel'] ) ? $data['timerMinLabel'] : '';
                $timerLangLabel['timerSecLabel']    = isset ( $data['timerSecLabel'] ) ? $data['timerSecLabel'] : '';
                $timerLangOptions[$currentLang]     = $timerLangLabel;
            }
        }

        $status             = isset($data['badgeStatus']) ? $data['badgeStatus'] : '';
        $schedules          = isset($data['schedules']) ? $data['schedules'] : '';
        $timings            = isset($data['timings']) ? $data['timings'] : '';
        $productSchedule    = isset($data['productSchedule']) ? $data['productSchedule'] : '';
        $mainLabel          = !$checkML ? ( array_key_exists ( 'badgeLabel', $label_options ) ? $label_options['badgeLabel'] : '' ) : $dataBadgeLabel; 

        $badgeLabel = array(
            'badgeLabel'            => $mainLabel,
            'badgeLabelColor'       => isset($data['badgeLabelColor']) ? $data['badgeLabelColor'] : '',
            'badgeLabelColorHex'    => isset($data['badgeLabelColorHex']) ? $data['badgeLabelColorHex'] : '',
            'fontSize'              => isset($data['fontSize']) ? $data['fontSize'] : '',
            'lineHeight'            => isset($data['lineHeight']) ? $data['lineHeight'] : '',
            'badgeLabelLang'        => $langOptions
        );

        $badgeStyle = array(
            'badgeStyle'            => isset($data['badgeStyle']) ? $data['badgeStyle'] : '',
            'badgeType'             => isset($data['badgeType']) ? $data['badgeType'] : '',
            'badgeColor'            => isset($data['badgeColor']) ? $data['badgeColor'] : '',
            'badgeColorHex'         => isset($data['badgeColorHex']) ? $data['badgeColorHex'] : '',
            'badgeWidth'            => isset($data['badgeWidth']) ? $data['badgeWidth'] : '',
            'badgeHeight'           => isset($data['badgeHeight']) ? $data['badgeHeight'] : '',
            'borderTopLeft'         => isset($data['borderTopLeft']) ? $data['borderTopLeft'] : '',
            'borderTopRight'        => isset($data['borderTopRight']) ? $data['borderTopRight'] : '',
            'borderBottomLeft'      => isset($data['borderBottomLeft']) ? $data['borderBottomLeft'] : '',
            'borderBottomRight'     => isset($data['borderBottomRight']) ? $data['borderBottomRight'] : '',
            'zIndex'                => isset ($data['zIndex']) ? $data['zIndex'] : '',
        );

        $timerOptions = array(
            'enableTimer'               => isset($data['enableTimer']) ? $data['enableTimer'] : '',
            'timerDayLabel'             => isset($data['timerDayLabel']) ? $data['timerDayLabel'] : '',
            'timerHrsLabel'             => isset($data['timerHrsLabel']) ? $data['timerHrsLabel'] : '',
            'timerMinLabel'             => isset($data['timerMinLabel']) ? $data['timerMinLabel'] : '',
            'timerSecLabel'             => isset($data['timerSecLabel']) ? $data['timerSecLabel'] : '',
            'enableProductTimer'        => isset($data['enableProductTimer']) ? $data['enableProductTimer'] : '',
            'timerPosRight'             => isset($data['timerPosRight']) ? $data['timerPosRight'] : '',
            'timerPosLeft'              => isset($data['timerPosLeft']) ? $data['timerPosLeft'] : '',
            'timerPosBottom'            => isset($data['timerPosBottom']) ? $data['timerPosBottom'] : '',
            'timerPosTop'               => isset($data['timerPosTop']) ? $data['timerPosTop'] : '',
            'timerPositionHorizontal'   => isset($data['timerPositionHorizontal']) ? $data['timerPositionHorizontal'] : '',
            'timerPosition'             => isset($data['timerPosition']) ? $data['timerPosition'] : '',
            'timerOpacity'              => isset($data['timerOpacity']) ? $data['timerOpacity'] : '',
            'timerlineHeight'           => isset($data['timerlineHeight']) ? $data['timerlineHeight'] : '',
            'timerfontSize'             => isset($data['timerfontSize']) ? $data['timerfontSize'] : '',
            'timerLabelColor'           => isset($data['timerLabelColor']) ? $data['timerLabelColor'] : '',
            'timerLabelColorHex'        => isset($data['timerLabelColorHex']) ? $data['timerLabelColorHex'] : '',
            'timerPrefixLabel'          => isset($data['timerPrefixLabel']) ? $data['timerPrefixLabel'] : '',
            'timerPrefixfontSize'       => isset($data['timerPrefixfontSize']) ? $data['timerPrefixfontSize'] : '',
            'timerPrefixlineHeight'     => isset($data['timerPrefixlineHeight']) ? $data['timerPrefixlineHeight'] : '',
            'timerLangOptions'          => $timerLangOptions,
            'timerlineHeightNew'        => isset($data['timerlineHeight']) ? $data['timerlineHeight'] : '',
            'timerfontSizeNew'          => isset($data['timerfontSize']) ? $data['timerfontSize'] : '',
            'timerLabelColorNew'        => isset($data['timerLabelColor']) ? $data['timerLabelColor'] : '',
            'timerLabelColorHexNew'     => isset($data['timerLabelColorHex']) ? $data['timerLabelColorHex'] : '',
            'timerBorderColor'          => isset($data['timerBorderColor']) ? $data['timerBorderColor'] : '',
            'timerBorderColorHex'       => isset($data['timerBorderColorHex']) ? $data['timerBorderColorHex'] : '',
        );

        $badgePosition = array(
            'opacity'                   => isset($data['opacity']) ? $data['opacity'] : '',
            'rotationX'                 => isset($data['rotationX']) ? $data['rotationX'] : '',
            'rotationY'                 => isset($data['rotationY']) ? $data['rotationY'] : '',
            'rotationZ'                 => isset($data['rotationZ']) ? $data['rotationZ'] : '',
            'flipHorizontal'            => isset($data['flipHorizontal']) ? $data['flipHorizontal'] : '',
            'flipVertical'              => isset($data['flipVertical']) ? $data['flipVertical'] : '',
            'badgePosition'             => isset($data['badgePosition']) ? $data['badgePosition'] : '',
            'badgePositionHorizontal'   => isset($data['badgePositionHorizontal']) ? $data['badgePositionHorizontal'] : '',
            'posTop'                    => isset($data['posTop']) ? $data['posTop'] : '',
            'posBottom'                 => isset($data['posBottom']) ? $data['posBottom'] : '',
            'posLeft'                   => isset($data['posLeft']) ? $data['posLeft'] : '',
            'posRight'                  => isset($data['posRight']) ? $data['posRight'] : '',
        );

        $previewSettings = array(
            'assignAll'                 => isset($data['assignAll']) ? $data['assignAll'] : '',
            'previewSelected'           => isset($data['previewSelected']) ? $data['previewSelected'] : '',
            'outOfStock'                => isset($data['outOfStock']) ? $data['outOfStock'] : '',
            'recentProducts'            => isset($data['recentProducts']) ? $data['recentProducts'] : '',
            'customPL'                  => isset($data['customPL']) ? $data['customPL'] : '',
            'custom_pl'                 => isset($data['custom_pl']) ? $data['custom_pl'] : '',
            'stockProducts'             => isset($data['stockProducts']) ? $data['stockProducts'] : '',
            'pricing_rule'              => isset ( $data['pricing_rule'] ) ? $data['pricing_rule'] : '',
            'selected_rule'             => isset ( $data['selected_rule'] ) ? $data['selected_rule'] : '',
        );

        $advBadgeOptions = array(
            'advBadgePrimaryColor'      => isset($data['advBadgePrimaryColor']) ? $data['advBadgePrimaryColor'] : '',
            'advBadgePrimaryColorHex'   => isset($data['advBadgePrimaryColorHex']) ? $data['advBadgePrimaryColorHex'] : '',
            'advBadgeSecondaryColor'    => isset($data['advBadgeSecondaryColor']) ? $data['advBadgeSecondaryColor'] : '',
            'advBadgeSecondaryColorHex' => isset($data['advBadgeSecondaryColorHex']) ? $data['advBadgeSecondaryColorHex'] : '',
            'advtoptext'                => isset($data['advtoptext']) ? $data['advtoptext'] : '',
            'advTopTextColor'           => isset($data['advTopTextColor']) ? $data['advTopTextColor'] : '',
            'advTopTextColorHex'        => isset($data['advTopTextColorHex']) ? $data['advTopTextColorHex'] : '',
            'advbottomtext'             => isset($data['advbottomtext']) ? $data['advbottomtext'] : '',
            'advBottomTextColor'        => isset($data['advBottomTextColor']) ? $data['advBottomTextColor'] : '',
            'advBottomTextColorHex'     => isset($data['advBottomTextColorHex']) ? $data['advBottomTextColorHex'] : '',
            'advBadgeCat'               => isset($data['advBadgeCat']) ? $data['advBadgeCat'] : '',
            'enableDynamicValue'        => isset($data['enableDynamicValue']) ? $data['enableDynamicValue'] : '',
            'advLangOptions'            => $advLangOptions
        );

        $scheduleOptions = array(
            'enableDPSchdule'   => isset($data['enableDPSchdule']) ? $data['enableDPSchdule'] : '',
            'selectedDPRule'    => isset($data['selectedDPRule']) ? $data['selectedDPRule'] : '',
        );

        $weekdayScheduleOptions = array(
            'scheduleWeekday'   => isset($data['scheduleWeekday']) ? $data['scheduleWeekday'] : '',
            'scheduleDays'      => isset($data['scheduleDays']) ? serialize($data['scheduleDays']) : '',
            'startTime'         => isset($data['startTime']) ? date('H:i', strtotime( $data['startTime'] ) ) : '',
            'endTime'           => isset($data['endTime']) ? date('H:i', strtotime( $data['endTime'] ) ) : '',
        );

        $selectedList = isset($data['selectedList']) ? $data['selectedList'] : '';
        
        // Schedules
        foreach($schedules as $schedule) { 
            // Start Date
            if($schedule['start_date']){
                $start_date = $schedule['start_date'];
                $start_date = date("Y-m-d H:i:s", strtotime($start_date));
                if( ( strtotime(get_post_meta($id, 'badge_start_date', true)) > strtotime($start_date) ) || $key == 0 ) {
                    update_post_meta($id, 'badge_start_date', $start_date);
                } 
            } else {
                $start_date = '';
            }
            // End Date
            if($schedule['end_date']){
                $end_date = $schedule['end_date'];
                $end_date = date("Y-m-d H:i:s", strtotime($end_date));
                if( ( strtotime(get_post_meta($id, 'badge_end_date', true)) < strtotime($end_date) ) || $key == 0 ) {
                    update_post_meta($id, 'badge_end_date', $end_date);
                } 
            } else {
                update_post_meta($id, 'badge_end_date', '');
                $end_date = '';
            }
            $schedule_array[$key]['start_date'] = $start_date;
            $schedule_array[$key]['end_date']   = $end_date;
            $key++;
        }

        $serialize_data     = array_values($schedule_array);
        $schedule_serialize = serialize($serialize_data);

        // Timings
        if ( !empty( $timings ) ) { 

            // Start Date
            if($timings[0]['start_date']){
                $start_date = $timings[0]['start_date'];
                $start_date = date("Y-m-d H:i:s", strtotime($start_date));
                update_post_meta($id, 'badge_timing_start_date', $start_date);
            }

            // End Date
            if($timings[0]['end_date']){
                $end_date = $timings[0]['end_date'];
                $end_date = date("Y-m-d H:i:s", strtotime($end_date));
                update_post_meta($id, 'badge_timing_end_date', $end_date);
            } else {
                update_post_meta($id, 'badge_timing_end_date', '');
            }

        }

        update_post_meta($id, 'badge_status', $status);
        update_post_meta($id, 'badge_schedules', $schedule_serialize);
        update_post_meta($id, 'badge_label_options', $badgeLabel);
        update_post_meta($id, 'badge_style_options', $badgeStyle);
        update_post_meta($id, 'badge_timer_options', $timerOptions);
        update_post_meta($id, 'badge_position_options', $badgePosition);
        update_post_meta($id, 'badge_preview_options', $previewSettings);
        update_post_meta($id, 'badge_use_pschedule', $productSchedule);
        update_post_meta($id, 'badge_selected_list', $selectedList);
        update_post_meta($id, 'badge_advance_options', $advBadgeOptions);
        update_post_meta($id, 'badge_schedule_options', $scheduleOptions);
        update_post_meta($id, 'badge_weekday_schedules', $weekdayScheduleOptions);

    }

    public function get_badges($data)
    {
        $data               = $data->get_params();
        $checkML            = call_user_func ( array ( new ACOPLW_ML(), 'is_default_lan' ), '' );
        $currentLang        = !$checkML ? call_user_func ( array ( new ACOPLW_ML(), 'current_language' ), '' ) : '';
        
        /*
        * Dynamic Labels Text
        * @ ver 3.1.8
        */
        $dayFormat          = get_option('acoplw_detail_dayFormat') ? ( get_option('acoplw_detail_dayFormat') == 'form_full' ? 'l' : ( get_option('acoplw_detail_dayFormat') == 'form_short' ? 'D' : 'jS' )  ) : 'l';
        $monthFormat        = get_option('acoplw_detail_monthFormat') ? ( get_option('acoplw_detail_monthFormat') == 'form_full' ? 'F' : ( get_option('acoplw_detail_monthFormat') == 'form_short' ? 'M' : 'n' )  ) : 'F';
        $yearFormat         = get_option('acoplw_detail_yearFormat') ? ( get_option('acoplw_detail_yearFormat') == 'form_full' ? 'Y' : 'y' ) : 'Y';
        $dynamicLabels      = array ( 'day' => date($dayFormat), 'month' => date($monthFormat), 'year' => date($yearFormat), 'sale' => '50%' );

        if (isset($data['id'])) {

            $result                 = array();
            $badge_ID               = $data['id'];
            $CSUpload               = '';
            $label_options          = get_post_meta($badge_ID, 'badge_label_options', true);
            $style_options          = get_post_meta($badge_ID, 'badge_style_options', true);
            $timer_options          = get_post_meta($badge_ID, 'badge_timer_options', true);
            $timer_options          = maybe_unserialize($timer_options);
            $position_options       = get_post_meta($badge_ID, 'badge_position_options', true);
            $preview_options        = get_post_meta($badge_ID, 'badge_preview_options', true);
            $pschedule              = get_post_meta($badge_ID, 'badge_use_pschedule', true);
            $schedule_options       = get_post_meta($badge_ID, 'badge_schedule_options', true);
            $schedule_options       = maybe_unserialize($schedule_options);
            $selectedList           = get_post_meta($badge_ID, 'badge_selected_list', true);
            $advBadgeOptions        = get_post_meta($badge_ID, 'badge_advance_options', true);
            $advBadgeOptions        = maybe_unserialize($advBadgeOptions);
            $timerEndDate           = get_post_meta($badge_ID, 'badge_timing_end_date', true) ? get_post_meta($badge_ID, 'badge_timing_end_date', true) : date('Y-m-d H:i:s', strtotime(get_post_meta($badge_ID, 'badge_timing_start_date', true) . ' +1 day'));
            $weekdayScheduleOptions = get_post_meta($badge_ID, 'badge_weekday_schedules', true) ? get_post_meta($badge_ID, 'badge_weekday_schedules', true) : [];
            $weekdayScheduleOptions = maybe_unserialize($weekdayScheduleOptions);
            $timerDetailPage        = get_option('acoplw_detail_timer_status') ? get_option('acoplw_detail_timer_status') : 0;
         // $timings                = isset($timings) ? $timings : '';
            $timings                = [];
            // Scheduling dates
            if( get_post_meta($badge_ID, 'badge_schedules', true) ) {
                $schedules = unserialize(get_post_meta($badge_ID, 'badge_schedules', true));
            } else if(get_post_meta($badge_ID, 'badge_start_date', true) && get_post_meta($badge_ID, 'badge_end_date', true)){ // data before scheduling
                $schedules[0]['start_date'] = get_post_meta($badge_ID, 'badge_start_date', true);
                $schedules[0]['end_date']   = get_post_meta($badge_ID, 'badge_end_date', true);
            }

            // Timer dates
            if( get_post_meta($badge_ID, 'badge_timing_start_date', true) ) { // data before scheduling
                $timings[0]['start_date']   = get_post_meta($badge_ID, 'badge_timing_start_date', true);
                $timings[0]['end_date']     = get_post_meta($badge_ID, 'badge_timing_end_date', true) ? get_post_meta($badge_ID, 'badge_timing_end_date', true) : '';
            }

            $PListID = (int)get_post_meta($badge_ID, 'badge_product_list', true);
            $select_array[] = array ( 'label' => 'Any Product', 'value' => '' ); 
            $select_array[] = array ( 'label' => get_the_title($PListID), 'value' => $PListID ); 

            /*
            * ver 3.1.8
            * UploadURL added instead of replacing badgestyle
            */
            if ( $style_options['badgeType'] == 'upload' ) {
                $imgUpload  = wp_get_attachment_url( $style_options['badgeStyle'] );
                $CSUpload   = basename ( $imgUpload );
            } 
            // else {
                $BStyle = $style_options['badgeStyle'];
            // }

            /* 
            * ML Integration
            * @@ version 1.1.2
            */
            if ( $currentLang ) { 

                $langLabel          = array_key_exists ( 'badgeLabelLang', $label_options ) ? $label_options['badgeLabelLang'] : [];
                $MLBadge            = !empty ( $langLabel ) ? ( array_key_exists ( $currentLang, $langLabel ) ? $langLabel[$currentLang] : $label_options['badgeLabel'] ) : $label_options['badgeLabel'];

                $advLabel           = array_key_exists ( 'advLangOptions', $advBadgeOptions ) ? $advBadgeOptions['advLangOptions'] : [];
                $advtoptext         = !empty ( $advLabel ) ? ( array_key_exists ( $currentLang, $advLabel ) ? $advLabel[$currentLang]['advtoptext'] : $advBadgeOptions['advtoptext'] ) : $advBadgeOptions['advtoptext'];
                $advbottomtext      = !empty ( $advLabel ) ? ( array_key_exists ( $currentLang, $advLabel ) ? $advLabel[$currentLang]['advbottomtext'] : $advBadgeOptions['advbottomtext'] ) : $advBadgeOptions['advbottomtext'];

                $timerLabel         = array_key_exists ( 'timerLangOptions', $timer_options ) ? $timer_options['timerLangOptions'] : [];
                $timerDayLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerDayLabel'] : $timer_options['timerDayLabel'] ) : $timer_options['timerDayLabel']; 
                $timerHrsLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerHrsLabel'] : $timer_options['timerHrsLabel'] ) : $timer_options['timerHrsLabel']; 
                $timerMinLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerMinLabel'] : $timer_options['timerMinLabel'] ) : $timer_options['timerMinLabel'];
                $timerSecLabel      = !empty ( $timerLabel ) ? ( array_key_exists ( $currentLang, $timerLabel ) ? $timerLabel[$currentLang]['timerSecLabel'] : $timer_options['timerSecLabel'] ) : $timer_options['timerSecLabel']; 

            } else {

                $MLBadge            = $label_options['badgeLabel'];

                $advtoptext         = $advBadgeOptions ? $advBadgeOptions['advtoptext'] : '';
                $advbottomtext      = $advBadgeOptions ? $advBadgeOptions['advbottomtext'] : '';

                $timerDayLabel      = $timer_options ? $timer_options['timerDayLabel'] : ''; 
                $timerHrsLabel      = $timer_options ? $timer_options['timerHrsLabel'] : ''; 
                $timerMinLabel      = $timer_options ? $timer_options['timerMinLabel'] : ''; 
                $timerSecLabel      = $timer_options ? $timer_options['timerSecLabel'] : '';

            } 

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
            $datenow    = $datenow->format('F d, Y H:i'); // gmdate('F d, Y H:i'),

            $customPL           = array_key_exists ( 'customPL', $preview_options ) ? $preview_options['customPL'] : [];
            $defaultTax         = [];
            $defaultProducts    = [];
            if ( !empty ( $customPL ) ) {
                global $wpdb; $taxvalues = $prodvalues = ''; $tx_cnt = $pr_cnt = 1;
                foreach ( $customPL as $singlePL ) { 
                    foreach ( $singlePL['rules'] as $val ) { 
                        if ( is_array ( $val ) && $val['rule']['value'] ) {
                            if ( $val['rule']['item'] == 'product_selection') {
                                if ( $pr_cnt != 1 ) $prodvalues .= ',';
                                $prodvalues .= implode ( ',', $val['rule']['value'] ); 
                                $pr_cnt++;
                            } else {
                                if ( $tx_cnt != 1 ) $taxvalues .= ',';
                                $taxvalues .= implode ( ',', $val['rule']['value'] ); 
                                $tx_cnt++;
                            }
                        }
                    } 
                    if( $taxvalues != '' ) { 
                        $defaultTax = $wpdb->get_results ( $wpdb->prepare( "SELECT DISTINCT cat.term_id as value, cat.name as label FROM {$wpdb->prefix}terms cat LEFT JOIN {$wpdb->prefix}term_taxonomy cattax ON cat.term_id = cattax.term_id WHERE cattax.term_id IN (" . $taxvalues . ")" ) );
                    } 
                    if( $prodvalues != '' ) { 
                        $defaultProducts = $wpdb->get_results ( $wpdb->prepare( "SELECT DISTINCT ID as value, post_title as label FROM {$wpdb->prefix}posts WHERE ID IN (" . $prodvalues . ")" ) );
                    } 
                }
            }

            // Checking dynamic pricing file
            $wdp_filecheck      = defined('AWDP_FILE') ? realpath(plugin_dir_path(AWDP_FILE)) . DIRECTORY_SEPARATOR . 'includes/class-awdp-plwsupport.php' : '';
            
            $result = Array(
                'badgeTitle'                => get_the_title($badge_ID),
                'id'                        => $badge_ID,
                'badgeStatus'               => get_post_meta($badge_ID, 'badge_status', true),
                'dp_plugin_active'          => ( get_option ('acoplw_dp_list_status') && get_option ('acoplw_dp_list_status') == 1 && function_exists('AWDP') ) ? true : false,
                
                'badgeLabel'                => $MLBadge,
                'badgeLabelColor'           => $label_options['badgeLabelColor'],
                'badgeLabelColorHex'        => $label_options['badgeLabelColorHex'],
                'fontSize'                  => $label_options['fontSize'],
                'lineHeight'                => $label_options['lineHeight'],
                
                'badgeStyle'                => $BStyle,
                'customUpload'              => $CSUpload,
                'badgeType'                 => $style_options['badgeType'],
                'badgeColor'                => $style_options['badgeColor'],
                'badgeColorHex'             => $style_options['badgeColorHex'],
                'badgeWidth'                => $style_options['badgeWidth'],
                'badgeHeight'               => $style_options['badgeHeight'],
                'borderTopLeft'             => $style_options['borderTopLeft'],
                'borderTopRight'            => $style_options['borderTopRight'],
                'borderBottomLeft'          => $style_options['borderBottomLeft'],
                'borderBottomRight'         => $style_options['borderBottomRight'],
                
                'zIndex'                    => array_key_exists ( 'zIndex', $style_options ) ? $style_options['zIndex'] : '',
                'uploadURL'                 => ( $style_options['badgeType'] == 'upload' && $BStyle != '' ) ? wp_get_attachment_url( $BStyle ) : '',
                
                'advBadgePrimaryColor'      => $advBadgeOptions ? $advBadgeOptions['advBadgePrimaryColor'] : '',
                'advBadgePrimaryColorHex'   => $advBadgeOptions ? $advBadgeOptions['advBadgePrimaryColorHex'] : '',
                'advBadgeSecondaryColor'    => $advBadgeOptions ? $advBadgeOptions['advBadgeSecondaryColor'] : '',
                'advBadgeSecondaryColorHex' => $advBadgeOptions ? $advBadgeOptions['advBadgeSecondaryColorHex'] : '',
                'advtoptext'                => $advtoptext,
                'advTopTextColor'           => $advBadgeOptions ? $advBadgeOptions['advTopTextColor'] : '',
                'advTopTextColorHex'        => $advBadgeOptions ? $advBadgeOptions['advTopTextColorHex'] : '',
                'advbottomtext'             => $advbottomtext,
                'advBottomTextColor'        => $advBadgeOptions ? $advBadgeOptions['advBottomTextColor'] : '',
                'advBottomTextColorHex'     => $advBadgeOptions ? $advBadgeOptions['advBottomTextColorHex'] : '',
                'advBadgeCat'               => $advBadgeOptions ? $advBadgeOptions['advBadgeCat'] : '',
                'enableDynamicValue'        => $advBadgeOptions ? $advBadgeOptions['enableDynamicValue'] : '',
                
                'enableTimer'               => $timer_options ? $timer_options['enableTimer'] : '',
                'timings'                   => $timings,
                'timerEndDate'              => $timerEndDate,
                'timerDayLabel'             => $timerDayLabel,
                'timerHrsLabel'             => $timerHrsLabel,
                'timerMinLabel'             => $timerMinLabel,
                'timerSecLabel'             => $timerSecLabel,
                'enableProductTimer'        => $timer_options ? $timer_options['enableProductTimer'] : '',
                'timerPosRight'             => $timer_options ? $timer_options['timerPosRight'] : '',
                'timerPosLeft'              => $timer_options ? $timer_options['timerPosLeft'] : '',
                'timerPosBottom'            => $timer_options ? $timer_options['timerPosBottom'] : '',
                'timerPosTop'               => $timer_options ? $timer_options['timerPosTop'] : '',
                'timerPositionHorizontal'   => $timer_options ? $timer_options['timerPositionHorizontal'] : '',
                'timerPosition'             => $timer_options ? $timer_options['timerPosition'] : '',
                'timerOpacity'              => $timer_options ? $timer_options['timerOpacity'] : '',
                'timerlineHeight'           => $timer_options ? $timer_options['timerlineHeightNew'] : '',
                'timerfontSize'             => $timer_options ? $timer_options['timerfontSizeNew'] : '',
                'timerLabelColor'           => $timer_options ? $timer_options['timerLabelColorNew'] : '',
                'timerLabelColorHex'        => $timer_options ? $timer_options['timerLabelColorHexNew'] : '',
                'timerBorderColor'          => $timer_options ? $timer_options['timerBorderColor'] : '',
                'timerBorderColorHex'       => $timer_options ? $timer_options['timerBorderColorHex'] : '',
                'timerPrefixLabel'          => $timer_options ? $timer_options['timerPrefixLabel'] : '',
                'timerPrefixfontSize'       => $timer_options ? $timer_options['timerPrefixfontSize'] : '',
                'timerPrefixlineHeight'     => $timer_options ? $timer_options['timerPrefixlineHeight'] : '',
                'timerDetailPage'           => $timerDetailPage,
                
                'opacity'                   => $position_options ? $position_options['opacity'] : '',
                'rotationX'                 => $position_options ? $position_options['rotationX'] : '',
                'rotationY'                 => $position_options ? $position_options['rotationY'] : '',
                'rotationZ'                 => $position_options ? $position_options['rotationZ'] : '',
                'flipHorizontal'            => $position_options ? $position_options['flipHorizontal'] : '',
                'flipVertical'              => $position_options ? $position_options['flipVertical'] : '',
                'badgePosition'             => $position_options ? $position_options['badgePosition'] : '',
                'badgePositionHorizontal'   => $position_options ? $position_options['badgePositionHorizontal'] : '',
                'posTop'                    => $position_options ? $position_options['posTop'] : '',
                'posBottom'                 => $position_options ? $position_options['posBottom'] : '',
                'posLeft'                   => $position_options ? $position_options['posLeft'] : '',
                'posRight'                  => $position_options ? $position_options['posRight'] : '',
                
                'schedules'                 => $schedules,
                'productSchedule'           => $pschedule,
                'serverDateTime'            => $datenow, 
                'enableDPSchdule'           => $schedule_options ? $schedule_options['enableDPSchdule'] : '',
                'selectedDPRule'            => $schedule_options ? $schedule_options['selectedDPRule'] : '',
                
                'scheduleWeekday'           => array_key_exists ( 'scheduleWeekday', $weekdayScheduleOptions ) ? $weekdayScheduleOptions['scheduleWeekday'] : '',
                'scheduleDays'              => array_key_exists ( 'scheduleDays', $weekdayScheduleOptions ) ? unserialize($weekdayScheduleOptions['scheduleDays']) : '',
                'startTime'                 => array_key_exists ( 'startTime', $weekdayScheduleOptions ) ? $weekdayScheduleOptions['startTime'] : '',
                'endTime'                   => array_key_exists ( 'endTime', $weekdayScheduleOptions ) ? $weekdayScheduleOptions['endTime'] : '',
                
                'assignAll'                 => $preview_options['assignAll'],
                'outOfStock'                => $preview_options['outOfStock'],
                'recentProducts'            => $preview_options['recentProducts'],
                'previewProductList'        => $preview_options['previewSelected'] ? array ( 'label' => get_the_title($preview_options['previewSelected']), 'value' => $preview_options['previewSelected'] ) : '',
                'previewProductThumb'       => $preview_options['previewSelected'] ? get_the_post_thumbnail_url( $preview_options['previewSelected'], 'post-thumbnail' ) : '',
                'previewSelected'           => $preview_options['previewSelected'],
                'customPL'                  => array_key_exists ( 'customPL', $preview_options ) ? $preview_options['customPL'] : '',
                'custom_pl'                 => array_key_exists ( 'custom_pl', $preview_options ) ? $preview_options['custom_pl'] : '',
                'stockProducts'             => array_key_exists ( 'stockProducts', $preview_options ) ? $preview_options['stockProducts'] : '',
                
                'selectedList'              => $selectedList,
                
                'dynamicLabels'             => $dynamicLabels,
                
                'listUrl'                   => admin_url('admin.php?page=acoplw_product_lists_ui#/'),
                'defaultTax'                => $defaultTax,
                'defaultProducts'           => $defaultProducts,
                'wdp_plugin_active'         => ( $wdp_filecheck && file_exists ( $wdp_filecheck ) ) ? true : false,
                'pricing_rule'              => ( $wdp_filecheck && file_exists ( $wdp_filecheck ) && array_key_exists ( 'pricing_rule', $preview_options ) ) ? $preview_options['pricing_rule'] : '',
                'selected_rule'             => ( $wdp_filecheck && file_exists ( $wdp_filecheck ) && array_key_exists ( 'selected_rule', $preview_options ) ) ? $preview_options['selected_rule'] : '',
            );
            
            return new WP_REST_Response($result, 200);

        }

        $all_listings = get_posts ( array ( 'fields' => 'ids','posts_per_page' => -1, 'post_type' => ACOPLW_POST_TYPE ) );
        $result = array();

        foreach ($all_listings as $listID) {
            $date1              = get_post_meta($listID, 'badge_start_date', true);
            $date2              = get_post_meta($listID, 'badge_end_date', true);
            $style_options      = get_post_meta($listID, 'badge_style_options', true);
            $label_options      = get_post_meta($listID, 'badge_label_options', true);
            $position_options   = get_post_meta($listID, 'badge_position_options', true);
            $preview_options    = get_post_meta($listID, 'badge_preview_options', true);

            if (!isset($date2) || $date2 == ''){
                $badge_schedule = 'Starts '.date_format(date_create($date1), 'jS M Y');
            } else if (date_format(date_create($date1), 'j M Y') == date_format(date_create($date2), 'j M Y')){
                $badge_schedule = date_format(date_create($date1), 'jS M Y');
            } else if (date_format(date_create($date1), 'M Y') == date_format(date_create($date2), 'M Y')){
                $badge_schedule = date_format(date_create($date1), 'jS') . ' - '. date_format(date_create($date2), 'jS M Y');
            } else if (date_format(date_create($date1), 'Y') == date_format(date_create($date2), 'Y')){
                $badge_schedule = date_format(date_create($date1), 'jS M') . ' - '. date_format(date_create($date2), 'jS M Y');
            } else {
                $badge_schedule = date_format(date_create($date1), 'j M Y') . ' - '. date_format(date_create($date2), 'j M Y');
            }

            /* 
            * ML Integration
            * @@ version 1.1.2
            */
            if ( $currentLang ) { 
                $langLabel  = array_key_exists ( 'badgeLabelLang', $label_options ) ? $label_options['badgeLabelLang'] : [];
                $MLBadge    = !empty ( $langLabel ) ? ( array_key_exists ( $currentLang, $langLabel ) ? $langLabel[$currentLang] : $label_options['badgeLabel'] ) : $label_options['badgeLabel'];
            } else {
                $MLBadge    = $label_options['badgeLabel'];
            } 

            $result[] = Array(
                'badgeID'               => $listID,
                'badgeTitle'            => get_the_title($listID),
                'badgeStatus'           => get_post_meta($listID, 'badge_status', true),
                'badgeDate'             => get_the_date('d M Y', $listID),

                'badgeLabel'            => $MLBadge,
                'badgeLabelColor'       => $label_options['badgeLabelColor'],
                'badgeStyle'            => $style_options['badgeStyle'],
                'badgeType'             => $style_options['badgeType'],
                'badgeColor'            => $style_options['badgeColor'],

                'uploadURL'             => ( $style_options['badgeType'] == 'upload' && $style_options['badgeStyle'] != '' ) ? wp_get_attachment_url( $style_options['badgeStyle'] ) : '',

                'fontSize'              => $label_options['fontSize'],
                'lineHeight'            => $label_options['lineHeight'],

                'badgeWidth'            => $style_options['badgeWidth'],
                'badgeHeight'           => $style_options['badgeHeight'],
                'borderTopLeft'         => $style_options['borderTopLeft'],
                'borderTopRight'        => $style_options['borderTopRight'],
                'borderBottomLeft'      => $style_options['borderBottomLeft'],
                'borderBottomRight'     => $style_options['borderBottomRight'],

                'opacity'               => $position_options['opacity'],
                'rotationX'             => $position_options['rotationX'],
                'rotationY'             => $position_options['rotationY'],
                'rotationZ'             => $position_options['rotationZ'],
                'flipHorizontal'        => $position_options['flipHorizontal'],
                'flipVertical'          => $position_options['flipVertical'],

                'assignAll'             => $preview_options['assignAll'],
                'dynamicLabels'         => $dynamicLabels 
            );
        }
        return new WP_REST_Response($result, 200);
    }

    public function get_list($data)
    {

        $data = $data->get_params();

        if (isset($data['id'])) {
            $result = array();
            global $wpdb;
            $list_item = get_post($data['id']);
            $result['list_name'] = sanitize_text_field($list_item->post_title);
            $result['list_id'] = $list_item->ID;
            $result['list_type'] = sanitize_text_field(get_post_meta($list_item->ID, 'list_type', true));
            $other_config = get_post_meta($list_item->ID, 'product_list_config', true);

            $rules = $other_config['rules']; $tax = []; $values = ''; $ar_cnt = 1;
            if($rules) {
                foreach ( $rules as $rule ) { 
                    foreach ( $rule['rules'] as $val ) { 
                        if ( is_array ( $val ) && $val['rule']['value'] ) {
                            if ( $ar_cnt != 1 ) $values .= ',';
				            $values .= implode ( ',', $val['rule']['value'] ); 
                        }
                        $ar_cnt++;
                    } 
                    if( $values != '' ) { 
                        $tax = $wpdb->get_results ( $wpdb->prepare( "SELECT DISTINCT cat.term_id as value, cat.name as label FROM {$wpdb->prefix}terms cat LEFT JOIN {$wpdb->prefix}term_taxonomy cattax ON cat.term_id = cattax.term_id WHERE cattax.term_id IN (" . $values . ")" ) );
                    }
                }

                foreach ( $other_config['rules'] as $key => $val) { 
                    $other_config['rules'][$key]['rules'] =  array_values(array_filter($other_config['rules'][$key]['rules'])); 
                }
            }

            $result['selectedProducts'] = ($other_config['selectedProducts']);
            $result['productAuthor']    = sanitize_text_field($other_config['productAuthor'] ?? '');
            $result['excludedProducts'] = ($other_config['excludedProducts']);
            $result['taxRelation']      = ($other_config['taxRelation']);
            $result['rules']            = $other_config['rules'] ? ($other_config['rules']) : '';
            $result['sku_search']       = array_key_exists ( 'sku_search', $other_config ) ? $other_config['sku_search'] : '';
            $defaultProducts            = array_merge(is_array($result['excludedProducts']) ? $result['excludedProducts'] : [], is_array($result['selectedProducts']) ? $result['selectedProducts'] : []); 
            $result['defaultProducts']  = empty($defaultProducts) ? [] : $this->get_products($defaultProducts);  // used for product list suggestion dropdown

            $result['defaultTax']       = $tax;

            return new WP_REST_Response($result, 200);
        }

        $all_listings = get_posts ( array ( 'fields' => 'ids', 'numberposts' => -1, 'post_type' => ACOPLW_PRODUCT_LIST ) );
        $result = array();
        foreach ($all_listings as $listID) {
            $result[] = array(
                'list_id'   => $listID,
                'list_name' => get_the_title($listID) ? get_the_title($listID) : 'No Label',
                'list_date' => get_the_date('d M Y', $listID)
            );
        }
        return new WP_REST_Response($result, 200);
        
    }

    /**
     *
     */
    public function get_products($arg)
    {

        if (is_a($arg, 'WP_REST_Request')) {

            $productslist = get_posts(array('fields' => 'ids','numberposts' => -1, 'post_type' => 'product'));
            $products = Array();
            foreach ($productslist as $product) {
                if(  empty($products) || array_search ( $product, array_column ( $products, 'value' ) ) === false ) {
                    $products[] = [
                        'value' => $product,
                        'label' => get_the_title ( $product )
                    ];
                }
            }
            return new WP_REST_Response($products, 200);

        } else {

            $productslist = $arg;
            $products = [];
            foreach ($productslist as $product) { 
                if( empty($products) || array_search ( $product, array_column ( $products, 'value' ) ) === false ) { 
                    $products[] = [
                        'value' => $product,
                        'label' => get_the_title ( $product )
                    ];
                }
            }
            return $products;

        }
        
    }

    public function post_list($data)
    {
        $data = $data->get_params();
        $this->delete_transient();
        if ($data['id']) {
            $my_post = array(
                'ID'            => $data['id'],
                'post_title'    => $data['name'] ? wp_strip_all_tags($data['name']) : 'Product List',
                'post_content' => '',
            );
            wp_update_post($my_post);
            $this->update_post_list_meta($data['id'], $data);
            return $data['id'];
        } else {
            $my_post = array(
                'post_type'     => ACOPLW_PRODUCT_LIST,
                'post_title'    => $data['name'] ? wp_strip_all_tags($data['name']) : 'Product List',
                'post_content'  => '',
                'post_status'   => 'publish',
            );
            $id = wp_insert_post($my_post);
            $this->update_post_list_meta($id, $data);
            return $id;
        }
    }

    public function update_post_list_meta($id, $data)
    {

        update_post_meta($id, 'list_type', $data['list_type']);
        $other_config = array(
            'selectedProducts'  => ($data['selectedProducts']),
            'productAuthor'     => ($data['productAuthor']),
            'excludedProducts'  => ($data['excludedProducts']),
            'taxRelation'       => ($data['taxRelation']),
            'rules'             => ($data['rules']),
            'sku_search'        => $data['sku_search'],
        );
        update_post_meta($id, 'product_list_config', $other_config);

    }

    
    /**
     * @search parameter - title
     */
    public function product_list_search($arg)
    {
        global $wpdb;
        $params = $arg->get_params();
        $search = isset($params['search']) ? sanitize_text_field($params['search']) : '';
        $results = $wpdb->get_results ( $wpdb->prepare( "SELECT post_title as label, ID as value, post_type as type FROM {$wpdb->prefix}posts WHERE post_type in ( 'acoplw_pt_products' ) AND post_status = 'publish' AND ( post_title LIKE '" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%' OR post_title LIKE '%" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%' OR post_title LIKE '%" . esc_sql ( $wpdb->esc_like ( $search ) ) . "' ) GROUP BY ID, post_title" ) );
        foreach ( $results as $result ) { 
            // $result->value = (int)$result->value; 
            // $result->label = $result->label; 
            if ( $result->label === '' ) $result->label = 'Product List';
            $result->value = 'list_'.$result->value; 
        } 

        return new WP_REST_Response($results, 200);
    }

    /**
     * @search parameter - title
     */
    public function products_search($arg)
    {
        global $wpdb;
        $params     = $arg->get_params();
        $search     = isset($params['search']) ? sanitize_text_field($params['search']) : '';
        $skuSearch  = isset($params['sku_search']) ? sanitize_text_field($params['sku_search']) : '';

        if ( $skuSearch ) {

            $results    = $wpdb->get_results ( $wpdb->prepare("SELECT post_title as label, ID as value, post_type as type FROM {$wpdb->prefix}posts pt LEFT JOIN {$wpdb->prefix}postmeta pm ON pt.ID = pm.post_id WHERE pm.meta_key='_sku' AND pm.meta_value LIKE '" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%'") );

        } else {

            $results    = $wpdb->get_results ( $wpdb->prepare("SELECT post_title as label, ID as value, post_type as type FROM {$wpdb->prefix}posts WHERE post_type in ( 'product' ) AND post_status in ( 'publish', 'draft' ) AND ( post_title LIKE '" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%' OR post_title LIKE '%" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%' OR post_title LIKE '%" . esc_sql ( $wpdb->esc_like ( $search ) ) . "' ) GROUP BY ID, post_title") );

        }

        foreach ( $results as $result ) { 
            $result->value = (int)$result->value;
            $status = get_post_status ( $result->value );
            if ( $status === 'draft' ) {
                $result->label = $result->label . ' - Draft';
            }
        } 

        return new WP_REST_Response($results, 200);
    }

    
    /**
     * @search parameter - title
     */
    public function taxonomy_search($arg)
    {
        global $wpdb;
        $params = $arg->get_params();
        $search = isset($params['search']) ? sanitize_text_field($params['search']) : '';
        $tax = ( $params['tax'] == 'tag' ) ? 'product_tag' : 'product_cat';

        $results = $wpdb->get_results ( $wpdb->prepare( "SELECT cat.term_id AS value, cat.name AS label FROM {$wpdb->prefix}terms cat LEFT JOIN {$wpdb->prefix}term_taxonomy cattax ON cat.term_id = cattax.term_id WHERE cattax.taxonomy = '" . $tax . "' AND ( cat.name LIKE '" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%' OR cat.name LIKE '%" . esc_sql ( $wpdb->esc_like ( $search ) ) . "%' OR cat.name LIKE '%" . esc_sql ( $wpdb->esc_like ( $search ) ) . "' )" ) );

        foreach ( $results as $result ) { 
            $result->value = (int)$result->value;
        } 

        return new WP_REST_Response($results, 200);
    }

    public function get_thumb($arg) {

        $params = $arg->get_params();
        $id = $params['id'];
        $result = [];
        $previewimage = plugin_dir_url(__FILE__). '../assets/images/preview-product.jpg';
        $thumb = get_the_post_thumbnail_url( $id, 'post-thumbnail' );
        $result['id'] = $id;
        $result['thumb'] = $thumb ? $thumb : $previewimage;
        return new WP_REST_Response($result, 200);
        
    }

    public function reset_thumb($arg) {

        $params = $arg->get_params();
        $id = $params['id'];
        $result = [];
        $previewimage = plugin_dir_url(__FILE__). '../assets/images/preview-product.jpg';
        $result['id'] = $id;
        $result['thumb'] = $previewimage;
        return new WP_REST_Response($result, 200);
        
    }

    /**
     * Permission Callback
     **/
    public function get_permission()
    {
        if (current_user_can('administrator') || current_user_can('manage_woocommerce')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

}
