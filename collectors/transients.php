<?php
/**
 * Transient storage collector.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

class QM_Collector_Transients extends QM_Collector {

	public $id = 'transients';

	public function __construct() {
		parent::__construct();
		add_action( 'setted_site_transient', array( $this, 'action_setted_site_transient' ), 10, 3 );
		add_action( 'setted_transient',      array( $this, 'action_setted_blog_transient' ), 10, 3 );
	}

	public function tear_down() {
		remove_action( 'setted_site_transient', array( $this, 'action_setted_site_transient' ), 10 );
		remove_action( 'setted_transient',      array( $this, 'action_setted_blog_transient' ), 10 );
		parent::tear_down();
	}

	public function action_setted_site_transient( $transient, $value, $expiration ) {
		$this->setted_transient( $transient, 'site', $value, $expiration );
	}

	public function action_setted_blog_transient( $transient, $value, $expiration ) {
		$this->setted_transient( $transient, 'blog', $value, $expiration );
	}

	public function setted_transient( $transient, $type, $value, $expiration ) {
		$trace = new QM_Backtrace( array(
			'ignore_frames' => 1, # Ignore the action_setted_(site|blog)_transient method
		) );

		$name = str_replace( array(
			'_site_transient_',
			'_transient_',
		), '', $transient );

		$size = strlen( maybe_serialize( $value ) );

		$this->data['trans'][] = array(
			'name'       => $name,
			'trace'      => $trace,
			'type'       => $type,
			'value'      => $value,
			'expiration' => $expiration,
			'exp_diff'   => ( $expiration ? human_time_diff( 0, $expiration ) : '' ),
			'size'       => $size,
			'size_formatted' => size_format( $size ),
		);
	}

	public function process() {
		$this->data['has_type'] = is_multisite();

		if ( empty( $this->data['trans'] ) ) {
			return;
		}

		foreach ( $this->data['trans'] as $i => $transient ) {
			$filtered_trace = $transient['trace']->get_display_trace();

			array_shift( $filtered_trace ); // remove do_action('setted_(site_)?transient')
			array_shift( $filtered_trace ); // remove set_(site_)?transient()

			$component = $transient['trace']->get_component();

			$this->data['trans'][ $i ]['filtered_trace'] = $filtered_trace;
			$this->data['trans'][ $i ]['component']      = $component;

			unset( $this->data['trans'][ $i ]['trace'] );
		}
	}

}

# Load early in case a plugin is setting transients when it initialises instead of after the `plugins_loaded` hook
QM_Collectors::add( new QM_Collector_Transients() );
