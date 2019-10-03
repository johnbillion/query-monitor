<?php
/**
 * Abstract output handler.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Output' ) ) {
abstract class QM_Output {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector Collector.
	 */
	protected $collector;

	/**
	 * Timer instance.
	 *
	 * @var QM_Timer Timer.
	 */
	protected $timer;

	public function __construct( QM_Collector $collector ) {
		$this->collector = $collector;
	}

	abstract public function get_output();

	public function output() {
		// nothing
	}

	public function get_collector() {
		return $this->collector;
	}

	final public function get_timer() {
		return $this->timer;
	}

	final public function set_timer( QM_Timer $timer ) {
		$this->timer = $timer;
	}

}
}
