<?php declare(strict_types = 1);
/**
 * Database query calling component output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_DB_Components extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Components Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 40 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Queries by Component', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_DB_Components $data */
		$data = $this->collector->get_data();

		if ( empty( $data->types ) || empty( $data->times ) ) {
			return;
		}

		$total_time = 0;

		$this->before_tabular_output();

		echo '<thead>';

		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Component', 'query-monitor' ) . '</th>';

		foreach ( $data->types as $type_name => $type_count ) {
			echo '<th scope="col" class="qm-num qm-sortable-column" role="columnheader">';
			echo $this->build_sorter( $type_name ); // WPCS: XSS ok;
			echo '</th>';
		}

		echo '<th scope="col" class="qm-num qm-sorted-desc qm-sortable-column" role="columnheader" aria-sort="descending">';
		echo $this->build_sorter( __( 'Time', 'query-monitor' ) ); // WPCS: XSS ok;
		echo '</th>';
		echo '</tr>';

		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->times as $row ) {
			$total_time += $row['ltime'];

			echo '<tr>';
			echo '<td class="qm-row-component">';
			echo self::build_filter_trigger( 'db_queries', 'component', $row['component'], esc_html( $row['component'] ) ); // WPCS: XSS ok;

			foreach ( $data->types as $type_name => $type_count ) {
				if ( isset( $row['types'][ $type_name ] ) ) {
					echo '<td class="qm-num">' . esc_html( number_format_i18n( $row['types'][ $type_name ] ) ) . '</td>';
				} else {
					echo '<td class="qm-num">&nbsp;</td>';
				}
			}

			echo '<td class="qm-num" data-qm-sort-weight="' . esc_attr( (string) $row['ltime'] ) . '">' . esc_html( number_format_i18n( $row['ltime'], 4 ) ) . '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '<tfoot>';

		$total_stime = number_format_i18n( $total_time, 4 );

		echo '<tr>';
		echo '<td>&nbsp;</td>';

		foreach ( $data->types as $type_name => $type_count ) {
			echo '<td class="qm-num">' . esc_html( number_format_i18n( $type_count ) ) . '</td>';
		}

		echo '<td class="qm-num">' . esc_html( $total_stime ) . '</td>';
		echo '</tr>';
		echo '</tfoot>';

		$this->after_tabular_output();
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		/** @var QM_Data_DB_Components $data */
		$data = $this->collector->get_data();

		if ( empty( $data->types ) || empty( $data->times ) ) {
			return $menu;
		}

		/** @var QM_Collector_DB_Queries|null $dbq */
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			/** @var QM_Data_DB_Queries $dbq_data */
			$dbq_data = $dbq->get_data();
			if ( ! empty( $dbq_data->component_times ) ) {
				$menu['qm-db_queries']['children'][] = $this->menu( array(
					'title' => esc_html__( 'Queries by Component', 'query-monitor' ),
				) );
			}
		}
		return $menu;

	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_components( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_components' );
	if ( $collector ) {
		$output['db_components'] = new QM_Output_Html_DB_Components( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_components', 40, 2 );
