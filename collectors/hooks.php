<?php
/**
 * Hooks and actions collector.
 *
 * @package query-monitor
 */

class QM_Collector_Hooks extends QM_Collector {

	public $id = 'hooks';
	protected static $hide_core;

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
