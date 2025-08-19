<?php
/**
 * UniMeta_WC_Order
 *
 * @since 1.9.0
 *
 * Copyright (c) 2022, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\UniMeta;

/**
 * Class UniMeta_WC_Order
 *
 * @since 1.9.0
 */
class UniMeta_WC_Order extends AbstractUniMeta {

	/**
	 * Var wp_object.
	 *
	 * @since 1.9.0
	 *
	 * @var \WC_Order
	 */
	protected $wp_object;

	/**
	 * Check if the key is an internal one.
	 *
	 * @since 1.9.2
	 *
	 * @param string $key Key to check.
	 *
	 * @return bool   true if it's an internal key, false otherwise
	 */
	protected function is_internal_meta_key( string $key ): bool {
		return in_array( $key, $this->wp_object->get_data_store()->get_internal_meta_keys(), true );
	}

	/**
	 * Implements deprecated_methods.
	 *
	 * @inheritDoc
	 */
	protected function deprecated_methods(): array {
		return array(
			'_order_currency' => '_currency',
		);
	}

	/**
	 * Implements get_meta
	 *
	 * @inheritDoc
	 */
	public function get_meta( string $key, bool $single = true, string $context = 'edit', $default_value = '' ) {
		$key = $this->un_deprecate( $key );
		if ( $this->is_internal_meta_key( $key ) ) {
			$function = 'get_' . ltrim( $key, '_' );

			if ( is_callable( array( $this->wp_object, $function ) ) ) {
				$meta = $this->wp_object->{$function}();
			} else {
				// We should not be here.
				$meta = '';
			}
		} else {
			$meta = $this->wp_object->get_meta( $key, $single, $context );
		}

		return $meta ? $meta : $default_value;
	}

	/**
	 * Implements set_meta
	 *
	 * @since 1.9.0
	 * @since 2.1.5 When HPOS, need to delete meta before setting new.
	 *
	 * @inheritDoc
	 */
	public function set_meta( string $key, $value ) {
		$key = $this->un_deprecate( $key );
		if ( $this->is_internal_meta_key( $key ) ) {
			$function = 'set_' . ltrim( $key, '_' );

			if ( is_callable( array( $this->wp_object, $function ) ) ) {
				$this->wp_object->{$function}( $value );
			}
		} else {
			// Needed for HPOS.
			$this->delete_meta( $key );
			$this->save_object();

			$this->wp_object->update_meta_data( $key, $value );
			// Do not need to save_object();
		}
	}

	/**
	 * Implements save_object
	 *
	 * @inheritDoc
	 */
	public function save_object() {
		$this->wp_object->save();
	}

	/**
	 * Implements delete_meta
	 *
	 * @inheritDoc
	 */
	public function delete_meta( string $key ) {
		$this->wp_object->delete_meta_data( $key );
	}

	/**
	 * Implements get_id
	 *
	 * @inheritDoc
	 */
	public function get_id(): int {
		return $this->wp_object->get_id();
	}
}
