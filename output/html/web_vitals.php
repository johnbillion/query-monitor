<?php declare(strict_types = 1);
/**
 * Web Vitals for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Web_Vitals extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Hooks Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 80 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Web Vitals', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		if ( ! wp_script_is( 'qm-web-vitals', 'enqueued' ) && ! wp_script_is( 'qm-web-vitals', 'done' ) ) {
			$this->before_non_tabular_output();
			/* translators: %s: Script handle. */
			$notice = sprintf( __( 'Script %s is not available.', 'query-monitor' ), 'qm-web-vitals' );
			echo $this->build_notice( $notice );
			$this->after_non_tabular_output();
			return;
		}

		$this->before_tabular_output();
		echo '<thead>';

		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Metric', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Score', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Rating', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Details', 'query-monitor' ) . '</th>';
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody id="qm-web-vitals-inner">';
			echo '<tr class="waiting">';
				echo '<td colspan="4">';
					echo '<div class="qm-none"><p>Waiting for data...</p></div>';
				echo '</td>';
			echo '</tr>';
		echo '</tbody>';

		$this->after_tabular_output();
		echo '<script>
		// Insert the web vital into the qm-web-vitals div.
		function insertWebVital( webVital ) {
			var webVitalsDiv = document.getElementById( "qm-web-vitals-inner" );
			var divToAdd = document.createElement("tr");
			divToAdd.innerHTML = "<td>" + webVital.name + "</td>" +
				"<td>" + webVital.value + "</td>" +
				"<td>" + webVital.rating + "</td>" +
				"<td><pre class=\"qm-pre-wrap\"><code>" + JSON.stringify( webVital ) + "</code></pre></td>";
			webVitalsDiv.querySelector( ".waiting" )?.remove();
			webVitalsDiv.appendChild( divToAdd );
		}

		// Collect and display the available metrics.
		webVitals.onFCP( insertWebVital ); // Available at load
		webVitals.onTTFB( insertWebVital ); // Available at load
		webVitals.onFID( insertWebVital ); // Available after interaction
		webVitals.onLCP( insertWebVital ); // Available after interaction
		webVitals.onCLS( insertWebVital ); // Only available on unload.
		webVitals.onINP( insertWebVital ); // Only available on unload.
	</script>';
	}


}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_web_vitals( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'web-vitals' );
	if ( $collector ) {
		$output['web-vitals'] = new QM_Output_Web_Vitals( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_web_vitals', 80, 2 );
