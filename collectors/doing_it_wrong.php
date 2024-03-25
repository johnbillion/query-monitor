<?php declare(strict_types = 1);
/**
 * Doing it Wrong collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Doing_It_Wrong>
 */
class QM_Collector_Doing_It_Wrong extends QM_DataCollector {

	public $id = 'doing_it_wrong';

	public function get_storage(): QM_Data {
		return new QM_Data_Doing_It_Wrong();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_action( 'doing_it_wrong_run', array( $this, 'action_doing_it_wrong_run' ), 10, 3 );
		add_action( 'deprecated_function_run', array( $this, 'action_deprecated_function_run' ), 10, 3 );
		add_action( 'deprecated_constructor_run', array( $this, 'action_deprecated_constructor_run' ), 10, 3 );
		add_action( 'deprecated_file_included', array( $this, 'action_deprecated_file_included' ), 10, 4 );
		add_action( 'deprecated_argument_run', array( $this, 'action_deprecated_argument_run' ), 10, 3 );
		add_action( 'deprecated_hook_run', array( $this, 'action_deprecated_hook_run' ), 10, 4 );

		add_filter( 'deprecated_function_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_constructor_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_file_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_argument_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_hook_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'doing_it_wrong_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		remove_action( 'doing_it_wrong_run', array( $this, 'action_doing_it_wrong_run' ) );
		remove_action( 'deprecated_function_run', array( $this, 'action_deprecated_function_run' ) );
		remove_action( 'deprecated_constructor_run', array( $this, 'action_deprecated_constructor_run' ) );
		remove_action( 'deprecated_file_included', array( $this, 'action_deprecated_file_included' ) );
		remove_action( 'deprecated_argument_run', array( $this, 'action_deprecated_argument_run' ) );
		remove_action( 'deprecated_hook_run', array( $this, 'action_deprecated_hook_run' ) );

		remove_filter( 'deprecated_function_trigger_error', array( $this, 'maybe_prevent_error' ) );
		remove_filter( 'deprecated_constructor_trigger_error', array( $this, 'maybe_prevent_error' ) );
		remove_filter( 'deprecated_file_trigger_error', array( $this, 'maybe_prevent_error' ) );
		remove_filter( 'deprecated_argument_trigger_error', array( $this, 'maybe_prevent_error' ) );
		remove_filter( 'deprecated_hook_trigger_error', array( $this, 'maybe_prevent_error' ) );
		remove_filter( 'doing_it_wrong_trigger_error', array( $this, 'maybe_prevent_error' ) );
	}

	/**
	 * Prevents the PHP error (notice or deprecated) from being triggered for doing it wrong calls when the
	 * current user can view Query Monitor output.
	 *
	 * @param bool $trigger
	 * @return bool
	 */
	public function maybe_prevent_error( $trigger ) {
		if ( function_exists( 'wp_get_current_user' ) && current_user_can( 'view_query_monitor' ) ) {
			return false;
		}

		return $trigger;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array(
			'doing_it_wrong_run',
			'deprecated_function_run',
			'deprecated_constructor_run',
			'deprecated_file_included',
			'deprecated_argument_run',
			'deprecated_hook_run',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'deprecated_function_trigger_error',
			'deprecated_constructor_trigger_error',
			'deprecated_file_trigger_error',
			'deprecated_argument_trigger_error',
			'deprecated_hook_trigger_error',
			'doing_it_wrong_trigger_error',
		);
	}

	/**
	 * @param string $function_name
	 * @param string $message
	 * @param string $version
	 * @return void
	 */
	public function action_doing_it_wrong_run( $function_name, $message, $version ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		if ( $version ) {
			/* translators: %s: Version number. */
			$version = sprintf( __( '(This message was added in version %s.)', 'query-monitor' ), $version );
		}

		$this->data->actions[] = array(
			'hook'           => 'doing_it_wrong_run',
			'filtered_trace' => $trace->get_filtered_trace(),
			'component'      => $trace->get_component(),
			'message'        => sprintf(
				/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: WordPress version number. */
				__( 'Function %1$s was called incorrectly. %2$s %3$s', 'query-monitor' ),
				$function_name,
				$message,
				$version
			),
		);
	}

	/**
	 * @param string $function_name
	 * @param string $replacement
	 * @param string $version
	 * @return void
	 */
	public function action_deprecated_function_run( $function_name, $replacement, $version ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$message = sprintf(
			/* translators: 1: PHP function name, 2: Version number. */
			__( 'Function %1$s is deprecated since version %2$s with no alternative available.', 'query-monitor' ),
			$function_name,
			$version
		);

		if ( $replacement ) {
			$message = sprintf(
				/* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
				__( 'Function %1$s is deprecated since version %2$s! Use %3$s instead.', 'query-monitor' ),
				$function_name,
				$version,
				$replacement
			);
		}

		$this->data->actions[] = array(
			'hook'           => 'deprecated_function_run',
			'filtered_trace' => $trace->get_filtered_trace(),
			'component'      => $trace->get_component(),
			'message'        => $message,
		);
	}

	/**
	 * @param string $class_name
	 * @param string $version
	 * @param string $parent_class
	 * @return void
	 */
	public function action_deprecated_constructor_run( $class_name, $version, $parent_class ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$message = sprintf(
			/* translators: 1: PHP class name, 2: Version number, 3: __construct() method. */
			__( 'The called constructor method for %1$s class is deprecated since version %2$s! Use %3$s instead.', 'query-monitor' ),
			$class_name,
			$version,
			'<code>__construct()</code>'
		);

		if ( $parent_class ) {
			$message = sprintf(
				/* translators: 1: PHP class name, 2: PHP parent class name, 3: Version number, 4: __construct() method. */
				__( 'The called constructor method for %1$s class in %2$s is deprecated since version %3$s! Use %4$s instead.', 'query-monitor' ),
				$class_name,
				$parent_class,
				$version,
				'<code>__construct()</code>'
			);
		}

		$this->data->actions[] = array(
			'hook'           => 'deprecated_constructor_run',
			'filtered_trace' => $trace->get_filtered_trace(),
			'component'      => $trace->get_component(),
			'message'        => $message,
		);
	}

	/**
	 * @param string $file
	 * @param string $replacement
	 * @param string $version
	 * @param string $message
	 * @return void
	 */
	public function action_deprecated_file_included( $file, $replacement, $version, $message ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		if ( $replacement ) {
			$message = sprintf(
				/* translators: 1: PHP file name, 2: Version number, 3: Alternative file name, 4: Optional message regarding the change. */
				__( 'File %1$s is deprecated since version %2$s! Use %3$s instead. %4$s', 'query-monitor' ),
				$file,
				$version,
				$replacement,
				$message
			);
		} else {
			$message = sprintf(
				/* translators: 1: PHP file name, 2: Version number, 3: Optional message regarding the change. */
				__( 'File %1$s is deprecated since version %2$s with no alternative available. %3$s', 'query-monitor' ),
				$file,
				$version,
				$message
			);
		}

		$this->data->actions[] = array(
			'hook'           => 'deprecated_file_included',
			'filtered_trace' => $trace->get_filtered_trace(),
			'component'      => $trace->get_component(),
			'message'        => $message,
		);
	}

	/**
	 * @param string $function_name
	 * @param string $message
	 * @param string $version
	 * @return void
	 */
	public function action_deprecated_argument_run( $function_name, $message, $version ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		if ( $message ) {
			$message = sprintf(
				/* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
				__( 'Function %1$s was called with an argument that is deprecated since version %2$s! %3$s', 'query-monitor' ),
				$function_name,
				$version,
				$message
			);
		} else {
			$message = sprintf(
				/* translators: 1: PHP function name, 2: Version number. */
				__( 'Function %1$s was called with an argument that is deprecated since version %2$s with no alternative available.', 'query-monitor' ),
				$function_name,
				$version
			);
		}

		$this->data->actions[] = array(
			'hook'           => 'deprecated_argument_run',
			'filtered_trace' => $trace->get_filtered_trace(),
			'component'      => $trace->get_component(),
			'message'        => $message,
		);
	}

	/**
	 * @param string $hook
	 * @param string $replacement
	 * @param string $version
	 * @param string $message
	 * @return void
	 */
	public function action_deprecated_hook_run( $hook, $replacement, $version, $message ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		if ( $replacement ) {
			$message = sprintf(
				/* translators: 1: WordPress hook name, 2: Version number, 3: Alternative hook name, 4: Optional message regarding the change. */
				__( 'Hook %1$s is deprecated since version %2$s! Use %3$s instead. %4$s', 'query-monitor' ),
				$hook,
				$version,
				$replacement,
				$message
			);
		} else {
			$message = sprintf(
				/* translators: 1: WordPress hook name, 2: Version number, 3: Optional message regarding the change. */
				__( 'Hook %1$s is deprecated since version %2$s with no alternative available. %3$s', 'query-monitor' ),
				$hook,
				$version,
				$message
			);
		}

		$this->data->actions[] = array(
			'hook'           => 'deprecated_hook_run',
			'filtered_trace' => $trace->get_filtered_trace(),
			'component'      => $trace->get_component(),
			'message'        => $message,
		);
	}

}

# Load early to catch early actions
QM_Collectors::add( new QM_Collector_Doing_It_Wrong() );
