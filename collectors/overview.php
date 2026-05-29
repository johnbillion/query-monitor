<?php declare(strict_types = 1);
/**
 * General overview collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Overview>
 */
class QM_Collector_Overview extends QM_DataCollector {

	public $id = 'overview';

	/**
	 * @var array<string, float>
	 */
	protected $segment_times = array();

	/**
	 * @var array<string, list<array{start: float, end?: float}>>
	 */
	protected $notable_action_times = array();

	/**
	 * Notable actions to track on the timeline.
	 *
	 * @var list<string>
	 */
	protected static $notable_actions = array(
		'admin_footer',
		'admin_head',
		'admin_init',
		'after_setup_theme',
		'init',
		'muplugins_loaded',
		// 'parse_query',
		'parse_request',
		'plugins_loaded',
		// 'pre_get_posts',
		'send_headers',
		'setup_theme',
		'shutdown',
		'template_redirect',
		'wp_body_open',
		'wp_footer',
		'wp_head',
		'wp_loaded',
		'wp',
	);

	public function get_storage(): QM_Data {
		return new QM_Data_Overview();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		foreach ( self::$notable_actions as $action ) {
			add_action( $action, array( $this, 'track_action_start' ), PHP_INT_MIN );
			// For shutdown, end must be tracked before QM dispatches (HTML dispatcher runs at priority 9).
			$end_priority = ( 'shutdown' === $action ) ? 8 : PHP_INT_MAX;
			add_action( $action, array( $this, 'track_action_end' ), $end_priority );
		}

		add_action( 'wp', array( $this, 'segment_request' ), 0 );
		add_action( 'send_headers', array( $this, 'segment_query' ), 0 );
		add_action( 'template_redirect', array( $this, 'segment_template' ), 0 );
		add_action( 'shutdown', array( $this, 'segment_shutdown' ), 0 );
		add_action( 'shutdown', array( $this, 'process_timing' ), 0 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		foreach ( self::$notable_actions as $action ) {
			remove_action( $action, array( $this, 'track_action_start' ), PHP_INT_MIN );
			$end_priority = ( 'shutdown' === $action ) ? 8 : PHP_INT_MAX;
			remove_action( $action, array( $this, 'track_action_end' ), $end_priority );
		}

		remove_action( 'wp', array( $this, 'segment_request' ), 0 );
		remove_action( 'send_headers', array( $this, 'segment_query' ), 0 );
		remove_action( 'template_redirect', array( $this, 'segment_template' ), 0 );
		remove_action( 'shutdown', array( $this, 'segment_shutdown' ), 0 );
		remove_action( 'shutdown', array( $this, 'process_timing' ), 0 );

		parent::tear_down();
	}

	/**
	 * @return void
	 */
	public function track_action_start() {
		/** @var string $action */
		$action = current_filter();

		$this->notable_action_times[ $action ][] = array(
			'start' => ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000,
		);
	}

	/**
	 * @return void
	 */
	public function track_action_end() {
		/** @var string $action */
		$action = current_filter();

		if ( ! empty( $this->notable_action_times[ $action ] ) ) {
			$last = array_key_last( $this->notable_action_times[ $action ] );
			$this->notable_action_times[ $action ][ $last ]['end'] = ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000;
		}
	}

	/**
	 * @return void
	 */
	public function segment_request() {
		$this->segment_times['request'] = ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000;
	}

	/**
	 * @return void
	 */
	public function segment_query() {
		$this->segment_times['query'] = ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000;
	}

	/**
	 * @return void
	 */
	public function segment_template() {
		$this->segment_times['template'] = ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000;
	}

	/**
	 * @return void
	 */
	public function segment_shutdown() {
		$this->segment_times['shutdown'] = ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000;
	}

	/**
	 * Processes the timing and memory related stats as early as possible, so the
	 * data isn't skewed by collectors that are processed before this one.
	 *
	 * @return void
	 */
	public function process_timing() {
		$this->data->time_taken = self::timer_stop_float();

		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$this->data->memory = memory_get_peak_usage();
		} elseif ( function_exists( 'memory_get_usage' ) ) {
			$this->data->memory = memory_get_usage();
		} else {
			$this->data->memory = 0;
		}
	}

	/**
	 * @return void
	 */
	public function process() {
		if ( ! isset( $this->data->time_taken ) ) {
			$this->process_timing();
		}

		$this->data->time_limit = (int) ini_get( 'max_execution_time' );
		if ( ! empty( $this->data->time_limit ) ) {
			$this->data->time_usage = ( 100 / $this->data->time_limit ) * $this->data->time_taken;
		} else {
			$this->data->time_usage = 0;
		}

		$this->data->memory_limit = QM_Util::convert_hr_to_bytes( ini_get( 'memory_limit' ) ?: '0' );

		if ( $this->data->memory_limit > 0 ) {
			$this->data->memory_usage = ( 100 / $this->data->memory_limit ) * $this->data->memory;
		} else {
			$this->data->memory_usage = 0;
		}

		/** @var array{init: float, request?: float, query?: float, template?: float, shutdown?: float} $segments */
		$segments = array(
			'init' => 0.0,
		);

		if ( isset( $this->segment_times['request'] ) ) {
			$segments['request'] = $this->segment_times['request'];
		}
		if ( isset( $this->segment_times['query'] ) ) {
			$segments['query'] = $this->segment_times['query'];
		}
		if ( isset( $this->segment_times['template'] ) ) {
			$segments['template'] = $this->segment_times['template'];
		}
		if ( isset( $this->segment_times['shutdown'] ) ) {
			$segments['shutdown'] = $this->segment_times['shutdown'];
		}

		// Not currently using this as I need to figure out how to clearly display the data.
		// $this->data->segments = $segments;

		$this->data->actions = $this->notable_action_times;
	}

}

# Load early to catch early actions
QM_Collectors::add( new QM_Collector_Overview() );
