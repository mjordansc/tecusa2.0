<?php
/**
 * Metabox engine.
 *
 * @since  1.6.0
 * Copyright (c) TIV.NET INC 2021.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Metabox;

use Automattic\WooCommerce\Admin\Overrides\Order;
use WOOMC\Dependencies\TIVWP\AbstractApp;
use WOOMC\Dependencies\TIVWP\Abstracts\MetaSetInterface;
use WOOMC\Dependencies\TIVWP\Constants;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Dependencies\TIVWP\Logger\Log;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Dependencies\TIVWP\ScreenInfo;
use WOOMC\Dependencies\TIVWP\UniMeta\AbstractUniMeta;
use WOOMC\Dependencies\TIVWP\UniMeta\UniMeta_Factory;

/**
 * Class
 *
 * @since  1.6.0
 */
class MetaboxEngine implements InterfaceHookable {

	/**
	 * Nonce name.
	 *
	 * @since  1.6.0
	 * @var string
	 */
	protected const NONCE_NAME = 'tivwp_metabox_nonce_name';

	/**
	 * Nonce action.
	 *
	 * @since  1.6.0
	 * @var string
	 */
	protected const NONCE_ACTION = 'tivwp_metabox_nonce_action';

	/**
	 * Meta action constant for the tivwp_meta_changed action.
	 *
	 * @since  1.10.0
	 * @var string
	 */
	public const META_ACTION_DELETE_EMPTY = 'delete_empty';

	/**
	 * Meta action constant for the tivwp_meta_changed action.
	 *
	 * @since  1.10.0
	 * @var string
	 */
	public const META_ACTION_DELETE_ABSENT = 'delete_absent';

	/**
	 * Meta action constant for the tivwp_meta_changed action.
	 *
	 * @since  1.10.0
	 * @var string
	 */
	public const META_ACTION_UPDATE = 'update';

	/**
	 * Field types.
	 *
	 * @since 2.7.0
	 * @var array<string, string>
	 */
	protected const FIELD_TYPE = array(
		'ALLOWED_COUNTRIES'  => 'SelectCountries',
		'DATETIME'           => 'DateTime',
		'NUMBER'             => 'Number',
		'PRODUCT_CATEGORIES' => 'SelectProductCategories',
		'PRODUCT_TAGS'       => 'SelectProductTags',
		'PRODUCTS'           => 'SelectProducts',
		'MULTISELECT'        => 'MultiSelect',
		'SELECT'             => 'Select',
		'HIDDEN'             => 'Hidden',
		'DESCRIPTION_ONLY'   => 'DescriptionOnly',
	);

	/**
	 * Method get_metabox_field_type.
	 *
	 * @since 2.7.1
	 *
	 * @param string $field_id
	 *
	 * @return string
	 */
	public static function get_metabox_field_type( string $field_id ): string {
		return self::FIELD_TYPE[ $field_id ] ?? self::FIELD_TYPE['NUMBER'];
	}

	/**
	 * List of the admin screens where to add these metaboxes.
	 *
	 * @since  1.6.0
	 * @var string[]
	 */
	protected $screens = array();

	/**
	 * MetaSet.
	 *
	 * @since  1.6.0
	 * @var MetaSetInterface
	 */
	protected $meta_set;

	/**
	 * Var ScreenInfo.
	 *
	 * @since 2.8.0
	 *
	 * @var ScreenInfo
	 */
	protected ScreenInfo $screen_info;

	/**
	 * Var title_prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var mixed|string
	 */
	protected $title_prefix;

