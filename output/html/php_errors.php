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
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 10 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'PHP Errors', 'query-monitor' );
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_PHP_Errors $data */
		$data = $this->collector->get_data();

		if ( ! empty( $data->errors ) ) {
			foreach ( $data->errors as $type => $errors ) {
				$class[] = 'qm-' . $type;
			}
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_PHP_Errors $data */
		$data = $this->collector->get_data();
		$menu_label = array();

		$types = array(
			/* translators: %s: Number of deprecated PHP errors */
			'deprecated' => _nx_noop( '%s Deprecated', '%s Deprecated', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of strict PHP errors */
			'strict' => _nx_noop( '%s Strict', '%s Stricts', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of PHP notices */
			'notice' => _nx_noop( '%s Notice', '%s Notices', 'PHP error level', 'query-monitor' ),
			/* translators: %s: Number of PHP warnings */
			'warning' => _nx_noop( '%s Warning', '%s Warnings', 'PHP error level', 'query-monitor' ),
		);

		$key = 'quiet';
		$generic = false;

		foreach ( $types as $type => $label ) {

			$count = 0;
			$has_errors = false;

			if ( isset( $data->suppressed[ $type ] ) ) {
				$has_errors = true;
				$generic = true;
			}
			if ( isset( $data->silenced[ $type ] ) ) {
				$has_errors = true;
				$generic = true;
			}
			if ( isset( $data->errors[ $type ] ) ) {
				$has_errors = true;
				$key = $type;
				$count += (int) array_sum( array_column( $data->errors[ $type ], 'calls' ) );
			}

			if ( ! $has_errors ) {
				continue;
			}

			if ( $count ) {
				$label = sprintf(
					translate_nooped_plural(
						$label,
						$count,
						'query-monitor'
					),
					number_format_i18n( $count )
				);
				$menu_label[] = $label;
			}
		}

		if ( empty( $menu_label ) && ! $generic ) {
			return $menu;
		}

		/* translators: %s: List of PHP error types */
		$title = __( 'PHP Errors (%s)', 'query-monitor' );

		/* translators: used between list items, there is a space after the comma */
		$sep = __( ', ', 'query-monitor' );

		if ( count( $menu_label ) ) {
			$title = sprintf(
				$title,
				implode( $sep, array_reverse( $menu_label ) )
			);
		} else {
			$title = __( 'PHP Errors', 'query-monitor' );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'id' => "query-monitor-{$key}s", // @TODO
			'title' => $title,
		) );
		return $menu;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		if ( ! isset( $menu[ $this->collector->id() ] ) ) {
			return $menu;
		}

		/** @var QM_Data_PHP_Errors $data */
		$data = $this->collector->get_data();
		$count = 0;
		$types = array(
			'suppressed',
			'silenced',
			'errors',
		);

		foreach ( $types as $type ) {
			if ( ! empty( $data->{$type} ) ) {
				foreach ( $data->{$type} as $errors ) {
					$count += array_sum( array_column( $errors, 'calls' ) );
				}
			}
		}

		$menu[ $this->collector->id() ]['title'] = esc_html( sprintf(
			/* translators: %s: Number of errors */
			__( 'PHP Errors (%s)', 'query-monitor' ),
			number_format_i18n( $count )
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
