<?php
/**
 * ScreenInfo
 *
 * @since 2.8.0
 *
 * Copyright (c) 2025, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP;

/**
 * Class ScreenInfo
 *
 * @since 2.8.0
 */
class ScreenInfo {

	/**
	 * Screen types
	 *
	 * @since 2.8.0
	 */
	public const TYPE = array(
		'PRODUCT'      => 'product',
		'ORDER'        => 'shop_order',
		'SUBSCRIPTION' => 'shop_subscription',
	);

	/**
	 * Var screen.
	 *
	 * @since 2.8.0
	 *
	 * @var \WP_Screen
	 */
	protected \WP_Screen $screen;

	/**
	 * Method get_screen.
	 *
	 * @since        2.8.0
	 * @return \WP_Screen
	 * @noinspection PhpUnused
	 */
	public function get_screen(): \WP_Screen {
		return $this->screen;
	}

	/**
	 * Var id.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Method get_id.
	 *
	 * @since 2.8.0
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Constructor ScreenInfo
	 *
	 * @since 2.8.0
	 *
	 * @param string $id
	 */
	public function __construct( string $id = '' ) {

		if ( is_string( $id ) && $id ) {
			$screen = \WP_Screen::get( $id );
		} else {
			$screen = \get_current_screen();
		}

		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}

		$this->screen = $screen;
		$this->id     = $screen->id;
	}

	/**
	 * Method is_exists.
	 *
	 * @since 2.8.0
	 * @return bool
	 */
	public function is_exists(): bool {
		return isset( $this->id );
	}

	/**
	 * Convert screen ID to new HPOS version.
	 *
	 * @since 2.8.0
	 *
	 * @param string $screen_id The screen ID to potentially convert.
	 *
	 * @return string The converted or original screen ID.
	 */
	public static function maybe_convert_screen_id( string $screen_id ): string {
		return function_exists( 'wc_get_page_screen_id' ) ? \wc_get_page_screen_id( $screen_id ) : $screen_id;
	}

	/**
	 * Checks if the given screen ID matches a specific type (product, order, or subscription).
	 *
	 * @since        2.8.0
	 *
	 * @param string $type The type to check against.
	 *
	 * @return bool True if the screen ID matches the type, false otherwise.
	 * @noinspection PhpUnused
	 */
	public function is_id_of_type( string $type ): bool {
		switch ( $type ) {
			case self::TYPE['PRODUCT']:
				return self::TYPE['PRODUCT'] === $this->id;

			case self::TYPE['ORDER']:
				if ( self::TYPE['ORDER'] === $this->id ) {
					return true;
				}

				return self::maybe_convert_screen_id( self::TYPE['ORDER'] ) === $this->id;

			case self::TYPE['SUBSCRIPTION']:
				if ( self::TYPE['SUBSCRIPTION'] === $this->id ) {
					return true;
				}

				return self::maybe_convert_screen_id( self::TYPE['SUBSCRIPTION'] ) === $this->id;

			default:
				return false;
		}
	}

	/**
	 * Method is_order.
	 *
	 * @since 2.8.0
	 * @return bool
	 */
	public function is_order(): bool {
		return $this->is_id_of_type( self::TYPE['ORDER'] );
	}

	/**
	 * Method is_subscription.
	 *
	 * @since 2.8.0
	 * @return bool
	 */
	public function is_subscription(): bool {
		return $this->is_id_of_type( self::TYPE['SUBSCRIPTION'] );
	}

	/**
	 * Method is_product.
	 *
	 * @since 2.8.0
	 * @return bool
	 */
	public function is_product(): bool {
		return $this->is_id_of_type( self::TYPE['PRODUCT'] );
	}
}
