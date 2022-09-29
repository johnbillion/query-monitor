<?php declare(strict_types = 1);
/**
 * Multisite collector, used for monitoring use of `switch_to_blog()` and `restore_current_blog()`.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Multisite>
 */
class QM_Collector_Multisite extends QM_DataCollector {
	public $id = 'multisite';

	public function __construct() {
		parent::__construct();

		$this->data->switches = array();

		add_action( 'switch_blog', array( $this, 'action_switch_blog' ), 10, 3 );
	}

	public function get_storage(): QM_Data {
		return new QM_Data_Multisite();
	}

	/**
	 * Fires when the blog is switched.
	 *
	 * @param int    $new_blog_id  New blog ID.
	 * @param int    $prev_blog_id Previous blog ID.
	 * @param string $context      Additional context. Accepts 'switch' when called from switch_to_blog()
	 *                             or 'restore' when called from restore_current_blog().
	 * @return void
	 */
	public function action_switch_blog( $new_blog_id, $prev_blog_id, $context ) {
		if ( intval( $new_blog_id ) === intval( $prev_blog_id ) ) {
			return;
		}

		$this->data->switches[] = array(
			'new' => $new_blog_id,
			'prev' => $prev_blog_id,
			'to' => ( 'switch' === $context ),
			'trace' => new QM_Backtrace( array(
				'ignore_hook' => array(
					'switch_blog' => true,
				),
				'ignore_func' => array(
					'switch_to_blog' => true,
					'restore_current_blog' => true,
				),
			) ),
		);
	}
}

if ( is_multisite() ) {
	# Load early to detect as many happenings during the bootstrap process as possible
	QM_Collectors::add( new QM_Collector_Multisite() );
}
