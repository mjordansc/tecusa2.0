<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by James Kemp on 28-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */ declare( strict_types=1 );

namespace Iconic_WooThumbs_NS\StellarWP\Uplink\Auth;

use Iconic_WooThumbs_NS\StellarWP\Uplink\Pipeline\Pipeline;

/**
 * Determines if the current site will allow the user to use the authorize button.
 */
final class Authorizer {

	/**
	 * @var Pipeline
	 */
	private $pipeline;

	/**
	 * @param  Pipeline  $pipeline  The populated pipeline of a set of rules to authorize a user.
	 */
	public function __construct( Pipeline $pipeline ) {
		$this->pipeline = $pipeline;
	}

	/**
	 * Runs the pipeline which executes a series of checks to determine if
	 * the user can use the authorize button on the current site.
	 *
	 * @see Provider::register_authorizer()
	 *
	 * @return bool
	 */
	public function can_auth(): bool {
		return $this->pipeline->send( true )->thenReturn();
	}

}
