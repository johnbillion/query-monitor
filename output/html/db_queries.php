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
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 20 );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 20 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
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

		if ( isset( $data->rows ) ) {
			$title[] = sprintf(
				/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
				esc_html_x( '%ss', 'Time in seconds', 'query-monitor' ),
				number_format_i18n( $data->total_time, 2 )
			);

			/* translators: %s: Number of database queries. Note the space between value and unit symbol. */
			$text = _n( '%s Q', '%s Q', $data->total_qs, 'query-monitor' );

			// Avoid a potentially blank translation for the plural form.
			// @see https://meta.trac.wordpress.org/ticket/5377
			if ( '' === $text ) {
				$text = '%s Q';
			}

			$title[] = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
				esc_html( $text ),
				number_format_i18n( $data->total_qs )
			) );
		} elseif ( isset( $data->total_qs ) ) {
			/* translators: %s: Number of database queries. Note the space between value and unit symbol. */
			$text = _n( '%s Q', '%s Q', $data->total_qs, 'query-monitor' );

			// Avoid a potentially blank translation for the plural form.
			// @see https://meta.trac.wordpress.org/ticket/5377
			if ( '' === $text ) {
				$text = '%s Q'; // @TODO
			}

			$title[] = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', sprintf(
				esc_html( $text ),
				number_format_i18n( $data->total_qs )
			) );
		}

		return $title;
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {

		if ( $this->collector->get_errors() ) {
			$class[] = 'qm-error';
		}
		if ( $this->collector->get_expensive() ) {
			$class[] = 'qm-expensive';
		}
		return $class;

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

		$id = $this->collector->id();
		$menu[ $id ] = $this->menu( array(
			'title' => esc_html__( 'Database Queries', 'query-monitor' ),
			// 'href' => esc_attr( sprintf( '#%s', $this->collector->id() ) ),
		) );

		if ( $errors ) {
			$id = $this->collector->id() . '-errors';
			$count = count( $errors );
			$menu[ $id ] = $this->menu( array(
				'id'    => 'query-monitor-errors',
				'title' => esc_html( sprintf(
					/* translators: %s: Number of database errors */
					__( 'Database Errors (%s)', 'query-monitor' ),
					number_format_i18n( $count )
				) ),
			) );
		}

		if ( $expensive ) {
			$id = $this->collector->id() . '-expensive';
			$count = count( $expensive );
			$menu[ $id ] = $this->menu( array(
				'id'    => 'query-monitor-expensive',
				'title' => esc_html( sprintf(
					/* translators: %s: Number of slow database queries */
					__( 'Slow Queries (%s)', 'query-monitor' ),
					number_format_i18n( $count )
				) ),
			) );
		}

		return $menu;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		foreach ( array( 'errors', 'expensive' ) as $sub ) {
			$id = $this->collector->id() . '-' . $sub;
			if ( isset( $menu[ $id ] ) ) {
				$menu['db_queries']['children'][] = $menu[ $id ];
				unset( $menu[ $id ] );
			}
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
