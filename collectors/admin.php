<?php declare(strict_types = 1);
/**
 * Admin screen collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Admin>
 */
class QM_Collector_Admin extends QM_DataCollector {

	public $id = 'response';

	public function get_storage(): QM_Data {
		return new QM_Data_Admin();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		$actions = array(
			'current_screen',
			'admin_notices',
			'all_admin_notices',
			'network_admin_notices',
			'user_admin_notices',
		);

		if ( ! empty( $this->data->list_table ) ) {
			$actions[] = $this->data->list_table['column_action'];
		}

		return $actions;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		$filters = array();

		if ( ! empty( $this->data->list_table ) ) {
			$filters[] = $this->data->list_table['columns_filter'];
			$filters[] = $this->data->list_table['sortables_filter'];
		}

		return $filters;
	}

	/**
	 * @return void
	 */
	public function process() {
		/**
		 * @var string $pagenow
		 * @var ?WP_List_Table $wp_list_table
		 */
		global $pagenow, $wp_list_table;

		$this->data->pagenow = $pagenow;
		$this->data->typenow = $GLOBALS['typenow'] ?? '';
		$this->data->taxnow = $GLOBALS['taxnow'] ?? '';
		$this->data->hook_suffix = $GLOBALS['hook_suffix'] ?? '';
		$this->data->current_screen = get_current_screen();

		$screens = array(
			'edit' => true,
			'edit-comments' => true,
			'edit-tags' => true,
			'link-manager' => true,
			'plugins' => true,
			'plugins-network' => true,
			'sites-network' => true,
			'themes-network' => true,
			'upload' => true,
			'users' => true,
			'users-network' => true,
		);

		if ( empty( $this->data->current_screen ) || ! isset( $screens[ $this->data->current_screen->base ] ) ) {
			return;
		}

		# And now, WordPress' legendary inconsistency comes into play:

		$columns = $this->data->current_screen->id;
		$sortables = $this->data->current_screen->id;
		$column = $this->data->current_screen->base;

		if ( ! empty( $this->data->current_screen->taxonomy ) ) {
			$column = $this->data->current_screen->taxonomy;
		} elseif ( ! empty( $this->data->current_screen->post_type ) ) {
			$column = $this->data->current_screen->post_type . '_posts';
		}

		if ( ! empty( $this->data->current_screen->post_type ) && empty( $this->data->current_screen->taxonomy ) ) {
			$columns = $this->data->current_screen->post_type . '_posts';
		}

		if ( 'edit-comments' === $column ) {
			$column = 'comments';
		} elseif ( 'upload' === $column ) {
			$column = 'media';
		} elseif ( 'link-manager' === $column ) {
			$column = 'link';
		}

		$list_table_data = array(
			'columns_filter' => "manage_{$columns}_columns",
			'sortables_filter' => "manage_{$sortables}_sortable_columns",
			'column_action' => "manage_{$column}_custom_column",
		);

		if ( ! empty( $wp_list_table ) ) {
			$list_table_data['class_name'] = get_class( $wp_list_table );
		}

		$this->data->list_table = $list_table_data;
	}

}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_admin( array $collectors, QueryMonitor $qm ) {
	$collectors['response'] = new QM_Collector_Admin();
	return $collectors;
}

if ( is_admin() ) {
	add_filter( 'qm/collectors', 'register_qm_collector_admin', 10, 2 );
}
