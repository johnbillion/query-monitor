<?php declare(strict_types = 1);
/**
 * Template and theme output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Output_Html_Theme extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Theme Collector.
	 */
	protected $collector;

	/**
	 * @var bool
	 */
	public static $client_side_rendered = true;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 60 );
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Theme', 'query-monitor' );
	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Theme $data */
		$data = $this->collector->get_data();

		if ( ! empty( $data->block_template ) ) {
			if ( $data->block_template->wp_id ) {
				$name = $data->block_template->id;
			} else {
				$name = sprintf(
					'%s/%s.html',
					$data->theme_folders[ $data->block_template->type ],
					$data->block_template->slug
				);
			}
		} elseif ( isset( $data->template_file ) ) {
			$name = ( $data->is_child_theme ) ? $data->theme_template_file : $data->template_file;
		} else {
			$name = __( 'Unknown', 'query-monitor' );
		}

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => sprintf(
				/* translators: %s: Template file name */
				__( 'Template: %s', 'query-monitor' ),
				$name
			),
		) );

		return $menu;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function panel_menu( array $menu ) {
		if ( isset( $menu[ $this->collector->id() ] ) ) {
			$menu[ $this->collector->id() ]['title'] = __( 'Template', 'query-monitor' );
		}

		return $menu;
	}

}

/**
 * @param array<string, QM_Output> $output
 * @param QM_Collectors $collectors
 * @return array<string, QM_Output>
 */
function register_qm_output_html_theme( array $output, QM_Collectors $collectors ) {
	if ( is_admin() ) {
		return $output;
	}
	$collector = QM_Collectors::get( 'response' );
	if ( $collector ) {
		$output['response'] = new QM_Output_Html_Theme( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_theme', 70, 2 );
