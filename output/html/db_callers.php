<?php
/**
 * Database query calling function output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_DB_Callers extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Callers Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 30 );
	}

	public function name() {
		return __( 'Queries by Caller', 'query-monitor' );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['types'] ) ) {
			return;
		}

		$total_time = 0;

		if ( ! empty( $data['times'] ) ) {
			$this->before_tabular_output();

			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';

			foreach ( $data['types'] as $type_name => $type_count ) {
				echo '<th scope="col" class="qm-num qm-ltr qm-sortable-column" role="columnheader" aria-sort="none">';
				echo $this->build_sorter( $type_name ); // WPCS: XSS ok;
				echo '</th>';
			}

			echo '<th scope="col" class="qm-num qm-sorted-desc qm-sortable-column" role="columnheader" aria-sort="descending">';
			echo $this->build_sorter( __( 'Time', 'query-monitor' ) ); // WPCS: XSS ok;
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

			foreach ( $data['times'] as $row ) {
				$total_time += $row['ltime'];
				$stime       = number_format_i18n( $row['ltime'], 4 );

				echo '<tr>';
				echo '<td class="qm-ltr"><button class="qm-filter-trigger" data-qm-target="db_queries-wpdb" data-qm-filter="caller" data-qm-value="' . esc_attr( $row['caller'] ) . '"><code>' . esc_html( $row['caller'] ) . '</code></button></td>';

				foreach ( $data['types'] as $type_name => $type_count ) {
					if ( isset( $row['types'][ $type_name ] ) ) {
						echo "<td class='qm-num'>" . esc_html( number_format_i18n( $row['types'][ $type_name ] ) ) . '</td>';
					} else {
						echo "<td class='qm-num'></td>";
					}
				}

				echo '<td class="qm-num" data-qm-sort-weight="' . esc_attr( $row['ltime'] ) . '">' . esc_html( $stime ) . '</td>';
				echo '</tr>';

			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $total_time, 4 );

			echo '<tr>';
			echo '<td></td>';

			foreach ( $data['types'] as $type_name => $type_count ) {
				echo '<td class="qm-num">' . esc_html( number_format_i18n( $type_count ) ) . '</td>';
			}

			echo '<td class="qm-num">' . esc_html( $total_stime ) . '</td>';
			echo '</tr>';

			echo '</tfoot>';

			$this->after_tabular_output();
		} else {
			$this->before_non_tabular_output();

			echo '<div class="qm-none">';
			echo '<p>' . esc_html__( 'None', 'query-monitor' ) . '</p>';
			echo '</div>';

			$this->after_non_tabular_output();
		}
	}

	public function panel_menu( array $menu ) {
		$dbq = QM_Collectors::get( 'db_queries' );

		if ( $dbq ) {
			$dbq_data = $dbq->get_data();
			if ( isset( $dbq_data['times'] ) ) {
				$menu['qm-db_queries-$wpdb']['children'][] = $this->menu( array(
					'title' => esc_html__( 'Queries by Caller', 'query-monitor' ),
				) );
			}
		}
		return $menu;

	}

}

function register_qm_output_html_db_callers( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_callers' );
	if ( $collector ) {
		$output['db_callers'] = new QM_Output_Html_DB_Callers( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_callers', 30, 2 );
