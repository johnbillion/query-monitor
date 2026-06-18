<?php declare(strict_types = 1);
/**
 * Scripts and styles output for HTML pages.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class QM_Output_Html_Assets extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Assets Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 70 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
	}

	/**
	 * @return array<string, string>
	 */
	abstract public function get_type_labels();

	/**
	 * Returns the number of assets with a problem, namely those that are missing or
	 * that have missing dependencies. This matches the rows flagged as errors in the panel.
	 *
	 * @return int
	 */
	protected function get_warning_count() {
		/** @var QM_Data_Assets $data */
		$data = $this->collector->get_data();

		if ( empty( $data->assets ) ) {
			return 0;
		}

		return count( array_filter( $data->assets, static function( array $asset ) {
			return ! empty( $asset['warning'] );
		} ) );
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		if ( $this->get_warning_count() > 0 ) {
			$class[] = 'qm-error';
		}

		return $class;

	}

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Assets $data */
		$data = $this->collector->get_data();

		$type_label = $this->get_type_labels();
		$warning_count = $this->get_warning_count();

		$args = array(
			'title' => $type_label['label'],
			'count' => array_sum( $data->types ),
		);

		if ( $warning_count > 0 ) {
			$args['warning_count'] = $warning_count;
			$args['meta']['classname'] = 'qm-error';
		}

		$id = $this->collector->id();
		$menu[ $id ] = $this->menu( $args );

		return $menu;

	}

}
