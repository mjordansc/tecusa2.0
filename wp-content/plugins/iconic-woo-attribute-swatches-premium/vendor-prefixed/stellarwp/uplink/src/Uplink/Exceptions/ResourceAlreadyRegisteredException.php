<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by James Kemp on 28-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Iconic_WAS_NS\StellarWP\Uplink\Exceptions;

class ResourceAlreadyRegisteredException extends \Exception {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Resource slug.
	 */
	public function __construct( $slug ) {
		parent::__construct( sprintf( __( 'The resource "%s" is already registered.', 'iconic-was' ), $slug ) );
	}
}
