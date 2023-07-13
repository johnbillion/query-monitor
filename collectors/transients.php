<?php declare(strict_types = 1);
/**
 * Transient storage collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Transients>
 */
class QM_Collector_Transients extends QM_DataCollector {

	public $id = 'transients';

	public function get_storage(): QM_Data {
		return new QM_Data_Transients();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_action( 'setted_site_transient', array( $this, 'action_setted_site_transient' ), 10, 3 );
		add_action( 'setted_transient', array( $this, 'action_setted_blog_transient' ), 10, 3 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'setted_site_transient', array( $this, 'action_setted_site_transient' ), 10 );
		remove_action( 'setted_transient', array( $this, 'action_setted_blog_transient' ), 10 );
		parent::tear_down();
	}

	/**
	 * @param string $transient
	 * @param mixed $value
	 * @param int $expiration
	 * @return void
	 */
	public function action_setted_site_transient( $transient, $value, $expiration ) {
		$this->setted_transient( $transient, 'site', $value, $expiration );
	}

	/**
	 * @param string $transient
	 * @param mixed $value
	 * @param int $expiration
	 * @return void
	 */
	public function action_setted_blog_transient( $transient, $value, $expiration ) {
		$this->setted_transient( $transient, 'blog', $value, $expiration );
	}

	/**
	 * @param string $transient
	 * @param string $type
	 * @param mixed $value
	 * @param int $expiration
	 * @phpstan-param 'site'|'blog' $value
	 * @return void
	 */
	public function setted_transient( $transient, $type, $value, $expiration ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
			'ignore_func' => array(
				'set_transient' => true,
				'set_site_transient' => true,
			),
		) );

		$name = str_replace( array(
			'_site_transient_',
			'_transient_',
		), '', $transient );

		$size = strlen( (string) maybe_serialize( $value ) );

		$this->data->trans[] = array(
			'name' => $name,
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
			'type' => $type,
			'value' => $value,
			'expiration' => $expiration,
			'exp_diff' => ( $expiration ? human_time_diff( 0, $expiration ) : '' ),
			'size' => $size,
			'size_formatted' => (string) size_format( $size ),
		);
	}

	/**
	 * @return void
	 */
	public function process() {
		$this->data->has_type = is_multisite();
	}

}

# Load early in case a plugin is setting transients when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Transients() );
