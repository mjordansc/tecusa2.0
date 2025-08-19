<?php
/**
 * Scripting.
 *
 * @since 2.2.0
 * Copyright (c) 2024, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP;

/**
 * Class Scripting
 *
 * @since 2.2.0
 */
class Scripting {

	/**
	 * Available modes.
	 *
	 * @var array
	 */
	public const MODES = array(
		'LAZY' => 'LAZY',
		'WP'   => 'WP',
	);

	/**
	 * Var mode.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	protected static $mode = self::MODES['LAZY'];

	/**
	 * Setter _mode
	 *
	 * @since 2.2.0
	 *
	 * @param string $mode
	 */
	public static function set_mode( string $mode ): void {
		// Check if the provided mode is one of the allowed constants
		if ( array_key_exists( $mode, self::MODES ) ) {
			self::$mode = $mode;
		} else {
			self::$mode = self::MODES['LAZY']; // Default to MODE_LAZY for invalid modes
		}
	}

	/**
	 * Scripts to enqueue.
	 *
	 * @since 2.2.0
	 *
	 * @var array
	 */
	protected static $scripts = array();

	/**
	 * Data scripts to enqueue.
	 *
	 * @since 2.2.0
	 *
	 * @var array
	 */
	protected static $data_scripts = array();

	/**
	 * Static setup_hooks().
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public static function setup_hooks(): void {
		\add_action( 'wp_footer', array( __CLASS__, 'action__wp_footer' ) );
	}

	/**
	 * Method enqueue_script.
	 *
	 * @since 2.2.0
	 *
	 * @param string           $handle   Name of the script. Should be unique.
	 * @param string           $src      Full URL of the script.
	 * @param string[]         $deps     Passed to \wp_enqueue_script()
	 * @param string|bool|null $ver      Optional. String specifying script version number.
	 * @param array|bool       $args     Passed to \wp_enqueue_script()
	 * @param int              $priority To sort the scripts.
	 *
	 * @return void
	 */
	public static function enqueue_script( string $handle, string $src = '', array $deps = array(), $ver = false, $args = array(), int $priority = 10 ): void {

		if ( self::MODES['WP'] === self::$mode ) {
			\wp_enqueue_script( $handle, $src, $deps, $ver, $args );

			return;
		}

		// For the LAZY mode, need to enqueue dependencies.
		foreach ( $deps as $dependency ) {
			\wp_enqueue_script( $dependency );
		}

		if ( $ver && is_string( $ver ) ) {
			$src = \add_query_arg( 'ver', $ver, $src );
		}
		$src = \add_query_arg( 'p', $priority, $src );

		self::$scripts[ $handle ] = array(
			'priority' => $priority,
			'src'      => $src,
		);
	}

	/**
	 * Method enqueue_data_script.
	 *
	 * @since 2.2.0
	 *
	 * @param string $var_name Global var name (for window.{...}).
	 * @param array  $data     The data array to be converted to JS object.
	 * @param int    $priority To sort if needed. Default is 10.
	 *
	 * @return void
	 */
	public static function enqueue_data_script( string $var_name, array $data, int $priority = 10 ): void {
		self::$data_scripts[ $var_name ] = array(
			'priority' => $priority,
			'data'     => $data,
		);
	}

	/**
	 * Method action__wp_footer.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	public static function action__wp_footer(): void {
		self::print_scripts();
	}

	/**
	 * Method print_scripts.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	protected static function print_scripts(): void {

		echo '<script id="tivwp-scripting">' . "\n";
		if ( count( self::$data_scripts ) ) {
			// Data scripts.
			uasort( self::$data_scripts, function ( $a, $b ) {
				return $a['priority'] - $b['priority'];
			} );
			foreach ( self::$data_scripts as $var_name => $data_script ) {
				echo 'window.' . \esc_attr( $var_name ) . '=' . \wp_kses_data( \wp_json_encode( $data_script['data'] ) ) . ";\n";
			}
		}

		if ( count( self::$scripts ) ) {
			// Lazy-loaded scripts.
			usort( self::$scripts, function ( $a, $b ) {
				return $a['priority'] - $b['priority'];
			} );
			?>
			document.addEventListener("DOMContentLoaded", function () {
			const scripts = <?php echo \wp_json_encode( array_column( self::$scripts, 'src' ) ); ?>;
			scripts.forEach(function(scriptUrl) {
			const script = document.createElement("script");
			script.src = scriptUrl;
			document.head.appendChild(script);
			});
			});
			<?php
		}
		echo '</script>' . "\n";
	}
}
