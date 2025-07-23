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

	/**
	 * @var bool
	 */
	private $collecting = false;

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
		add_action( 'deprecated_class_run', array( $this, 'action_deprecated_class_run' ), 10, 3 );

		add_filter( 'deprecated_function_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_constructor_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_file_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_argument_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_hook_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'doing_it_wrong_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		add_filter( 'deprecated_class_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_action( 'doing_it_wrong_run', array( $this, 'action_doing_it_wrong_run' ) );
		remove_action( 'deprecated_function_run', array( $this, 'action_deprecated_function_run' ) );
		remove_action( 'deprecated_constructor_run', array( $this, 'action_deprecated_constructor_run' ) );
		remove_action( 'deprecated_file_included', array( $this, 'action_deprecated_file_included' ) );
		remove_action( 'deprecated_argument_run', array( $this, 'action_deprecated_argument_run' ) );
		remove_action( 'deprecated_hook_run', array( $this, 'action_deprecated_hook_run' ) );
		remove_action( 'deprecated_class_run', array( $this, 'action_deprecated_class_run' ) );

		remove_filter( 'deprecated_function_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		remove_filter( 'deprecated_constructor_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		remove_filter( 'deprecated_file_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		remove_filter( 'deprecated_argument_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		remove_filter( 'deprecated_hook_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		remove_filter( 'doing_it_wrong_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );
		remove_filter( 'deprecated_class_trigger_error', array( $this, 'maybe_prevent_error' ), 999 );

		parent::tear_down();
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
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Doing_It_Wrong_Run(
			$trace,
			[
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			]
		);

		$this->collecting = false;
	}

	/**
	 * @param string $function_name
	 * @param string $replacement
	 * @param string $version
	 * @return void
	 */
	public function action_deprecated_function_run( $function_name, $replacement, $version ) {
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Deprecated_Function_Run(
			$trace,
			[
				'function_name' => $function_name,
				'replacement'   => $replacement,
				'version'       => $version,
			]
		);

		$this->collecting = false;
	}

	/**
	 * @param string $class_name
	 * @param string $version
	 * @param string $parent_class
	 * @return void
	 */
	public function action_deprecated_constructor_run( $class_name, $version, $parent_class ) {
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Deprecated_Constructor_Run(
			$trace,
			[
				'class_name'   => $class_name,
				'parent_class' => $parent_class,
				'version'      => $version,
			]
		);

		$this->collecting = false;
	}

	/**
	 * @param string $file
	 * @param string $replacement
	 * @param string $version
	 * @param string $message
	 * @return void
	 */
	public function action_deprecated_file_included( $file, $replacement, $version, $message ) {
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Deprecated_File_Included(
			$trace,
			[
				'file'        => $file,
				'replacement' => $replacement,
				'version'     => $version,
				'message'     => $message,
			]
		);

		$this->collecting = false;
	}

	/**
	 * @param string $function_name
	 * @param string $message
	 * @param string $version
	 * @return void
	 */
	public function action_deprecated_argument_run( $function_name, $message, $version ) {
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Deprecated_Argument_Run(
			$trace,
			[
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			]
		);
		$this->collecting = false;
	}

	/**
	 * @param string $hook
	 * @param string $replacement
	 * @param string $version
	 * @param string $message
	 * @return void
	 */
	public function action_deprecated_hook_run( $hook, $replacement, $version, $message ) {
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Deprecated_Hook_Run(
			$trace,
			[
				'hook'        => $hook,
				'replacement' => $replacement,
				'version'     => $version,
				'message'     => $message,
			]
		);

		$this->collecting = false;
	}

	/**
	 * @param string $class_name
	 * @param string $replacement
	 * @param string $version
	 * @return void
	 */
	public function action_deprecated_class_run( $class_name, $replacement, $version ) {
		if ( $this->collecting ) {
			return;
		}

		$this->collecting = true;

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_action() => true,
			),
		) );

		$this->data->actions[] = new QM_Deprecated_Class_Run(
			$trace,
			[
				'class_name'  => $class_name,
				'replacement' => $replacement,
				'version'     => $version,
			]
		);

		$this->collecting = false;
	}
}

# Load early to catch early actions
QM_Collectors::add( new QM_Collector_Doing_It_Wrong() );
