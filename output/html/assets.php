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
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function admin_class( array $class ) {
		/** @var QM_Data_Assets $data */
		$data = $this->collector->get_data();

		if ( ! empty( $data->broken ) || ! empty( $data->missing ) ) {
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

		if ( empty( $data->assets ) ) {
			return $menu;
		}

		$type_label = $this->get_type_labels();
		$label = sprintf(
			$type_label['count'],
			number_format_i18n( array_sum( $data->types ) )
		);

		$args = array(
			'title' => $label,
		);

		if ( ! empty( $data->broken ) || ! empty( $data->missing ) ) {
			$args['meta']['classname'] = 'qm-error';
		}

		$id = $this->collector->id();
		$menu[ $id ] = $this->menu( $args );

		return $menu;

	}

}
