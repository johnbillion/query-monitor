<?php declare(strict_types = 1);
/**
 * Raw database query output.
 *
 * @package query-monitor
 */

class QM_Output_Raw_DB_Queries extends QM_Output_Raw {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Queries Collector.
	 */
	protected $collector;

	/**
	 * @var int
	 */
	public $query_row = 0;

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Database Queries', 'query-monitor' );
	}

	/**
	 * @return array<string, mixed>
	 * @phpstan-return array{
	 *   total: int,
	 *   time: float,
	 *   queries: mixed[],
	 *   errors?: array{
	 *     total: int,
	 *     errors: array<int, array<string, mixed>>,
	 *   },
	 *   dupes?: array{
	 *     total: int,
	 *     queries: array<string, int[]>,
	 *   },
	 * }|array{}
	 */
	public function get_output() {
		/** @var QM_Data_DB_Queries $data */
		$data = $this->collector->get_data();

		if ( empty( $data->rows ) ) {
			return array();
		}

		$output = array(
			'total' => $data->total_qs,
			'time' => round( $data->total_time, 4 ),
			'queries' => array_map( array( $this, 'output_query_row' ), $data->rows ),
		);

		if ( ! empty( $data->errors ) ) {
			$output['errors'] = array(
				'total' => count( $data->errors ),
				'errors' => $data->errors,
			);
		}

		if ( ! empty( $data->dupes ) ) {
			$dupes = $data->dupes;

			// Filter out SQL queries that do not have dupes
			$dupes = array_filter( $dupes, array( $this->collector, 'filter_dupe_items' ) );

			// Ignore dupes from `WP_Query->set_found_posts()`
			unset( $dupes['SELECT FOUND_ROWS()'] );

			$output['dupes'] = array(
				'total' => count( $dupes ),
				'queries' => $dupes,
			);
		}

		return $output;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	protected function output_query_row( array $row ) {
		$output = array();

		$output['i'] = ++$this->query_row;
		$output['sql'] = $row['sql'];
		$output['time'] = round( $row['ltime'], 4 );

		if ( isset( $row['trace'] ) ) {
			$stack = array();
			$filtered_trace = $row['trace']->get_filtered_trace();

			foreach ( $filtered_trace as $item ) {
				$stack[] = $item['display'];
			}
		} else {
			$stack = $row['stack'];
		}

		$output['stack'] = $stack;
		$output['result'] = $row['result'];

		return $output;
	}
}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_raw_db_queries( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_queries' );
	if ( $collector ) {
		$output['db_queries'] = new QM_Output_Raw_DB_Queries( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/raw', 'register_qm_output_raw_db_queries', 20, 2 );
