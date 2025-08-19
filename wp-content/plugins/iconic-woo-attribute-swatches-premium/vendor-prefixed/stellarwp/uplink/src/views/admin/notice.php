<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by James Kemp on 28-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */ declare( strict_types=1 );
/**
 * Render a WordPress dashboard notice.
 *
 * @see \Iconic_WAS_NS\StellarWP\Uplink\Notice\Notice_Controller
 *
 * @var string $message The message to display.
 * @var string $classes The CSS classes for the notice.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="<?php echo esc_attr( $classes ) ?>">
	<p><?php echo esc_html( $message ) ?></p>
</div>
