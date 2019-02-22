<?php
/**
 * Hooks and actions collector.
 *
 * @package query-monitor
 */

class QM_Collector_Hooks extends QM_Collector {

	public $id = 'hooks';
	protected static $hide_core;

	public function name() {
		return __( 'Hooks & Actions', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_action( 'qm/listen/start', array( $this, 'action_function_listen_start' ), 10, 1 );
		add_action( 'qm/listen/stop',  array( $this, 'action_function_listen_stop' ), 10, 1 );
	}

	public function action_function_listen_start( $label ) {
		if ( ! array_key_exists( 'discoveries', $this->data ) ) {
			$this->data['discoveries'] = array();
		}

		if ( array_key_exists( $label, $this->data['discoveries'] ) ) {
			return;
		}

		$this->data['discoveries'][ $label ] = array(
			'bounds' => array(
				'start' => new QM_Backtrace(),
				'stop' => null,
			),
			'hooks' => array(),
		);

		add_action( 'all', array( $this, 'action_function_listen_all' ), 0, 1 );
	}

	public function action_function_listen_all( $var = null ) {
		if ( in_array( current_action(), array(
			'qm/listen/start',
			'qm/listen/stop',
		) ) ) {
			return $var;
		}

		global $wp_actions;

		end( $this->data['discoveries'] );
		$label = key( $this->data['discoveries'] );
		$last = end( $this->data['discoveries'][ $label ]['hooks'] );

		if ( current_action() === $last['hook'] ) {
			$i = key( $this->data['discoveries'][ $label ]['hooks'] );
			$this->data['discoveries'][ $label ]['hooks'][ $i ]['count']++;
		} else {
			$this->data['discoveries'][ $label ]['hooks'][] = array(
				'is_action' => array_key_exists( current_action(), $wp_actions ),
				'hook' => current_action(),
				'count' => 1,
			);
		}

		return $var;
	}

	public function action_function_listen_stop( $label ) {
		remove_action( 'all', array( $this, 'action_function_listen_all' ), 0, 1 );
		$this->data['discoveries'][ $label ]['bounds']['stop'] = new QM_Backtrace();
	}

	public function process() {

		global $wp_actions, $wp_filter;

		self::$hide_qm   = self::hide_qm();
		self::$hide_core = ( defined( 'QM_HIDE_CORE_ACTIONS' ) && QM_HIDE_CORE_ACTIONS );

		$hooks      = array();
		$all_parts  = array();
		$components = array();

		if ( has_filter( 'all' ) ) {
			$hooks[] = QM_Hook::process( 'all', $wp_filter, self::$hide_qm, self::$hide_core );
		}

		if ( defined( 'QM_SHOW_ALL_HOOKS' ) && QM_SHOW_ALL_HOOKS ) {
			// Show all hooks
			$hook_names = array_keys( $wp_filter );
		} else {
			// Only show action hooks that have been called at least once
			$hook_names = array_keys( $wp_actions );
		}

		foreach ( $hook_names as $name ) {

			$hook    = QM_Hook::process( $name, $wp_filter, self::$hide_qm, self::$hide_core );
			$hooks[] = $hook;

			$all_parts  = array_merge( $all_parts, $hook['parts'] );
			$components = array_merge( $components, $hook['components'] );

		}

		$this->data['hooks']      = $hooks;
		$this->data['parts']      = array_unique( array_filter( $all_parts ) );
		$this->data['components'] = array_unique( array_filter( $components ) );

		usort( $this->data['parts'], 'strcasecmp' );
		usort( $this->data['components'], 'strcasecmp' );
	}

}

# Load early to catch all hooks
QM_Collectors::add( new QM_Collector_Hooks() );
