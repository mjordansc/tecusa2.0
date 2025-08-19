<?php
/**
 * Abstract CPT.
 *
 * @since 2.7.0
 * Copyright (c) TIV.NET INC 2024.
 */

namespace WOOMC\Dependencies\TIVWP\Abstracts;

use WOOMC\Dependencies\TIVWP\InterfaceHookable;

/**
 * Class AbstractCPT
 *
 * @since 2.7.0
 */
abstract class AbstractCPT implements InterfaceHookable {

	/**
	 * Post type.
	 *
	 * @since 2.7.0
	 * @var string
	 */
	public const CPT_KEY = '';

	/**
	 * Display name.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public static function cpt_label(): string {
		return '';
	}

	/**
	 * Setup actions and filters.
	 *
	 * @since 2.7.0
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_action( 'woocommerce_after_register_post_type', array( $this, 'action__register_cpt' ) );

		/**
		 * Add our CPT to the list of Woo screens where it loads its stuff.
		 * Required by {@see WooScriptLoader}
		 */
		\add_filter(
			'woocommerce_screen_ids',
			function ( $screen_ids ) {
				$screen_ids[] = static::CPT_KEY;

				return $screen_ids;
			}
		);
	}

	/**
	 * CPT labels.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	protected function cpt_labels(): array {
		return array(
			'singular_name' => $this->cpt_label(),
			'add_new'       => \__( 'Add New', 'woocommerce' ),
			'add_new_item'  => \__( 'Add New', 'woocommerce' ),
		);
	}

	/**
	 * Method admin_menu_icon.
	 *
	 * @since 2.7.0
	 *
	 * @return string
	 */
	protected function admin_menu_icon(): string {
		return 'dashicons-admin-post';
	}

	/**
	 * CPT args.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	protected function cpt_args() {
		return array(
			'labels'              => $this->cpt_labels(),
			'label'               => $this->cpt_label(),
			'can_export'          => true,
			'capability_type'     => 'page',
			'delete_with_user'    => true,
			'description'         => '',
			'ep_mask'             => EP_NONE,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'map_meta_cap'        => true,
			'menu_icon'           => $this->admin_menu_icon(),
			'menu_position'       => '',
			'public'              => true,
			'publicly_queryable'  => true,
			'query_var'           => false,
			'rest_base'           => '',
			'rewrite'             => array( 'with_front' => false ),
			'show_in_admin_bar'   => false,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => true,
			'show_ui'             => true,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt' ),
			'taxonomies'          => array(),
		);
	}

	/**
	 * Register CPT if configured.
	 *
	 * @since 2.7.0
	 */
	public function action__register_cpt(): void {
		if ( static::CPT_KEY && $this->cpt_label() ) {
			$this->maybe_register_post_type();
		}
	}

	/**
	 * Register CPT if not registered yet.
	 *
	 * @since 2.7.0
	 */
	protected function maybe_register_post_type(): void {

		if ( \is_blog_installed() && ! \post_type_exists( static::CPT_KEY ) ) {
			\register_post_type(
				static::CPT_KEY,
				$this->cpt_args()
			);
		}
	}
}
