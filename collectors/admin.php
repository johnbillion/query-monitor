<?php
/**
 * Admin screen collector.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Collector_Admin extends QM_Collector {

	public $id = 'response';

	public function get_concerned_actions() {
		$actions = array(
			'current_screen',
			'admin_notices',
			'all_admin_notices',
			'network_admin_notices',
			'user_admin_notices',
		);

		if ( ! empty( $this->data['list_table'] ) ) {
			$actions[] = $this->data['list_table']['column_action'];
		}

		return $actions;
	}

	public function get_concerned_filters() {
		$filters = array();

		if ( ! empty( $this->data['list_table'] ) ) {
			$filters[] = $this->data['list_table']['columns_filter'];
			$filters[] = $this->data['list_table']['sortables_filter'];
		}

		return $filters;
	}

	public function process() {

		global $pagenow, $wp_list_table;

		$current_screen = get_current_screen();

		if ( isset( $_GET['page'] ) && null !== $current_screen ) { // phpcs:ignore
			$this->data['base'] = $current_screen->base;
		} else {
			$this->data['base'] = $pagenow;
		}

		$this->data['pagenow']        = $pagenow;
		$this->data['typenow']        = isset( $GLOBALS['typenow'] ) ? $GLOBALS['typenow'] : '';
		$this->data['taxnow']         = isset( $GLOBALS['taxnow'] ) ? $GLOBALS['taxnow'] : '';
		$this->data['hook_suffix']    = isset( $GLOBALS['hook_suffix'] ) ? $GLOBALS['hook_suffix'] : '';
		$this->data['current_screen'] = ( $current_screen ) ? get_object_vars( $current_screen ) : null;

		$screens = array(
			'edit'            => true,
			'edit-comments'   => true,
			'edit-tags'       => true,
			'link-manager'    => true,
			'plugins'         => true,
			'plugins-network' => true,
			'sites-network'   => true,
			'themes-network'  => true,
			'upload'          => true,
			'users'           => true,
			'users-network'   => true,
		);

		if ( ! empty( $this->data['current_screen'] ) && isset( $screens[ $this->data['current_screen']['base'] ] ) ) {

			$list_table = array();

			# And now, WordPress' legendary inconsistency comes into play:

			if ( ! empty( $this->data['current_screen']['taxonomy'] ) ) {
				$list_table['column'] = $this->data['current_screen']['taxonomy'];
			} elseif ( ! empty( $this->data['current_screen']['post_type'] ) ) {
				$list_table['column'] = $this->data['current_screen']['post_type'] . '_posts';
			} else {
				$list_table['column'] = $this->data['current_screen']['base'];
			}

			if ( ! empty( $this->data['current_screen']['post_type'] ) && empty( $this->data['current_screen']['taxonomy'] ) ) {
				$list_table['columns'] = $this->data['current_screen']['post_type'] . '_posts';
			} else {
				$list_table['columns'] = $this->data['current_screen']['id'];
			}

			if ( 'edit-comments' === $list_table['column'] ) {
				$list_table['column'] = 'comments';
			} elseif ( 'upload' === $list_table['column'] ) {
				$list_table['column'] = 'media';
			} elseif ( 'link-manager' === $list_table['column'] ) {
				$list_table['column'] = 'link';
			}

			$list_table['sortables'] = $this->data['current_screen']['id'];

			$this->data['list_table'] = array(
				'columns_filter'   => "manage_{$list_table['columns']}_columns",
				'sortables_filter' => "manage_{$list_table['sortables']}_sortable_columns",
				'column_action'    => "manage_{$list_table['column']}_custom_column",
			);

			if ( ! empty( $wp_list_table ) ) {
				$this->data['list_table']['class_name'] = get_class( $wp_list_table );
			}
		}

	}

}

function register_qm_collector_admin( array $collectors, QueryMonitor $qm ) {
	$collectors['response'] = new QM_Collector_Admin();
	return $collectors;
}

if ( is_admin() ) {
	add_filter( 'qm/collectors', 'register_qm_collector_admin', 10, 2 );
}
