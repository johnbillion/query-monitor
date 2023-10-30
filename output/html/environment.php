<?php declare(strict_types = 1);
/**
 * Environment data output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Environment extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Environment Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 110 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Environment', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function output() {
		/** @var QM_Data_Environment $data */
		$data = $this->collector->get_data();

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>PHP</h3>';

		echo '<table>';
		echo '<tbody>';

		$append = '';
		$class = '';
		$php_warning = $data->php['old'];

		if ( $php_warning ) {
			$append .= sprintf(
				'&nbsp;<span class="qm-info">(<a href="%s" target="_blank" class="qm-external-link">%s</a>)</span>',
				'https://wordpress.org/support/update-php/',
				esc_html__( 'Help', 'query-monitor' )
			);
			$class = 'qm-warn';
		}

		echo '<tr class="' . esc_attr( $class ) . '">';
		echo '<th scope="row">' . esc_html__( 'Version', 'query-monitor' ) . '</th>';
		echo '<td>';

		if ( $php_warning ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo QueryMonitor::icon( 'warning' );
		}

		echo esc_html( $data->php['version'] ?: esc_html__( 'Unknown', 'query-monitor' ) );
		echo $append; // WPCS: XSS ok.
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th scope="row">SAPI</th>';
		echo '<td>' . esc_html( $data->php['sapi'] ?: esc_html__( 'Unknown', 'query-monitor' ) ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'User', 'query-monitor' ) . '</th>';
		if ( ! empty( $data->php['user'] ) ) {
			echo '<td>' . esc_html( $data->php['user'] ) . '</td>';
		} else {
			echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
		}
		echo '</tr>';

		foreach ( $data->php['variables'] as $key => $val ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html( $key ) . '</th>';
			echo '<td>';
			echo esc_html( $val );
			echo '</td>';
			echo '</tr>';
		}

		$out = array();

		foreach ( $data->php['error_levels'] as $level => $reported ) {
			if ( $reported ) {
				$out[] = esc_html( $level ) . '&nbsp;&#x2713;';
			} else {
				$out[] = '<span class="qm-false">' . esc_html( $level ) . '</span>';
			}
		}

		$error_levels = implode( '</li><li>', $out );

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Error Reporting', 'query-monitor' ) . '</th>';
		echo '<td class="qm-has-toggle qm-ltr">';

		echo esc_html( (string) $data->php['error_reporting'] );
		echo self::build_toggler(); // WPCS: XSS ok;

		echo '<div class="qm-toggled">';
		echo "<ul class='qm-supplemental'><li>{$error_levels}</li></ul>"; // WPCS: XSS ok.
		echo '</div>';

		echo '</td>';
		echo '</tr>';

		if ( ! empty( $data->php['extensions'] ) ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Extensions', 'query-monitor' ) . '</th>';
			echo '<td class="qm-has-inner qm-has-toggle qm-ltr">';

			printf( // WPCS: XSS ok.
				'<div class="qm-inner-toggle">%1$s %2$s</div>',
				esc_html( number_format_i18n( count( $data->php['extensions'] ) ) ),
				self::build_toggler()
			);

			echo '<div class="qm-toggled">';
			self::output_inner( $data->php['extensions'] );
			echo '</div>';

			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

		echo '</section>';

		if ( isset( $data->db ) ) {
			echo '<section>';
			echo '<h3>' . esc_html__( 'Database', 'query-monitor' ) . '</h3>';

			echo '<table>';
			echo '<tbody>';

			$info = array(
				'server-version' => __( 'Server Version', 'query-monitor' ),
				'extension' => __( 'Extension', 'query-monitor' ),
				'client-version' => __( 'Client Version', 'query-monitor' ),
				'user' => __( 'User', 'query-monitor' ),
				'host' => __( 'Host', 'query-monitor' ),
				'database' => __( 'Database', 'query-monitor' ),
			);

			foreach ( $info as $field => $label ) {

				echo '<tr>';
				echo '<th scope="row">' . esc_html( $label ) . '</th>';

				if ( ! isset( $data->db['info'][ $field ] ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<td><span class="qm-warn">' . QueryMonitor::icon( 'warning' ) . esc_html__( 'Unknown', 'query-monitor' ) . '</span></td>';
				} else {
					echo '<td>' . esc_html( $data->db['info'][ $field ] ) . '</td>';
				}

				echo '</tr>';

			}

			foreach ( $data->db['variables'] as $setting ) {

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$key = (string) $setting->Variable_name;
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$val = (string) $setting->Value;

				$append = '';

				if ( is_numeric( $val ) && ( $val >= ( 1024 * 1024 ) ) ) {
					$append .= sprintf(
						'&nbsp;<span class="qm-info">(~%s)</span>',
						esc_html( (string) size_format( $val ) )
					);
				}

				echo '<tr>';

				echo '<th scope="row">' . esc_html( $key ) . '</th>';
				echo '<td>';
				echo esc_html( $val );
				echo $append; // WPCS: XSS ok.
				echo '</td>';

				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';

			echo '</section>';
		}

		echo '<section>';
		echo '<h3>WordPress</h3>';

		echo '<table>';
		echo '<tbody>';

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Version', 'query-monitor' ) . '</th>';
		echo '<td>' . esc_html( $data->wp['version'] ) . '</td>';
		echo '</tr>';

		if ( isset( $data->wp['environment_type'] ) ) {
			echo '<tr>';
			echo '<th scope="row">';
			esc_html_e( 'Environment Type', 'query-monitor' );
			printf(
				'&nbsp;<span class="qm-info">(<a href="%s" target="_blank" class="qm-external-link">%s</a>)</span>',
				'https://make.wordpress.org/core/2020/07/24/new-wp_get_environment_type-function-in-wordpress-5-5/',
				esc_html__( 'Help', 'query-monitor' )
			);
			echo '</th>';
			echo '<td>' . esc_html( $data->wp['environment_type'] ) . '</td>';
			echo '</tr>';
		}

		if ( isset( $data->wp['development_mode'] ) ) {
			$mode = $data->wp['development_mode'];

			if ( '' === $mode ) {
				$mode = __( 'empty string', 'query-monitor' );
			}

			echo '<tr>';
			echo '<th scope="row">';
			esc_html_e( 'Development Mode', 'query-monitor' );
			printf(
				'&nbsp;<span class="qm-info">(<a href="%s" target="_blank" class="qm-external-link">%s</a>)</span>',
				'https://core.trac.wordpress.org/changeset/56042',
				esc_html__( 'Help', 'query-monitor' )
			);
			echo '</th>';
			echo '<td>' . esc_html( $mode ) . '</td>';
			echo '</tr>';
		}

		foreach ( $data->wp['constants'] as $key => $val ) {

			echo '<tr>';
			echo '<th scope="row">' . esc_html( $key ) . '</th>';
			echo '<td>' . esc_html( $val ) . '</td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html__( 'Server', 'query-monitor' ) . '</h3>';

		$server = array(
			'name' => __( 'Software', 'query-monitor' ),
			'version' => __( 'Version', 'query-monitor' ),
			'address' => __( 'IP Address', 'query-monitor' ),
			'host' => __( 'Host', 'query-monitor' ),
			/* translators: OS stands for Operating System */
			'OS' => __( 'OS', 'query-monitor' ),
			'arch' => __( 'Architecture', 'query-monitor' ),
		);

		echo '<table>';
		echo '<tbody>';

		foreach ( $server as $field => $label ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html( $label ) . '</th>';
			if ( ! empty( $data->server[ $field ] ) ) {
				echo '<td>' . esc_html( $data->server[ $field ] ) . '</td>';
			} else {
				echo '<td><em>' . esc_html__( 'Unknown', 'query-monitor' ) . '</em></td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</section>';

		$this->after_non_tabular_output();
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_environment( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'environment' );
	if ( $collector ) {
		$output['environment'] = new QM_Output_Html_Environment( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_environment', 120, 2 );
