<?php declare(strict_types = 1);
/**
 * Discovered hooks and actions output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Hooks_Discovered extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Hooks Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'action_output_menus' ), 80 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Discovered Hooks', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function action_output_menus( array $menu ) {
		$hooks = QM_Collectors::get( 'hooks' );

		if ( ! $hooks ) {
			return $menu;
		}

		$menu['qm-hooks']['children'][] = $this->menu( array(
			'title' => $this->name(),
		) );

		return $menu;
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Hooks_Discovered */
		$data = $this->collector->get_data();

		$types = array(
			'action' => __( 'Action', 'query-monitor' ),
			'filter' => __( 'Filter', 'query-monitor' ),
		);

		if ( empty( $data->hooks ) ) {
			$notice = __( 'No discovered hooks.', 'query-monitor' );

			if ( defined( 'QM_DISABLED_HOOK_DISCOVERY' ) && constant( 'QM_DISABLED_HOOK_DISCOVERY' ) ) {
				$notice = __( 'Hook discovery disabled.', 'query-monitor' );
			}

			$this->before_non_tabular_output();
			echo $this->build_notice( $notice ); // WPCS: XSS ok.
			$this->after_non_tabular_output();

			return;
		}

		printf( '<div class="qm qm-discovered" id="%1$s" role="group" aria-labelledby="%1$s" tabindex="-1">', esc_attr( $this->collector->id() ) );

		echo '<div><table>';

		printf( '<caption><h2 id="%1$s-caption">%2$s</h2></caption>', esc_attr( $this->collector->id() ), esc_html( $this->name() ) );

		echo '<thead>';
		echo '<tr>';
		printf( '<th scope="col">%s</th>', esc_html__( 'Label', 'query-monitor' ) );
		echo '<th class="qm-num" scope="col">#</th>';
		printf( '<th scope="col">%s</th>', esc_html__( 'Hook', 'query-monitor' ) );
		printf( '<th scope="col">%s</th>', esc_html__( 'Type', 'query-monitor' ) );
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data->hooks as $id => $hooks ) {
			$trace_text__start = '';
			$trace_text__stop  = '';

			$bounds = $data->bounds[ $id ];

			if ( is_a( $bounds['start'], QM_Backtrace::class ) ) {
				$trace__start      = $bounds['start']->get_trace();
				$trace_text__start = self::output_filename( '', $trace__start[0]['file'], $trace__start[0]['line'] );
			}

			$trace_text__stop = sprintf( '<br /><span class="qm-info qm-supplemental"><abbr title="%s">Limit</abbr> reached</span>', constant( 'QM_MAX_DISCOVERED_HOOKS' ) );

			if ( empty( $bounds['terminated'] ) && is_a( $bounds['stop'], QM_Backtrace::class ) ) {
				$trace__stop      = $bounds['stop']->get_trace();
				$trace_text__stop = self::output_filename( '', $trace__stop[0]['file'], $trace__stop[0]['line'] );
			}

			$first = true;

			foreach ( $hooks as $i => $hook ) {
				$type = $types['filter'];

				if ( $hook['is_action'] ) {
					$type = $types['action'];
				}

				printf( '<tr data-qm-type="%s">', strtolower( $type ) );

				if ( $first ) {
					$first = false;

					printf(
						'<th scope="row" rowspan="%d" class="qm-nowrap"><span class="qm-sticky">%s%s%s</span>',
						absint( count( $hooks ) ),
						esc_html( $id ),
						$trace_text__start,
						$trace_text__stop
					);
				}

				printf( '<td class="qm-num">%d</td>', absint( ++$i ) );

				echo '<td>';
				printf( '<code>%s</code>', esc_html( $hook['name'] ) );
				if ( 1 < $hook['fires'] ) {
					printf( '<br /><span class="qm-info qm-supplemental">Fired %d times</span>', absint( $hook['fires'] ) );
				}
				echo '</td>';

				printf( '<td>%s</td>', $type );

				echo '</tr>';
			}
		}

		echo '</tbody>';

		echo '</table></div>';

		echo '</div>';
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_discovered_hooks( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'hooks_discovered' );
	if ( $collector ) {
		$output['hooks_discovered'] = new QM_Output_Html_Hooks_Discovered( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_discovered_hooks', 80, 2 );
