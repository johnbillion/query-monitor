<?php declare(strict_types = 1);
/**
 * Container for dispatchers.
 *
 * @package query-monitor
 */

/**
 * @implements \IteratorAggregate<string, QM_Dispatcher>
 */
class QM_Dispatchers implements IteratorAggregate {

	/**
	 * @var array<string, QM_Dispatcher>
	 */
	private $items = array();

	/**
	 * @return ArrayIterator<string, QM_Dispatcher>
	 */
	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	/**
	 * @param QM_Dispatcher $dispatcher
	 * @return void
	 */
	public static function add( QM_Dispatcher $dispatcher ) {
		$dispatchers = self::init();
		$dispatchers->items[ $dispatcher->id ] = $dispatcher;
	}

	/**
	 * @param string $id
	 * @return QM_Dispatcher|false
	 */
	public static function get( $id ) {
		$dispatchers = self::init();

		return $dispatchers->items[ $id ] ?? false;
	}

	/**
	 * @return void
	 */
	public static function cease() {
		$dispatchers = self::init();

		/** @var QM_Dispatcher $dispatcher */
		foreach ( $dispatchers as $dispatcher ) {
			$dispatcher->cease();
		}
	}

	/**
	 * @return self
	 */
	public static function init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new QM_Dispatchers();
		}

		return $instance;

	}

}
