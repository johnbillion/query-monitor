<?php declare(strict_types = 1);
/**
 * Web Vitals for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Web_Vitals_Hooks extends QM_Output_Html {

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
		$this->before_tabular_output();
		echo '<thead>';
		echo '<script>
				// Insert the web vital into the qm-web-vitals div.
				function insertWebVital( webVital ) {
					console.log( { webVital } );
					var webVitalsDiv = document.getElementById( "qm-web-vitals-inner" );
					var divToAdd = document.createElement("tr");
					divToAdd.innerHTML = "<td>" + webVital.name + "</td><td>" + webVital.value + "</td><td>" + webVital.rating + "</td>";
					webVitalsDiv.appendChild( divToAdd );
				}

				webVitals.onCLS( insertWebVital );
				webVitals.onFID( insertWebVital );
				webVitals.onLCP( insertWebVital );

			</script>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Metric', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Score', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Rating', 'query-monitor' ) . '</th>';
		echo '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr id="qm-web-vitals-inner">';
		echo '</tr>';
		echo '</tbody>';

		$this->after_tabular_output();
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
		$output['web-vitals'] = new QM_Output_Web_Vitals_Hooks( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_web_vitals', 80, 2 );
