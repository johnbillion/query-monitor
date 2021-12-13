<?php
/**
 * Container for data collectors.
 *
 * @package query-monitor
 */

if ( ! class_exists( 'QM_Collectors' ) ) {
class QM_Collectors implements IteratorAggregate {

	/** @var QM_Collector[] */
	private $items = array();
	private $processed = false;

	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	public static function add( QM_Collector $collector ) {
		$collectors = self::init();

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
		if ( isset( $collectors->items[ $id ] ) ) {
			return $collectors->items[ $id ];
		}
		return null;
	}

	public static function init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new QM_Collectors();
		}

		return $instance;

	}

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

	public static function cease() {
		$collectors = self::init();

		/** @var QM_Collector $collector */
		foreach ( $collectors as $collector ) {
			$collector->tear_down();
			$collector->discard_data();
		}
	}
}
}
