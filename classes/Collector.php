<?php declare(strict_types = 1);
/**
 * Abstract data collector.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Collector' ) ) {
abstract class QM_Collector {

	/**
	 * @var QM_Timer|null
	 */
	protected $timer;

	/**
	 * @var QM_Data
	 */
	protected $data;

	/**
	 * @var bool|null
	 */
	protected static $hide_qm = null;

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $concerned_actions = array();

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $concerned_filters = array();

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $concerned_constants = array();

	/**
	 * @var array<int, string>
	 */
	public $tracked_hooks = array();

	/**
	 * @var string
	 */
	public $id = '';

	public function __construct() {
		$this->data = $this->get_storage();
	}

	/**
	 * @return void
	 */
	public function set_up() {}

	/**
	 * @return string
	 */
	final public function id() {
		return "qm-{$this->id}";
	}

	/**
	 * @param string $type
	 * @return void
	 */
	protected function log_type( $type ) {
		if ( isset( $this->data->types[ $type ] ) ) {
			$this->data->types[ $type ]++;
		} else {
			$this->data->types[ $type ] = 1;
		}
	}

	/**
	 * @param QM_Component $component
	 * @param float $ltime
	 * @param string|int $type
	 * @return void
	 */
	protected function log_component( $component, $ltime, $type ) {
		if ( ! isset( $this->data->component_times[ $component->name ] ) ) {
			$this->data->component_times[ $component->name ] = array(
				'component' => $component->name,
				'ltime' => 0,
				'types' => array(),
			);
		}

		$this->data->component_times[ $component->name ]['ltime'] += $ltime;

		if ( isset( $this->data->component_times[ $component->name ]['types'][ $type ] ) ) {
			$this->data->component_times[ $component->name ]['types'][ $type ]++;
		} else {
			$this->data->component_times[ $component->name ]['types'][ $type ] = 1;
		}

	}

	/**
	 * @return float
	 */
	public static function timer_stop_float() {
		return microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'];
	}

	/**
	 * @param string $constant
	 * @return string
	 */
	public static function format_bool_constant( $constant ) {
		// @TODO this should be in QM_Util

		if ( ! defined( $constant ) ) {
			/* translators: Undefined PHP constant */
			return __( 'undefined', 'query-monitor' );
		} elseif ( is_string( constant( $constant ) ) && ! is_numeric( constant( $constant ) ) ) {
			return constant( $constant );
		} elseif ( ! constant( $constant ) ) {
			return 'false';
		} else {
			return 'true';
		}
	}

	/**
	 * @return QM_Data
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * @return QM_Data
	 */
	public function get_storage(): QM_Data {
		return new QM_Data_Fallback();
	}

	/**
	 * @return void
	 */
	final public function discard_data() {
		$this->data = $this->get_storage();
	}

	/**
	 * @param string $id
	 * @return void
	 */
	final public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return void
	 */
	final public function process_concerns() {
		global $wp_filter;

		$tracked = array();
		$id = $this->id;

		/**
		 * Filters the concerned actions for the given panel.
		 *
		 * The dynamic portion of the hook name, `$id`, refers to the collector ID, which is typically the `$id`
		 * property of the collector class.
		 *
		 * @since 3.3.0
		 *
		 * @param string[] $actions Array of action names that this panel concerns itself with.
		 */
		$concerned_actions = apply_filters( "qm/collect/concerned_actions/{$id}", $this->get_concerned_actions() );

		/**
		 * Filters the concerned filters for the given panel.
		 *
		 * The dynamic portion of the hook name, `$id`, refers to the collector ID, which is typically the `$id`
		 * property of the collector class.
		 *
		 * @since 3.3.0
		 *
		 * @param string[] $filters Array of filter names that this panel concerns itself with.
		 */
		$concerned_filters = apply_filters( "qm/collect/concerned_filters/{$id}", $this->get_concerned_filters() );

		/**
		 * Filters the concerned options for the given panel.
		 *
		 * The dynamic portion of the hook name, `$id`, refers to the collector ID, which is typically the `$id`
		 * property of the collector class.
		 *
		 * @since 3.3.0
		 *
		 * @param string[] $options Array of option names that this panel concerns itself with.
		 */
		$concerned_options = apply_filters( "qm/collect/concerned_options/{$id}", $this->get_concerned_options() );

		/**
		 * Filters the concerned constants for the given panel.
		 *
		 * The dynamic portion of the hook name, `$id`, refers to the collector ID, which is typically the `$id`
		 * property of the collector class.
		 *
		 * @since 3.3.0
		 *
		 * @param string[] $constants Array of constant names that this panel concerns itself with.
		 */
		$concerned_constants = apply_filters( "qm/collect/concerned_constants/{$id}", $this->get_concerned_constants() );

		foreach ( $concerned_actions as $action ) {
			if ( has_action( $action ) ) {
				$this->concerned_actions[ $action ] = QM_Hook::process( $action, $wp_filter, true, false );
			}
			$tracked[] = $action;
		}

		foreach ( $concerned_filters as $filter ) {
			if ( has_filter( $filter ) ) {
				$this->concerned_filters[ $filter ] = QM_Hook::process( $filter, $wp_filter, true, false );
			}
			$tracked[] = $filter;
		}

		$option_filters = array(
			// Should this include the pre_delete_ and pre_update_ filters too?
			'pre_option_%s',
			'default_option_%s',
			'option_%s',
			'pre_site_option_%s',
			'default_site_option_%s',
			'site_option_%s',
		);

		foreach ( $concerned_options as $option ) {
			foreach ( $option_filters as $option_filter ) {
				$filter = sprintf(
					$option_filter,
					$option
				);
				if ( has_filter( $filter ) ) {
					$this->concerned_filters[ $filter ] = QM_Hook::process( $filter, $wp_filter, true, false );
				}
				$tracked[] = $filter;
			}
		}

		$this->concerned_actions = array_filter( $this->concerned_actions, array( $this, 'filter_concerns' ) );
		$this->concerned_filters = array_filter( $this->concerned_filters, array( $this, 'filter_concerns' ) );

		foreach ( $concerned_constants as $constant ) {
			if ( defined( $constant ) ) {
				$this->concerned_constants[ $constant ] = constant( $constant );
			}
		}

		sort( $tracked );

		$this->tracked_hooks = $tracked;
	}

	/**
	 * @param array<string, mixed> $concerns
	 * @return bool
	 */
	public function filter_concerns( $concerns ) {
		return ! empty( $concerns['actions'] );
	}

	/**
	 * @param WP_User $user_object
	 * @return array<string, mixed>
	 */
	public static function format_user( WP_User $user_object ) {
		$user = get_object_vars( $user_object->data );
		unset(
			$user['user_pass'],
			$user['user_activation_key']
		);
		$user['roles'] = $user_object->roles;

		return $user;
	}

	/**
	 * @return bool
	 */
	public static function enabled() {
		return true;
	}

	/**
	 * @return bool
	 */
	public static function hide_qm() {
		if ( ! defined( 'QM_HIDE_SELF' ) ) {
			return false;
		}

		if ( null === self::$hide_qm ) {
			self::$hide_qm = QM_HIDE_SELF;
		}

		return self::$hide_qm;
	}

	/**
	 * @param array<string, mixed> $item
	 * @phpstan-param array{
	 *   component: QM_Component,
	 * } $item
	 * @return bool
	 */
	public function filter_remove_qm( array $item ) {
		return ( 'query-monitor' !== $item['component']->context );
	}

	/**
	 * @param mixed[] $items
	 * @return bool
	 */
	public function filter_dupe_items( $items ) {
		return ( count( $items ) > 1 );
	}

	/**
	 * @return void
	 */
	public function process() {}

	/**
	 * @return void
	 */
	public function post_process() {}

	/**
	 * @return void
	 */
	public function tear_down() {}

	/**
	 * @return QM_Timer|null
	 */
	public function get_timer() {
		return $this->timer;
	}

	/**
	 * @param QM_Timer $timer
	 * @return void
	 */
	public function set_timer( QM_Timer $timer ) {
		$this->timer = $timer;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_options() {
		return array();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_constants() {
		return array();
	}
}
}
