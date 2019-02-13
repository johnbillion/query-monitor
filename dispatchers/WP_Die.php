<?php
/**
 * Dispatcher for output that gets added to `wp_die()` calls.
 *
 * @package query-monitor
 */

class QM_Dispatcher_WP_Die extends QM_Dispatcher {

	public $id    = 'wp_die';
	public $trace = null;

	protected $outputters = array();

	public function __construct( QM_Plugin $qm ) {
		add_action( 'shutdown', array( $this, 'dispatch' ), 0 );

		add_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );

		parent::__construct( $qm );
	}

	public function filter_wp_die_handler( $handler ) {
		$this->trace = new QM_Backtrace( array(
			'ignore_frames' => 1,
		) );

		return $handler;
	}

	public function dispatch() {
		if ( ! $this->should_dispatch() ) {
			return;
		}

		require_once $this->qm->plugin_path( 'output/Html.php' );

		$switched_locale = function_exists( 'switch_to_locale' ) && switch_to_locale( get_user_locale() );
		$stack           = array();
		$filtered_trace  = $this->trace->get_display_trace();

		// Ignore the `apply_filters('wp_die_handler')` stack frame:
		array_pop( $filtered_trace );

		foreach ( $filtered_trace as $i => $item ) {
			$stack[] = QM_Output_Html::output_filename( $item['display'], $item['file'], $item['line'] );
		}

		if ( isset( $filtered_trace[ $i - 1 ] ) ) {
			$culprit = $filtered_trace[ $i - 1 ];
		} else {
			$culprit = $filtered_trace[ $i ];
		}

		$component = QM_Backtrace::get_frame_component( $culprit );

		?>
		<style>
			#query-monitor {
				position: absolute;
				margin: 4em 0 1em;
				border: 1px solid #aaa;
				background: #fff;
				width: 700px;
			}

			#query-monitor h2 {
				font-size: 12px;
				font-weight: normal;
				padding: 5px;
				background: #f3f3f3;
				margin: 0;
				border-bottom: 1px solid #aaa;
			}

			#query-monitor ol,
			#query-monitor p {
				font-size: 12px;
				padding: 0;
				margin: 1em;
			}

			#query-monitor ol {
				padding: 0 0 1em 2em;
			}

			#query-monitor li {
				margin: 0 0 0.5em;
			}

			#query-monitor .qm-info {
				color: #777;
			}
		</style>
		<?php

		echo '<div id="query-monitor">';

		echo '<h2>' . esc_html__( 'Query Monitor', 'query-monitor' ) . '</h2>';

		if ( $component ) {
			echo '<p>';
			$name = ( 'plugin' === $component->type ) ? $component->context : $component->name;
			printf(
				/* translators: %s: Plugin or theme name */
				esc_html__( 'The message above was triggered by %s.', 'query-monitor' ),
				'<b>' . esc_html( $name ) . '</b>'
			);
			echo '</p>';
		}

		echo '<p>' . esc_html__( 'Call stack:', 'query-monitor' ) . '</p>';
		echo '<ol>';
		echo '<li>' . implode( '</li><li>', $stack ) . '</li>'; // WPCS: XSS ok.
		echo '</ol>';

		echo '</div>';

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	public function is_active() {
		if ( ! $this->trace ) {
			return false;
		}

		if ( ! $this->user_can_view() ) {
			return false;
		}

		return true;
	}

}

function register_qm_dispatcher_wp_die( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['wp_die'] = new QM_Dispatcher_WP_Die( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_wp_die', 10, 2 );
