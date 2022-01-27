<?php
/**
 * Multisite collector, used mostly for monitoring use of `switch_to_blog()` and `restore_current_blog()`.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Collector_Multisite extends QM_Collector {
	public $id = 'multisite';

	public function __construct() {
		parent::__construct();

		$this->data['switches'] = array();

		add_action( 'switch_blog', array( $this, 'action_switch_blog' ), 10, 3 );
	}

	/**
	 * Fires when the blog is switched.
	 *
	 * @param int    $new_blog_id  New blog ID.
	 * @param int    $prev_blog_id Previous blog ID.
	 * @param string $context      Additional context. Accepts 'switch' when called from switch_to_blog()
	 *                             or 'restore' when called from restore_current_blog().
	 */
	public function action_switch_blog( $new_blog_id, $prev_blog_id, $context ) {
		if ( intval( $new_blog_id ) === intval( $prev_blog_id ) ) {
			return;
		}

		$this->data['switches'][] = array(
			'new' => $new_blog_id,
			'prev' => $prev_blog_id,
			'to' => ( 'switch' === $context ),
			'trace' => new QM_Backtrace( array(
				'ignore_frames' => 5,
			) ),
		);
	}
}

# Load early to detect as many happenings during the bootstrap process as possible
QM_Collectors::add( new QM_Collector_Multisite() );