	/**
	 * Constructor.
	 *
	 * @since  1.6.0
	 *
	 * @param MetaSetInterface $meta_set MetaSet.
	 * @param string|string[]  $screens  Screen(s).
	 */
	public function __construct( MetaSetInterface $meta_set, $screens, $title_prefix = '' ) {

		$this->meta_set     = $meta_set;
		$this->screens      = (array) $screens;
		$this->title_prefix = $title_prefix;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function setup_hooks() {
		\add_action( 'current_screen', array( $this, 'action__current_screen' ) );
	}

	/**
	 * Method action__current_screen.
	 *
	 * @since  1.6.0
	 * @since  2.1.6 Do nothing for screen IDs not in $this->screens.
	 *
	 * @param \WP_Screen|mixed $current_screen Current screen.
	 *
	 * @return void
	 */
	public function action__current_screen( $current_screen ): void {

		if ( ! $current_screen instanceof \WP_Screen ) {
			return;
		}

		$this->maybe_convert_screens();
		if ( ! in_array( $current_screen->id, $this->screens, true ) ) {
			return;
		}

		$this->screen_info = new ScreenInfo( $current_screen->id );

		\add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		if ( $this->screen_info->is_order() || $this->screen_info->is_subscription() ) {
			\add_action( 'woocommerce_process_shop_order_meta', array(
				$this,
				'action__woocommerce_process_shop_order_meta',
			), 10, 2 );
		} elseif ( $this->screen_info->is_product() ) {
			\add_action( 'woocommerce_process_product_meta', array(
				$this,
				'action__woocommerce_process_product_meta',
			), AbstractApp::HOOK_PRIORITY_EARLY, 2 );
		} else {
			\add_action( 'save_post', array( $this, 'action__save_post' ) );
		}

		( new WooScriptLoader() )->setup_hooks();
	}

	/**
	 * Maybe convert screens.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	protected function maybe_convert_screens(): void {
		foreach ( $this->screens as &$screen_id ) {
			$screen_id = ScreenInfo::maybe_convert_screen_id( $screen_id );
		}
	}

	/**
	 * Add metaboxes.
	 *
	 * @since    1.6.0
	 * @since    1.9.0 Convert screens.
	 * @since    2.1.6 Convert screens done before.
	 *
	 * @internal action
	 */
	public function add_meta_boxes() {
		$title = $this->meta_set->get_title();
		if ( $this->title_prefix ) {
			$title = $this->title_prefix . ' - ' . $title;
		}
		\add_meta_box(
			'tivwp-metabox-' . $this->screen_info->get_id() . '-' . $this->meta_set->get_type(),
			$title,
			array( $this, 'meta_box_callback' ),
			$this->screen_info->get_screen(),
			'advanced',
			'high'
		);
	}

	/**
	 * Output nonce fields once per screen.
	 *
	 * @since 2.7.0
	 *
	 * @param string $screen_id Screen ID.
	 *
	 * @return void
	 */
	private static function put_nonce_once_per_screen( string $screen_id ): void {
		static $already_done = array();
		if ( ! isset( $already_done[ $screen_id ] ) ) {
			\wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, false );
			$already_done[ $screen_id ] = true;
		}
	}

	/**
	 * Metabox callback.
	 *
	 * @since    1.6.0
	 *
	 * @param \WP_Post|Order $post The Post or Order object.
	 *
	 * @internal callback.
	 */
	public function meta_box_callback( $post ) {
		self::put_nonce_once_per_screen( $this->screen_info->get_id() );

		\add_filter( 'safe_style_css', array( __CLASS__, 'filter__safe_style_css' ) );
		$this->field_generator( $post );
		\remove_filter( 'safe_style_css', array( __CLASS__, 'filter__safe_style_css' ) );
	}

