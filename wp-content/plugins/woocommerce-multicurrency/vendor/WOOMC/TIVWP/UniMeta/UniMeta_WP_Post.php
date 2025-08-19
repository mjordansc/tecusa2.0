<?php
/**
 * UniMeta_WP_Post
 *
 * @since 1.9.0
 *
 * Copyright (c) 2022, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\UniMeta;

/**
 * Class UniMeta_WP_Post
 *
 * @since 1.9.0
 */
class UniMeta_WP_Post extends AbstractUniMeta {

	/**
	 * Var wp_object.
	 *
	 * @since 1.9.0
	 *
	 * @var \WP_Post
	 */
	protected $wp_object;

	/**
	 * Implements get_meta
	 *
	 * @inheritDoc
	 */
	public function get_meta( string $key, bool $single = true, string $context = 'view', $default_value = '' ) {
		$meta = \get_post_meta( $this->wp_object->ID, $key, $single );

		return $meta ? $meta : $default_value;
	}

	/**
	 * Implements set_meta
	 *
	 * @inheritDoc
	 */
	public function set_meta( string $key, $value ) {
		\update_post_meta( $this->wp_object->ID, $key, $value );
	}

	/**
	 * Implements delete_meta
	 *
	 * @inheritDoc
	 */
	public function delete_meta( string $key ) {
		\delete_post_meta( $this->wp_object->ID, $key );
	}

	/**
	 * Implements get_id
	 *
	 * @inheritDoc
	 */
	public function get_id(): int {
		return $this->wp_object->ID;
	}
}
