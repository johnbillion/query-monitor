<?php declare(strict_types = 1);
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
	 * @var QM_Timer|null Timer.
	 */
	protected $timer;

	public function __construct( QM_Collector $collector ) {
		$this->collector = $collector;
	}

	/**
	 * @return mixed
	 */
	abstract public function get_output();

	/**
	 * @return void
	 */
	public function output() {
		// nothing
	}

	/**
	 * @return QM_Collector
	 */
	public function get_collector() {
		return $this->collector;
	}

	/**
	 * @return QM_Timer|null
	 */
	final public function get_timer() {
		return $this->timer;
	}

	/**
	 * @param QM_Timer $timer
	 * @return void
	 */
	final public function set_timer( QM_Timer $timer ) {
		$this->timer = $timer;
	}

}
}
