<?php
/**
 * SQL methods.
 *
 * @since 1.0.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP;

class SQL {

	/**
	 * Builds the SQL IN(...) statement.
	 *
	 * @param string[]|string $items  List of items.
	 * @param string          $format printf format; default is '%s'.
	 *
	 * @return string
	 */
	public static function in( $items, string $format = '%s' ): string {
		/**
		 * WPDB.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$sql = '';

		$items = (array) $items;
		if ( count( $items ) ) {
			$template = implode( ', ', array_fill( 0, count( $items ), "'$format'" ) );
			if ( $wpdb instanceof \wpdb ) {
				$fn  = 'prepare';
				$sql = $wpdb->$fn( $template, $items );
			} else {
				$items = array_map( function ( $item ) {
					return addslashes( $item );
				}, $items );
				$sql   = vsprintf( $template, $items );
			}
		}

		return $sql;
	}
}
