<?php declare(strict_types = 1);
/**
 * Database query output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @phpstan-import-type QueryRow from QM_Data_DB_Queries
 */
class QM_Output_Html_DB_Queries extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_DB_Queries Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	/**
	 * @var int
	 */
	public $query_row = 0;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 20 );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 20 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Database Queries', 'query-monitor' );
	}

	/**
	 * @param array<int, string> $title
	 * @return array<int, string>
	 */
	public function admin_title( array $title ) {
		/** @var QM_Data_DB_Queries $data */
		$data = $this->collector->get_data();
		$total_qs = $data->total_qs ?? 0;

		/* translators: %s: Number of database queries. Note the space between value and unit symbol. */
		$text = _n( '%s Q', '%s Q', $total_qs, 'query-monitor' );

		// Avoid a potentially blank translation for the plural form.
		// @see https://meta.trac.wordpress.org/ticket/5377
		if ( '' === $text ) {
			$text = '%s Q';
		}

		$query_count = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
			esc_html( $text ),
			number_format_i18n( $total_qs )
		) );

		if ( isset( $data->rows ) ) {
			$title[] = sprintf(
				/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
				esc_html_x( '%ss', 'Time in seconds', 'query-monitor' ),
				number_format_i18n( array_sum( array_column( $data->rows, 'ltime' ) ), 2 )
			);
			$title[] = $query_count;
		} elseif ( isset( $data->total_qs ) ) {
			$title[] = $query_count;
		}

		return $title;
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_DB_Queries $data */
		$data = $this->collector->get_data();
		$errors = $this->collector->get_errors();
		$expensive = $this->collector->get_expensive();

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => __( 'Database Queries', 'query-monitor' ),
			'ok_count' => ( $data->total_qs ?? 0 ) ?: null,
		) );

		if ( $errors ) {
			$menu['db_errors'] = $this->menu( array(
				'id' => 'db_errors',
				'panel' => 'db_errors',
				'title' => __( 'Database Errors', 'query-monitor' ),
				'warning_count' => count( $errors ),
			) );
		}

		if ( $expensive ) {
			$menu['db_expensive'] = $this->menu( array(
				'id' => 'db_expensive',
				'panel' => 'db_expensive',
				'title' => __( 'Slow Queries', 'query-monitor' ),
				'notice_count' => count( $expensive ),
			) );
		}

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_db_queries( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'db_queries' );
	if ( $collector ) {
		$output['db_queries'] = new QM_Output_Html_DB_Queries( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_db_queries', 20, 2 );
