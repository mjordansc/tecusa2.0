<?php
/**
 * Cookie management.
 *
 * @since 2.6.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\Dependencies\TIVWP\Constants;

/**
 * Class Cookie
 */
class Cookie {

	/**
	 * Set the cookie at frontend.
	 * When cookies set by server do not work for some reason (disabled by hosting, etc.).
	 * Note: this breaks caching that uses mod_rewrite to serve pages from disk.
	 *
	 * @since 2.14.4-rc.1
	 * @since 4.3.0-2 Use COOKIEPATH and COOKIE_DOMAIN
	 *
	 * @param string     $name    Name.
	 * @param string|int $value   Value.
	 * @param int|float  $max_age Expiration.
	 *
	 * @return void
	 */
	protected static function set_with_js( $name, $value, $max_age = YEAR_IN_SECONDS ) {

		\add_action( 'wp_print_scripts', function () use ( $name, $value, $max_age ) {
			$path          = COOKIEPATH ? COOKIEPATH : '/';
			$domain_clause = COOKIE_DOMAIN ? ';domain=' . COOKIE_DOMAIN : '';
			//@formatter:off
			?>
			<!--suppress JSUnresolvedVariable -->
			<script>
				document.addEventListener("DOMContentLoaded", function () {
					document.cookie = "<?php echo \esc_js( $name ); ?>=<?php echo \esc_js( $value ); ?>;path=<?php echo \esc_js( $path ); ?>;max-age=<?php echo \esc_js( $max_age ); ?>;samesite=strict<?php echo \esc_js( $domain_clause ); ?>";
				});
			</script>
			<?php
			//@formatter:on
		} );
	}

	/**
	 * Set the cookie.
	 *
	 * @since 2.6.3-rc.2 Do not set JS cookie if the page is cached in browser. Use JS cookies optionally.
	 * @since 2.6.7-beta.2 Added $force parameter.
	 * @since 2.14.4-rc.1 Refactored to set cookie only if changed.
	 * @since 4.3.0-2 Use COOKIEPATH and COOKIE_DOMAIN
	 *
	 * @param string     $name    Name.
	 * @param string|int $value   Value.
	 * @param int|float  $max_age Expiration.
	 * @param bool       $force   Allow repeated setcookie calls.
	 *
	 * @return void
	 */
	public static function set( $name, $value, $max_age = YEAR_IN_SECONDS, $force = false ) {

		/**
		 * Do once during this server request.
		 */
		static $already_done = array();
		if ( ! $force && isset( $already_done[ $name ] ) && $value === $already_done[ $name ] ) {
			return;
		}
		$already_done[ $name ] = $value;

		// Frontend.
		if ( DAO\Factory::getDao()->isClientSideCookies() ) {
			self::set_with_js( $name, $value, $max_age );
			$_COOKIE[ $name ] = $value;

			return;
		}

		//
		// Backend.
		//

		if ( isset( $_COOKIE[ $name ] ) && $value === $_COOKIE[ $name ] ) {
			return;
		}
		$_COOKIE[ $name ] = $value;

		if ( headers_sent() ) {
			return;
		}

		$options = array(
			'expires'  => time() + $max_age,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => false,
			'HttpOnly' => false,
			'SameSite' => 'strict',
		);

		if ( PHP_VERSION_ID >= 70300 ) {
			// Syntax PHP 7.3+.
			setcookie( $name, $value, $options );

			return;
		}

		/**
		 * Older PHP.
		 *
		 * @since 2.8.4-beta.1 Being tested. Might affect my-account login in some cases.
		 */
		if ( Constants::is_true( 'WOOMC_OLD_PHP_SAME_SITE_COOKIES' ) ) {
			// Use header() to be able to set `SameSite`.
			$header_args = implode( '; ', array(
				"{$name}={$value}",
				// RFC 7231. https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Date.
				// DATE_RFC7231 is the correct format, but Zend uses the one with dashes.
				'expires=' . gmdate( 'D, d-M-Y H:i:s', $options['expires'] ) . ' GMT',
				'Max-Age=' . $max_age,
				'path=' . $options['path'],
				'SameSite=' . $options['SameSite'],
			) );

			header( "Set-Cookie: {$header_args}" );
		} else {
			// Standard call, w/o `SameSite`.
			setcookie(
				$name,
				$value,
				$options['expires'],
				$options['path'],
				$options['domain'],
				$options['secure'],
				$options['HttpOnly']
			);
		}
	}
}
