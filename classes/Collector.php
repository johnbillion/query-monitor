<?php
/**
 * Abstract data collector.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Collector' ) ) {
abstract class QM_Collector {

	protected $timer;
	protected $data = array(
		'types'           => array(),
		'component_times' => array(),
	);
	protected static $hide_qm = null;

	public $concerned_actions   = array();
	public $concerned_filters   = array();
	public $concerned_constants = array();
	public $tracked_hooks       = array();

	public function __construct() {}

	final public function id() {
		return "qm-{$this->id}";
	}

	abstract public function name();

	protected function log_type( $type ) {

		if ( isset( $this->data['types'][ $type ] ) ) {
			$this->data['types'][ $type ]++;
		} else {
			$this->data['types'][ $type ] = 1;
		}

	}

	protected function maybe_log_dupe( $sql, $i ) {

		$sql = str_replace( array( "\r\n", "\r", "\n" ), ' ', $sql );
		$sql = str_replace( array( "\t", '`' ), '', $sql );
		$sql = preg_replace( '/ +/', ' ', $sql );
		$sql = trim( $sql );

		$this->data['dupes'][ $sql ][] = $i;

	}

	protected function log_component( $component, $ltime, $type ) {

		if ( ! isset( $this->data['component_times'][ $component->name ] ) ) {
			$this->data['component_times'][ $component->name ] = array(
				'component' => $component->name,
				'ltime'     => 0,
				'types'     => array(),
			);
		}

		$this->data['component_times'][ $component->name ]['ltime'] += $ltime;

		if ( isset( $this->data['component_times'][ $component->name ]['types'][ $type ] ) ) {
			$this->data['component_times'][ $component->name ]['types'][ $type ]++;
		} else {
			$this->data['component_times'][ $component->name ]['types'][ $type ] = 1;
		}

	}

	public static function timer_stop_float() {
		global $timestart;
		return microtime( true ) - $timestart;
	}

	public static function format_bool_constant( $constant ) {
		// @TODO this should be in QM_Util

		if ( ! defined( $constant ) ) {
			/* translators: Undefined PHP constant */
			return __( 'undefined', 'query-monitor' );
		} elseif ( ! constant( $constant ) ) {
			return 'false';
		} else {
			return 'true';
		}
	}

	final public function get_data() {
		return $this->data;
	}

	final public function set_id( $id ) {
		$this->id = $id;
	}

	final public function process_concerns() {
		global $wp_filter;

		$tracked = array();
		$id      = $this->id;

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
				$this->concerned_actions[ $action ] = QM_Hook::process( $action, $wp_filter, true, true );
			}
			$tracked[] = $action;
		}

		foreach ( $concerned_filters as $filter ) {
			if ( has_filter( $filter ) ) {
				$this->concerned_filters[ $filter ] = QM_Hook::process( $filter, $wp_filter, true, true );
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
					$this->concerned_filters[ $filter ] = QM_Hook::process( $filter, $wp_filter, true, true );
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

	public function filter_concerns( $concerns ) {
		return ! empty( $concerns['actions'] );
	}

	public static function format_user( WP_User $user_object ) {
		$user = get_object_vars( $user_object->data );
		unset(
			$user['user_pass'],
			$user['user_activation_key']
		);
		$user['roles'] = $user_object->roles;

		return $user;
	}

	public static function hide_qm() {
		if ( null === self::$hide_qm ) {
			self::$hide_qm = ( defined( 'QM_HIDE_SELF' ) && QM_HIDE_SELF );
		}

		return self::$hide_qm;
	}

	public function filter_remove_qm( array $item ) {
		$component = $item['trace']->get_component();
		return ( 'query-monitor' !== $component->context );
	}

	public function process() {}

	public function post_process() {}

	public function tear_down() {}

	public function get_timer() {
		return $this->timer;
	}

	public function set_timer( QM_Timer $timer ) {
		$this->timer = $timer;
	}

	public function get_concerned_actions() {
		return array();
	}

	public function get_concerned_filters() {
		return array();
	}

	public function get_concerned_options() {
		return array();
	}

	public function get_concerned_constants() {
		return array();
	}
}
}
