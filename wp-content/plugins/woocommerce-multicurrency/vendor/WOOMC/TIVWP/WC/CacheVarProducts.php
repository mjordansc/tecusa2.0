<?php
/**
 * CacheVarProducts
 *
 * @since 2.1.0
 *
 * Copyright (c) 2023, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\WC;

use WOOMC\Dependencies\TIVWP\Abstracts\AbstractCacheVar;

/**
 * Class CacheVarProducts
 *
 * @since 2.1.0
 */
class CacheVarProducts extends AbstractCacheVar {


	/**
	 * Method is_cached.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $product_id Product ID.
	 * @param string $property   Product property: is_purchasable, is_active, etc.
	 *
	 * @return bool
	 */
	public static function is_cached( int $product_id, string $property ): bool {
		return isset( self::$cache[ $product_id ][ $property ] );
	}

	/**
	 * Method from_cache.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $product_id Product ID.
	 * @param string $property   Product property: is_purchasable, is_active, etc.
	 *
	 * @return bool|null
	 */
	public static function from_cache( int $product_id, string $property ): ?bool {
		return self::$cache[ $product_id ][ $property ] ?? null;
	}

	/**
	 * Method to_cache.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $product_id Product ID.
	 * @param string $property   Product property: is_purchasable, is_active, etc.
	 * @param bool   $value      The property value.
	 *
	 * @return void
	 */
	public static function to_cache( int $product_id, string $property, bool $value ) {
		self::$cache[ $product_id ][ $property ] = $value;
	}

	/**
	 * Method get.
	 *
	 * @since 2.1.0
	 *
	 * @param int        $product_id Product ID.
	 * @param string     $property   Product property: is_purchasable, is_active, etc.
	 * @param callable   $method     Method to get the property value.
	 * @param mixed|null $args       [Optional] Arguments to the Method.
	 *
	 * @return bool|null
	 */
	public static function get( int $product_id, string $property, callable $method, $args = null ): ?bool {
		if ( ! self::is_cached( $product_id, $property ) ) {
			self::to_cache( $product_id, $property,
				null === $args ? $method() : $method( $args )
			);
		}

		return self::from_cache( $product_id, $property );
	}
}
