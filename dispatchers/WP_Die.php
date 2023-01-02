<?php declare(strict_types = 1);
/**
 * Dispatcher for output that gets added to `wp_die()` calls.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Dispatcher_WP_Die extends QM_Dispatcher {

	/**
	 * @var string
	 */
	public $id = 'wp_die';

	/**
	 * @var QM_Backtrace|null
	 */
	public $trace = null;

	public function __construct( QM_Plugin $qm ) {
		add_action( 'shutdown', array( $this, 'dispatch' ), 0 );

		add_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );

		parent::__construct( $qm );
	}

	/**
	 * @param callable $handler
	 * @return callable
	 */
	public function filter_wp_die_handler( $handler ) {
		$this->trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		return $handler;
	}

	/**
	 * @return void
	 */
	public function dispatch() {
		if ( ! $this->should_dispatch() ) {
			return;
		}

		$switched_locale = self::switch_to_locale( get_user_locale() );
		$stack = array();
		$filtered_trace = $this->trace->get_filtered_trace();
		$component = $this->trace->get_component();

		foreach ( $filtered_trace as $i => $item ) {
			$stack[] = QM_Output_Html::output_filename( $item['display'], $item['file'], $item['line'] );
		}

		?>
		<style>
			#query-monitor {
				position: absolute;
				margin: 4em 0 1em -2em;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgb( 0 0 0 / 4% );
				background: #fff;
				padding-top: 1em;
				max-width: 700px;
				z-index: -1;
			}

			#query-monitor h2 {
				font-size: 12px;
				font-weight: normal;
				padding: 7px;
				background: #f3f3f3;
				margin: 0;
				border-top: 1px solid #ddd;
			}

			#query-monitor ol,
			#query-monitor p {
				font-size: 12px;
				padding: 0;
				margin: 1em 2.5em;
			}

			#query-monitor ol {
				padding: 0 0 1em 0;
			}

			#query-monitor li {
				margin: 0 0 0.7em;
				list-style: none;
			}

			#query-monitor .qm-info {
				color: #666;
			}

			#query-monitor a.qm-edit-link svg {
				display: none !important;
			}

		</style>
		<?php

		echo '<div id="query-monitor">';

		echo '<p>';

		if ( 'unknown' !== $component->type ) {
			$name = ( 'plugin' === $component->type ) ? $component->context : $component->name;
			printf(
				/* translators: %s: Plugin or theme name */
				esc_html__( 'This message was triggered by %s.', 'query-monitor' ),
				'<b>' . esc_html( $name ) . '</b>'
			);
		}

		echo '</p>';

		echo '<p>' . esc_html__( 'Call stack:', 'query-monitor' ) . '</p>';
		echo '<ol>';
		echo '<li>' . implode( '</li><li>', $stack ) . '</li>'; // WPCS: XSS ok.
		echo '</ol>';

		echo '<h2>' . esc_html__( 'Query Monitor', 'query-monitor' ) . '</h2>';

		echo '</div>';

		if ( $switched_locale ) {
			self::restore_previous_locale();
		}
	}

	/**
	 * @return bool
	 */
	public function is_active() {
		if ( ! $this->trace ) {
			return false;
		}

		if ( ! self::user_can_view() ) {
			return false;
		}

		return true;
	}

}

/**
 * @param array<string, QM_Dispatcher> $dispatchers
 * @param QM_Plugin $qm
 * @return array<string, QM_Dispatcher>
 */
function register_qm_dispatcher_wp_die( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['wp_die'] = new QM_Dispatcher_WP_Die( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_wp_die', 10, 2 );