	/**
	 * Metabox content.
	 *
	 * @since  1.6.0
	 *
	 * @param \WP_Post|Order $post The Post or Order object.
	 */
	protected function field_generator( $post ) {

		$uni_meta = UniMeta_Factory::get_object( $post );
		if ( ! $uni_meta ) {
			return;
		}

		$output = '';
		foreach ( $this->meta_set->get_meta_fields() as $meta_field ) {

			if ( ! empty( $meta_field['hide'] ) ) {
				continue;
			}

			try {
				if ( empty( $meta_field['id'] ) ) {
					throw new Message( 'empty( $meta_field["id"] )' );
				}

			} catch ( Message $e ) {
				Log::error( $e );

				continue;
			}

			if ( ! empty( $meta_field['label'] ) ) {
				$label = '<label for="' . $meta_field['id'] . '">' . $meta_field['label'] . '</label>';
			} else {
				$label = '';
			}

			/**
			 * Factory
			 *
			 * @var \WOOMC\Dependencies\TIVWP\WC\Metabox\Fields\InterfaceMetaboxField::render $cls
			 */
			$cls   = __NAMESPACE__ . '\\Fields\\' . $meta_field['type'];
			$input = class_exists( $cls ) ? $cls::render( $meta_field, $uni_meta ) : '';

			if ( static::FIELD_TYPE['HIDDEN'] === $meta_field['type'] ) {
				$output .= $input;
			} else {
				if ( ! empty( $meta_field['desc'] ) ) {
					$input .= HTML::make_tag( 'p',
						array(
							'class' => 'description ' . $meta_field['id'] . '_desc',
						),
						$meta_field['desc']
					);
				}
				$output .= $this->format_rows( $label, $input, $meta_field );
			}
		}

		$allowed_tags = array(
			'script' => true,
			'strong' => true,
			'option' => array(
				'selected' => true,
				'value'    => true,
			),
			'label'  => array( 'for' => true ),
			'input'  => array(
				'aria-label'  => true,
				'class'       => true,
				'data_type'   => true,
				'disabled'    => true,
				'id'          => true,
				'list'        => true,
				'max'         => true,
				'min'         => true,
				'name'        => true,
				'placeholder' => true,
				'readonly'    => true,
				'required'    => true,
				'step'        => true,
				'style'       => true,
				'type'        => true,
				'value'       => true,
			),
			'i'      => array(
				'class' => true,
			),
			'select' => array(
				'aria-label'       => true,
				'class'            => true,
				'data-action'      => true,
				'data-allow_clear' => true,
				'data-exclude'     => true,
				'data-placeholder' => true,
				'data-sortable'    => true,
				'data-taxonomy'    => true,
				'disabled'         => true,
				'id'               => true,
				'multiple'         => true,
				'name'             => true,
				'required'         => true,
				'style'            => true,
			),
			'tr'     => array(
				'class'    => true,
				'style'    => true,
				'disabled' => true,
			),
			'th'     => array(
				'class' => true,
				'scope' => true,
			),
			'td'     => true,
			'p'      => array(
				'id'    => true,
				'style' => true,
				'class' => true,
			),
			'br'     => true,
			'span'   => array(
				'style' => true,
				'class' => true,
			),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'style'  => true,
				'class'  => true,
			),
		);

