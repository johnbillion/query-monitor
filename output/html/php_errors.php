<?php declare(strict_types = 1);
/**
 * PHP error output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_PHP_Errors extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_PHP_Errors Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 10 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'PHP Errors', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_PHP_Errors $data */
		$data = $this->collector->get_data();

		if ( empty( $data->errors ) ) {
			return $menu;
		}

		$notice_count = 0;
		$warning_count = 0;

		foreach ( $data->errors as $error ) {
			if ( $error->suppressed ) {
				continue;
			}

			if ( 'warning' === $error->level ) {
				$warning_count += $error->count;
			} else {
				$notice_count += $error->count;
			}
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => __( 'PHP Errors', 'query-monitor' ),
			'notice_count' => $notice_count ?: null,
			'warning_count' => $warning_count ?: null,
		) );

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_php_errors( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'php_errors' );
	if ( $collector ) {
		$output['php_errors'] = new QM_Output_Html_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_php_errors', 110, 2 );
