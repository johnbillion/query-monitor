<?php
/**
 * Duplicate database query output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_DB_Dupes extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 45 );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( empty( $data['dupes'] ) ) {
			return;
		}

		$this->before_tabular_output();

		echo '<thead>';

		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Query', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-num">' . esc_html__( 'Count', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Callers', 'query-monitor' ) . '</th>';
		if ( ! empty( $data['dupe_components'] ) ) {
			echo '<th scope="col">' . esc_html__( 'Components', 'query-monitor' ) . '</th>';
		}
		echo '<th scope="col">' . esc_html__( 'Potential Troublemakers', 'query-monitor' ) . '</th>';
		echo '</tr>';

		echo '</thead>';

		echo '<tbody>';

		/* translators: %s: Number of calls to a PHP function */
		$call_text = _n_noop( '%s call', '%s calls', 'query-monitor' );

		foreach ( $data['dupes'] as $sql => $queries ) {

			// This should probably happen in the collector's processor
			$type    = QM_Util::get_query_type( $sql );
			$sql_out = self::format_sql( $sql );

			if ( 'SELECT' !== $type ) {
				$sql_out = "<span class='qm-nonselectsql'>{$sql_out}</span>";
			}

			echo '<tr>';
			echo '<td class="qm-row-sql qm-ltr qm-wrap">';
			echo $sql_out; // WPCS: XSS ok;
			echo '</td>';
			echo '<td class="qm-num">';
			echo esc_html( number_format_i18n( count( $queries ), 0 ) );
			echo '</td>';
			echo '<td class="qm-row-caller qm-nowrap qm-ltr">';
			foreach ( $data['dupe_callers'][ $sql ] as $caller => $calls ) {
				printf(
					'<a href="#" class="qm-filter-trigger" data-qm-target="db_queries-wpdb" data-qm-filter="caller" data-qm-value="%s"><code>%s</code></a><br><span class="qm-info qm-supplemental">%s</span><br>',
					esc_attr( $caller ),
					esc_html( $caller ),
					esc_html( sprintf(
						translate_nooped_plural( $call_text, $calls, 'query-monitor' ),
						number_format_i18n( $calls )
					) )
				);
			}
			echo '</td>';
			if ( isset( $data['dupe_components'][ $sql ] ) ) {
				echo '<td class="qm-row-component qm-nowrap">';
				foreach ( $data['dupe_components'][ $sql ] as $component => $calls ) {
					printf(
						'%s<br><span class="qm-info qm-supplemental">%s</span><br>',
						esc_html( $component ),
						esc_html( sprintf(
							translate_nooped_plural( $call_text, $calls, 'query-monitor' ),
							number_format_i18n( $calls )
						) )
					);
				}
				echo '</td>';
			}
			echo '<td class="qm-row-caller qm-nowrap qm-ltr">';
			foreach ( $data['dupe_sources'][ $sql ] as $source => $calls ) {
				printf(
					'<code>%s</code><br><span class="qm-info qm-supplemental">%s</span><br>',
					esc_html( $source ),
					esc_html( sprintf(
						translate_nooped_plural( $call_text, $calls, 'query-monitor' ),
						number_format_i18n( $calls )
					) )
				);
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';

		$this->after_tabular_output();
	}

	public function admin_menu( array $menu ) {

		if ( $dbq = QM_Collectors::get( 'db_dupes' ) ) {
			$dbq_data = $dbq->get_data();
			if ( isset( $dbq_data['dupes'] ) && count( $dbq_data['dupes'] ) ) {
				$menu[] = $this->menu( array(
					'title' => esc_html( sprintf(
						/* translators: %s: Number of duplicate database queries */
						__( 'Duplicate Queries (%s)', 'query-monitor' ),
						count( $dbq_data['dupes'] )
					) ),
				) );
			}
		}
		return $menu;

	}

}

function register_qm_output_html_db_dupes( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'db_dupes' ) ) {
		$output['db_dupes'] = new QM_Output_Html_DB_Dupes( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_dupes', 45, 2 );
