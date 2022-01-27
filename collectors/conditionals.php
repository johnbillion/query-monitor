<?php
/**
 * Template conditionals collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Conditionals extends QM_Collector {

	public $id = 'conditionals';

	/**
	 * @return void
	 */
	public function process() {

		/**
		 * Allows users to filter the names of conditional functions that are exposed by QM.
		 *
		 * @since 2.7.0
		 *
		 * @param string[] $conditionals The list of conditional function names.
		 */
		$conds = apply_filters( 'qm/collect/conditionals', array(
			'is_404',
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_blog_admin',
			'is_category',
			'is_comment_feed',
			'is_customize_preview',
			'is_date',
			'is_day',
			'is_embed',
			'is_favicon',
			'is_feed',
			'is_front_page',
			'is_home',
			'is_main_network',
			'is_main_site',
			'is_month',
			'is_network_admin',
			'is_page',
			'is_page_template',
			'is_paged',
			'is_post_type_archive',
			'is_preview',
			'is_privacy_policy',
			'is_robots',
			'is_rtl',
			'is_search',
			'is_single',
			'is_singular',
			'is_ssl',
			'is_sticky',
			'is_tag',
			'is_tax',
			'is_time',
			'is_trackback',
			'is_user_admin',
			'is_year',
		) );

		/**
		 * This filter is deprecated. Please use `qm/collect/conditionals` instead.
		 *
		 * @since 2.7.0
		 *
		 * @param string[] $conditionals The list of conditional function names.
		 */
		$conds = apply_filters( 'query_monitor_conditionals', $conds );

		$true = array();
		$false = array();
		$na = array();

		foreach ( $conds as $cond ) {
			if ( function_exists( $cond ) ) {
				$id = null;
				if ( ( 'is_sticky' === $cond ) && ! get_post( $id ) ) {
					# Special case for is_sticky to prevent PHP notices
					$false[] = $cond;
				} elseif ( ! is_multisite() && in_array( $cond, array( 'is_main_network', 'is_main_site' ), true ) ) {
					# Special case for multisite conditionals to prevent them from being annoying on single site installations
					$na[] = $cond;
				} else {
					if ( call_user_func( $cond ) ) {
						$true[] = $cond;
					} else {
						$false[] = $cond;
					}
				}
			} else {
				$na[] = $cond;
			}
		}
		$this->data['conds'] = compact( 'true', 'false', 'na' );

	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_conditionals( array $collectors, QueryMonitor $qm ) {
	$collectors['conditionals'] = new QM_Collector_Conditionals();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_conditionals', 10, 2 );