		echo '<table class="form-table"><tbody>' . \wp_kses( $output, $allowed_tags ) . '</tbody></table>';
	}

	/**
	 * Allow style:opacity in CSS.
	 *
	 * @since 2.7.0
	 *
	 * @param string[] $attr Array of allowed CSS attributes.
	 *
	 * @return string[]
	 */
	public static function filter__safe_style_css( $attr ) {
		$attr[] = 'opacity';
		$attr[] = 'pointer-events';

		return $attr;
	}

	/**
	 * Put values in a table row.
	 *
	 * @since  1.6.0
	 *
	 * @param string $th         Content of the <th> tag.
	 * @param string $td         Content of the <td> tag.
	 * @param array  $meta_field Meta field.
	 *
	 * @return string
	 */
	protected function format_rows( string $th, string $td, array $meta_field ): string {
		$tag_th = HTML::make_tag( 'th', array( 'scope' => 'row', 'class' => 'titledesc' ), $th );
		$tag_td = HTML::make_tag( 'td', array(), $td );

		$attributes_tr = array();
		if ( ! empty( $meta_field['disabled'] ) ) {
			$attributes_tr['style'] = 'opacity:.4;pointer-events:none';
		}

		return HTML::make_tag( 'tr', $attributes_tr, $tag_th . $tag_td );
	}

	/**
	 * Method handle_meta_updates.
	 *
	 * @since 1.9.0
	 *
	 * @param AbstractUniMeta $uni_meta UniMeta object.
	 *
	 * @return void
	 */
	protected function handle_meta_updates( AbstractUniMeta $uni_meta ) {

		$any_updates = false;

		$meta_set = $this->meta_set;
		foreach ( $meta_set->get_meta_fields() as $meta_field ) {
			$meta_key = $meta_field['id'];
			if ( Env::is_parameter_in_http_post( $meta_key ) ) {
				$meta_value = Env::get_http_post_parameter( $meta_key );
				if ( ! $meta_value && ! empty( $meta_field['delete_empty'] ) ) {
					$uni_meta->delete_meta( $meta_key );
					$meta_action = self::META_ACTION_DELETE_EMPTY;

					/**
					 * Action tivwp_meta_deleted.
					 *
					 * @deperecated Use tivwp_meta_changed
					 * @since       1.9.0
					 *
					 * @param array            $meta_field Meta field.
					 * @param MetaSetInterface $meta_set   MetaSet.
					 */
					\do_action( 'tivwp_meta_deleted', $meta_field, $meta_set );
				} else {

					// Do not set meta if value has not changed.
					$existing_value = $uni_meta->get_meta( $meta_key );
					if ( $existing_value === $meta_value ) {
						continue;
					}

					$uni_meta->set_meta( $meta_key, $meta_value );
					$meta_action = self::META_ACTION_UPDATE;

					/**
					 * Action tivwp_meta_updated.
					 *
					 * @deperecated Use tivwp_meta_changed
					 * @since       1.9.0
					 *
					 * @param array            $meta_field Meta field.
					 * @param MetaSetInterface $meta_set   MetaSet.
					 */
					\do_action( 'tivwp_meta_updated', $meta_field, $meta_value, $meta_set );
				}
			} else {
				// Meta not in $POST. Delete.
				$uni_meta->delete_meta( $meta_key );
				$meta_action = self::META_ACTION_DELETE_ABSENT;
				$meta_value  = '';
			}

			/**
			 * Act on meta changes.
			 * We are here only if meta value has changed or meta deleted because was not in $POST.
			 *
			 * @since 1.10.0
			 *
			 * @param string           $meta_action Meta action - see constants.
			 * @param array            $meta_field  Meta field.
			 * @param mixed            $meta_value  Meta value.
			 * @param MetaSetInterface $meta_set    MetaSet.
			 * @param AbstractUniMeta  $uni_meta    UniMeta.
			 */
			\do_action(
				'tivwp_meta_changed',
				$meta_action,
				$meta_field,
				$meta_value,
				$meta_set,
				$uni_meta
			);
			$any_updates = true;
		}

		if ( $any_updates ) {
			$_todo_post_id = 0;
			/**
			 * Act when any meta updated in a metaset.
			 *
			 * @since 2.7.0
			 *
			 * @param MetaSetInterface $meta_set      MetaSet.
			 * @param AbstractUniMeta  $uni_meta      UniMeta.
			 * @param int              $_todo_post_id TODO Post ID.
			 *
			 * @todo  Orders and Products are saved AFTER this!
			 * @todo  Do we need to pass a post ID?
			 */
			\do_action(
				'tivwp_meta_any_updates',
				$meta_set,
				$uni_meta,
				$_todo_post_id
			);
		}
	}

	/**
	 * Save meta - generic.
	 *
	 * @since    1.6.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @internal action.
	 */
	public function action__save_post( int $post_id ) {

		if ( Constants::is_true( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! \wp_verify_nonce( \wc_clean( \wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		$uni_meta = UniMeta_Factory::get_object( $post_id );
		if ( $uni_meta ) {
			$this->handle_meta_updates( $uni_meta );
		}
	}

	/**
	 * Save meta - for shop order.
	 *
	 * @since        1.9.0
	 * @since        2.1.2 Parameter can also be of type WP_Post, {@see \WC_Admin_Meta_Boxes::save_meta_boxes()}
	 *
	 * @param int                $order_id Order ID (Unused).
	 * @param \WC_Order|\WP_Post $order    Order object.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function action__woocommerce_process_shop_order_meta( int $order_id, $order ) {

		$uni_meta = UniMeta_Factory::get_object( $order );
		$this->handle_meta_updates( $uni_meta );
		$uni_meta->save_object();
	}

	/**
	 * Save meta - for product.
	 * do_action( 'woocommerce_process_' . $post->post_type . '_meta', $post_id, $post );
	 *
	 * @since        1.9.0
	 *
	 * @param int            $post_id                  Post ID (Unused).
	 * @param \WP_Post|mixed $post_having_type_product Post object.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function action__woocommerce_process_product_meta( int $post_id, $post_having_type_product ) {

		$uni_meta = UniMeta_Factory::get_object( $post_having_type_product );
		$this->handle_meta_updates( $uni_meta );
		$uni_meta->save_object();
	}
}
