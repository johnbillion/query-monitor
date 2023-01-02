<?php declare(strict_types = 1);
/**
 * Container for data collectors.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Collectors' ) ) {
/**
 * @implements \IteratorAggregate<string, QM_Collector>
 */
class QM_Collectors implements IteratorAggregate {

	/**
	 * @var array<string, QM_Collector>
	 */
	private $items = array();

	/**
	 * @var boolean
	 */
	private $processed = false;

	/**
	 * @return ArrayIterator<string, QM_Collector>
	 */
	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	/**
	 * @param QM_Collector $collector
	 * @return void
	 */
	public static function add( QM_Collector $collector ) {
		$collectors = self::init();

		$collector->set_up();

		$collectors->items[ $collector->id ] = $collector;
	}

	/**
	 * Fetches a collector instance.
	 *
	 * @param string $id The collector ID.
	 * @return QM_Collector|null The collector object.
	 */
	public static function get( $id ) {
		$collectors = self::init();

		return $collectors->items[ $id ] ?? null;
	}

	/**
	 * @return self
	 */
	public static function init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new QM_Collectors();
		}

		return $instance;

	}

	/**
	 * @return void
	 */
	public function process() {
		if ( $this->processed ) {
			return;
		}

		foreach ( $this as $collector ) {
			$collector->tear_down();

			$timer = new QM_Timer();
			$timer->start();

			$collector->process();
			$collector->process_concerns();

			$collector->set_timer( $timer->stop() );
		}

		foreach ( $this as $collector ) {
			$collector->post_process();
		}

		$this->processed = true;
	}

	/**
	 * @return void
	 */
	public static function cease() {
		$collectors = self::init();

		$collectors->processed = true;

		/** @var QM_Collector $collector */
		foreach ( $collectors as $collector ) {
			$collector->tear_down();
			$collector->discard_data();
		}
	}
}
}
