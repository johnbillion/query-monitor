<?php declare(strict_types = 1);
/**
 * Emails collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Emails>
 */
class QM_Collector_Emails extends QM_DataCollector {

	public $id = 'emails';

	public function get_storage(): QM_Data {
		return new QM_Data_Emails();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'pre_wp_mail', array( $this, 'filter_pre_wp_mail' ), 9999, 2 );
		add_action( 'wp_mail_succeeded', array( $this, 'action_wp_mail_succeeded' ) );
		add_action( 'wp_mail_failed', array( $this, 'action_wp_mail_failed' ) );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_wp_mail', array( $this, 'filter_pre_wp_mail' ), 9999 );
		remove_action( 'wp_mail_succeeded', array( $this, 'action_wp_mail_succeeded' ) );
		remove_action( 'wp_mail_failed', array( $this, 'action_wp_mail_failed' ) );

		parent::tear_down();
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array(
			'phpmailer_init',
			'wp_mail_succeeded',
			'wp_mail_failed',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'pre_wp_mail',
			'wp_mail',
			'wp_mail_from',
			'wp_mail_from_name',
			'wp_mail_content_type',
			'wp_mail_charset',
		);
	}

	protected function hash( $value ) {
		$value = json_encode( $value );
		return wp_hash( $value );
	}

	public function filter_pre_wp_mail( $preempt, $atts ) {
		if ( is_null( $preempt ) ) {
			return null;
		}

		if ( is_null( $this->data->preempted ) ) {
			$this->data->preempted = array();
		}

		$hash  = $this->hash( $atts );
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		$this->data->preempted[]     = $hash;
		$this->data->emails[ $hash ] = array(
			'atts'           => $atts,
			'error'          => new WP_Error( 'pre_wp_mail', 'Preempted sending email.' ),
			'filtered_trace' => $trace->get_filtered_trace(),
		);

		return $preempt;
	}

	public function action_wp_mail_succeeded( $atts ) {
		$hash  = $this->hash( $atts );
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		$this->data->emails[ $hash ] = array(
			'atts'           => $atts,
			'error'          => null,
			'filtered_trace' => $trace->get_filtered_trace(),
		);
	}

	public function action_wp_mail_failed( $error ) {
		if ( is_null( $this->data->failed ) ) {
			$this->data->failed = array();
		}

		$atts  = $error->get_error_data( 'wp_mail_failed' );
		$hash  = $this->hash( $atts );
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		$this->data->failed[]        = $hash;
		$this->data->emails[ $hash ] = array(
			'atts'           => $atts,
			'error'          => $error,
			'filtered_trace' => $trace->get_filtered_trace(),
		);
	}

	public function process() {
		$this->data->counts = array(
			'preempted' => 0,
			'failed'    => 0,
			'succeeded' => 0,
			'total'     => 0,
		);

		if ( ! is_array( $this->data->preempted ) ) {
			$this->data->preempted = array();
		}

		if ( ! is_array( $this->data->failed ) ) {
			$this->data->failed = array();
		}

		foreach ( $this->data->emails as $hash => $email ) {
			$this->data->counts['total']++;

			if ( in_array( $hash, $this->data->preempted ) ) {
				$this->data->counts['preempted']++;
			} else if ( in_array( $hash, $this->data->failed ) ) {
				$this->data->counts['failed']++;
			} else {
				$this->data->counts['succeeded']++;
			}
		}
	}

}

# Load early to catch early emails
QM_Collectors::add( new QM_Collector_Emails() );