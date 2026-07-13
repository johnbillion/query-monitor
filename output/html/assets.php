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

	/**
	 * @param array<string, mixed[]> $menu
	 * @return array<string, mixed[]>
	 */
	public function admin_menu( array $menu ) {
		/** @var QM_Data_Assets $data */
		$data = $this->collector->get_data();

		$warning_count = 0;

		if ( ! empty( $data->assets ) ) {
			$warning_count = count( array_filter( $data->assets, static function ( array $asset ) : bool {
				return $asset['warning'];
			} ) );
		}

		$total = ! empty( $data->types ) ? array_sum( $data->types ) : 0;

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => $this->name(),
			'ok_count' => $total - $warning_count,
			'warning_count' => $warning_count ?: null,
		) );

		return $menu;
	}

}
