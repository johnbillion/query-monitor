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

}
