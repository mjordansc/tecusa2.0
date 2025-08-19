<?php
/**
 * Abstract: Currency switcher widget.
 *
 * @since 2.12.0-beta.1
 */

// Copyright (c) 2023, TIV.NET INC. All Rights Reserved.

namespace WOOMC\Currency\Switcher;

/**
 * Class Widget
 */
abstract class AbstractSwitcherWidget extends \WP_Widget {

	/**
	 * Initialize widget settings.
	 */
	abstract protected function init_settings();

	// =================================

	/**
	 * Widget settings.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Widget constructor.
	 */
	public function __construct() {

		$this->init_settings();

		$widget_options = array(
			'classname'                   => $this->settings['classname'],
			'description'                 => $this->settings['description'],
			'customize_selective_refresh' => true,
		);

		parent::__construct(
			$this->settings['id_base'],
			$this->settings['name'],
			$widget_options
		);
	}

	/**
	 * Default arguments.
	 *
	 * @return array
	 */
	protected function default_args() {
		return array(
			'title'  => $this->settings['title'],
			'format' => $this->settings['default_format'],
			'flag'   => 0,
		);
	}

	/**
	 * Widget front-end.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		// Defaults.
		$instance = \wp_parse_args( (array) $instance, $this->default_args() );

		/**
		 * Filter widget_title.
		 *
		 * @since 2.12.0-beta.1
		 */
		$title = \apply_filters( 'widget_title', \sanitize_text_field( $instance['title'] ) );

		$format = \sanitize_text_field( $instance['format'] );
		$flag   = $instance['flag'] ? 1 : 0;

		// Before and after widget arguments are defined by themes.
		echo \wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo \wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		// Run the code and display the output.
		echo \do_shortcode( '[' . $this->settings['tag'] . ' format="' . $format . '" flag="' . $flag . '"]' );

		echo \wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Actions before the form output starts.
	 */
	protected function before_form() {
		echo '<p class="wp-ui-highlight">';
		echo \esc_html( $this->settings['description'] );
		echo '</p>';
	}

	/**
	 * Widget back-end.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {

		// Defaults.
		$instance = \wp_parse_args( (array) $instance, $this->default_args() );

		$instance['title']  = \sanitize_text_field( $instance['title'] );
		$instance['format'] = \sanitize_text_field( $instance['format'] );
		$instance['flag']   = (int) (bool) $instance['flag'];

		// Widget admin form.
		$this->before_form();
		?>
		<p>
			<label for="<?php echo \esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php \esc_html_e( 'Title:' ); ?></label>
			<br/>
			<!--suppress XmlDefaultAttributeValue -->
			<input
					class="widefat" id="<?php echo \esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo \esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
					value="<?php echo \esc_attr( $instance['title'] ); ?>"/>
		</p>
		<p>
			<label for="<?php echo \esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php echo \esc_html( $this->settings['admin_format_label'] ); ?></label>
			<br/>
			<?php
			/**
			 * The "format" input INTENTIONALLY does not have `type="text"`.
			 * 1. WPGlobus does not add its Globe icon to translate this field.
			 * 2. WP does not show its content adjacent to the widget title (bug?).
			 */
			?>
			<input
					class="widefat" id="<?php echo \esc_attr( $this->get_field_id( 'format' ) ); ?>"
					name="<?php echo \esc_attr( $this->get_field_name( 'format' ) ); ?>"
					value="<?php echo \esc_attr( $instance['format'] ); ?>"/>
			<br/>
			<small><?php echo \esc_html( $this->settings['admin_format_example'] ); ?><?php echo \esc_attr( $this->settings['default_format'] ); ?></small>
		</p>
		<p>
			<input
					type="checkbox" id="<?php echo \esc_attr( $this->get_field_id( 'flag' ) ); ?>"
					name="<?php echo \esc_attr( $this->get_field_name( 'flag' ) ); ?>"<?php \checked( $instance['flag'] ); ?>/>
			<label for="<?php echo \esc_attr( $this->get_field_id( 'flag' ) ); ?>"><?php echo \esc_html( $this->settings['admin_flag_label'] ); ?></label>
		</p>
		<?php
	}

	/**
	 * Updating widget replacing old instances with new.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['title']  = \sanitize_text_field( $new_instance['title'] );
		$instance['format'] = ! empty( $new_instance['format'] ) ? \sanitize_text_field( $new_instance['format'] ) : '';
		$instance['flag']   = ! empty( $new_instance['flag'] );

		return $instance;
	}
}
