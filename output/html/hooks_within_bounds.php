<?php
/**
 * Hooks within bounds output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Hooks_Within_Bounds extends QM_Output_Html {

	public $id = 'hooks_within_bounds';

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 80 );
	}

	public function panel_menu( array $menu ) {
		$hooks     = QM_Collectors::get( 'hooks' );
		$collector = QM_Collectors::get( 'hooks_within_bounds' );

		if ( $hooks && $collector ) {
			$menu['qm-hooks']['children'][] = $this->menu( array(
				'title' => '└ ' . esc_html__( 'Discovered Hooks', 'query-monitor' ),
			) );
		}

		return $menu;
	}

	public function output() {
		$data = $this->collector->get_data();

		if ( empty( $data['hooks'] ) ) {
			$this->before_non_tabular_output();

			$_notice = 'No discovered hooks.';

			if ( defined( 'QM_DISABLE_HOOK_DISCOVERY' ) && QM_DISABLE_HOOK_DISCOVERY ) {
				$_notice = 'Hook discovery disabled.';
			}

			$notice = __( $_notice, 'query-monitor' );
			echo $this->build_notice( $notice ); // WPCS: XSS ok.

			$this->after_non_tabular_output();
			return;
		}

		printf(
			'<div class="qm qm-discovered" id="%1$s" role="group" aria-labelledby="%1$s" tabindex="-1">',
			esc_attr( $this->collector->id() )
		);

		echo '<div><table>';

		printf(
			'<caption><h2 id="%1$s-caption">%2$s</h2></caption>',
			esc_attr( $this->collector->id() ),
			esc_html__( 'Discovered Hooks', 'query-monitor' )
		);

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Label', 'query-monitor' ) . '</th>';
		echo '<th scope="col"></th>';
		echo '<th scope="col">' . esc_html__( 'Hook',  'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Type',  'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['hooks'] as $id => $hooks ) {
			$trace_file__start
				= $trace_file__stop
				= '';

			$bound__start = $data['bounds'][ $id ]['start'];
			$bound__stop  = $data['bounds'][ $id ]['stop'];

			if ( is_a( $bound__start, 'QM_Backtrace' ) ) {
				$trace__start = $bound__start->get_trace();
				$trace_text__start = self::output_filename( '', $trace__start[0]['file'], $trace__start[0]['line'] );
			}

			if ( !empty( $data['bounds'][ $id ]['was_terminated'] ) ) {
				$trace_text__stop = '<br /><span class="qm-info qm-supplemental">Auto-terminated (<abbr title="' . QM_MAX_HOOKS_DISCOVERED . '">max hooks</abbr>)</span>';
			} else if ( is_a( $bound__stop, 'QM_Backtrace' ) ) {
				$trace__stop = $bound__stop->get_trace();
				$trace_text__stop = self::output_filename( '', $trace__stop[0]['file'], $trace__stop[0]['line'] );
			}

			foreach ( $hooks as $i => $hook ) {
				echo '<tr>';

				if ( 0 === $i ) {
					echo '<th scope="row" rowspan="' . esc_attr( count( $hooks ) ) . '" class="qm-nowrap">';
					echo '<span class="qm-sticky">';
					echo esc_html( $id );
					echo $trace_text__start; // WPCS: XSS ok.
					echo $trace_text__stop;  // WPCS: XSS ok.
					echo '</span>';
					echo '</th>';
				}

				echo '<td class="qm-num">' . esc_html( ++$i ) . '</td>';

				echo '<td>';
				echo '<code>' . esc_html( $hook['hook'] ) . '</code>';

				if ( 1 < $hook['fires'] ) {
					echo '<br /><span class="qm-info qm-supplemental">Fired ' . esc_html( $hook['fires'] ) . ' times</span>';
				}
				echo '</td>';
				echo '<td>' . ( $hook['is_action'] ? 'Action' : 'Filter' ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '</table></div>';

		echo '</div>';
	}

}

function register_qm_output_html_hooks_within_bounds( array $output, QM_Collectors $collectors ) {
	$collector = $collectors::get( 'hooks_within_bounds' );
	if ( $collector ) {
		$output['hooks_within_bounds'] = new QM_Output_Html_Hooks_Within_Bounds( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_hooks_within_bounds', 80, 2 );
