<?php
/**
 * Plugins collector.
 *
 * @package query-monitor
 */

class QM_Collector_Plugins extends QM_Collector {

	public $id = 'plugins';
	protected static $hide_core;
	private $start;

	public function __construct() {
		$this->data['plugins'] = array();
		$active_and_valid_plugins = wp_get_active_and_valid_plugins();
		foreach ( $active_and_valid_plugins as $plugin ) {
			$name = dirname( plugin_basename( $plugin ) );
			$this->data['plugins'][ $name ]['file'] = $plugin;
		}

		if ( ! empty( $this->data['plugins'] ) ) {
			add_action( 'mu_plugin_loaded', array( $this, 'action_plugin_loaded' ), 0 );
			add_action( 'network_plugin_loaded', array( $this, 'action_plugin_loaded' ), 0 );
			add_action( 'plugin_loaded', array( $this, 'action_plugin_loaded' ), 0 );

			$this->start = microtime( true );
		}
  }

	protected function action_plugin_loaded( $plugin ) {
		$name = dirname( plugin_basename( $plugin ) );

		$this->data['plugins'][ $name ]['load_time'] = microtime( true ) - $this->start;

		$this->start = microtime( true );
	}
}

class QM_Collector_Plugins_Hooks extends QM_Collector {

	public $id = 'plugins-hooks';
	protected static $hide_core;

}

# Load early to catch all plugins
QM_Collectors::add( new QM_Collector_Plugins() );
QM_Collectors::add( new QM_Collector_Plugins_Hooks() );
