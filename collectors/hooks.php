<?php declare(strict_types = 1);
/**
 * Hooks and actions collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Hooks>
 */
class QM_Collector_Hooks extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'hooks';

	/**
	 * @var bool
	 */
	protected static $hide_core;

	public function get_storage(): QM_Data {
		return new QM_Data_Hooks();
	}

	/**
	 * @return void
	 */
	public function process() {
		/**
		 * @var array<string, int> $wp_actions
		 * @var array<string, WP_Hook> $wp_filter
		 */
		global $wp_actions, $wp_filter;

		self::$hide_qm = self::hide_qm();
		self::$hide_core = ( defined( 'QM_HIDE_CORE_ACTIONS' ) && QM_HIDE_CORE_ACTIONS );

		$hooks = array();
		$all_parts = array();
		$components = array();

		if ( has_action( 'all' ) ) {
			$hooks[] = QM_Hook::process( 'all', 'action', $wp_filter, self::$hide_qm, self::$hide_core );
		}

		$this->data->all_hooks = defined( 'QM_SHOW_ALL_HOOKS' ) && QM_SHOW_ALL_HOOKS;

		if ( $this->data->all_hooks ) {
			// Show all hooks
			$hook_names = array_keys( $wp_filter );
		} else {
			// Only show action hooks that have been called at least once
			$hook_names = array_keys( $wp_actions );
		}

		foreach ( $hook_names as $name ) {
			$type = 'action';

			if ( $this->data->all_hooks ) {
				$type = array_key_exists( $name, $wp_actions ) ? 'action' : 'filter';
			}

			$hook = QM_Hook::process( $name, $type, $wp_filter, self::$hide_qm, self::$hide_core );
			$hooks[] = $hook;

			$all_parts = array_merge( $all_parts, $hook['parts'] );
			$components = array_merge( $components, $hook['components'] );

		}

		$this->data->hooks = $hooks;
		$this->data->parts = array_unique( array_filter( $all_parts ) );
		$this->data->components = array_unique( array_filter( $components ) );

		usort( $this->data->parts, 'strcasecmp' );
		usort( $this->data->components, 'strcasecmp' );
	}

}

# Load early to catch all hooks
QM_Collectors::add( new QM_Collector_Hooks() );
