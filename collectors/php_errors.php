<?php declare(strict_types = 1);
/**
 * PHP error collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'QM_ERROR_FATALS' ) ) {
	define( 'QM_ERROR_FATALS', E_ERROR | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );
}

/**
 * @extends QM_DataCollector<QM_Data_PHP_Errors>
 */
class QM_Collector_PHP_Errors extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'php_errors';

	/**
	 * @var int|null
	 */
	private $error_reporting = null;

	/**
	 * @var string|false|null
	 */
	private $display_errors = null;

	/**
	 * @var callable|null
	 */
	private $previous_error_handler = null;

	/**
	 * @var callable|null
	 */
	private $previous_exception_handler = null;

	/**
	 * @var string|null
	 */
	private static $unexpected_error = null;

	/**
	 * @var int
	 */
	private const NON_SILENT_ERROR_TYPES = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;

	public function get_storage(): QM_Data {
		return new QM_Data_PHP_Errors();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		if ( defined( 'QM_DISABLE_ERROR_HANDLER' ) && QM_DISABLE_ERROR_HANDLER ) {
			return;
		}

		parent::set_up();

		// Capture the last error that occurred before QM loaded:
		$prior_error = error_get_last();

		// Non-fatal error handler:
		$this->previous_error_handler = set_error_handler( array( $this, 'error_handler' ), ( E_ALL ^ QM_ERROR_FATALS ) );

		// Fatal error and uncaught exception handler:
		$this->previous_exception_handler = set_exception_handler( array( $this, 'exception_handler' ) );

		$this->error_reporting = error_reporting();
		$this->display_errors = ini_get( 'display_errors' );
		ini_set( 'display_errors', '0' );

		if ( $prior_error ) {
			$this->error_handler(
				$prior_error['type'],
				$prior_error['message'],
				$prior_error['file'],
				$prior_error['line'],
				null,
				false
			);
		}
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		if ( defined( 'QM_DISABLE_ERROR_HANDLER' ) && QM_DISABLE_ERROR_HANDLER ) {
			return;
		}

		if ( null !== $this->previous_error_handler ) {
			restore_error_handler();
		}

		if ( null !== $this->previous_exception_handler ) {
			restore_exception_handler();
		}

		if ( null !== $this->error_reporting ) {
			error_reporting( $this->error_reporting );
		}

		if ( is_string( $this->display_errors ) ) {
			ini_set( 'display_errors', $this->display_errors );
		}

		parent::tear_down();
	}

	/**
	 * Uncaught error handler.
	 *
	 * @param Throwable $e The error or exception.
	 * @return void
	 */
	public function exception_handler( $e ) {
		$error = 'Uncaught Error';

		if ( $e instanceof Exception ) {
			$error = sprintf(
				'Uncaught %s',
				get_class( $e )
			);
		}

		$this->output_fatal( 'Fatal error', array(
			'message' => sprintf(
				'%s: %s',
				$error,
				$e->getMessage()
			),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTrace(),
		) );

		// The error must be re-thrown or passed to the previously registered exception handler so that the error
		// is logged appropriately instead of discarded silently.
		if ( $this->previous_exception_handler ) {
			call_user_func( $this->previous_exception_handler, $e );
		} else {
			throw $e;
		}

		exit( 1 );
	}

	/**
	 * @param int     $errno    The error number.
	 * @param string  $message  The error message.
	 * @param string  $file     The file location.
	 * @param int     $line     The line number.
	 * @param mixed[] $context  The context being passed.
	 * @param bool    $do_trace Whether a stack trace should be included in the logged error data.
	 * @return bool
	 */
	public function error_handler( $errno, $message, $file = null, $line = null, $context = null, $do_trace = true ) {
		$level = null;

		/**
		 * Fires before logging the PHP error in Query Monitor.
		 *
		 * @since 2.7.0
		 *
		 * @param int          $errno   The error number.
		 * @param string       $message The error message.
		 * @param string|null  $file    The file location.
		 * @param int|null     $line    The line number.
		 * @param mixed[]|null $context The context being passed.
		 */
		do_action( 'qm/collect/new_php_error', $errno, $message, $file, $line, $context );

		switch ( $errno ) {

			case E_WARNING:
			case E_USER_WARNING:
				$level = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$level = 'notice';
				break;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$level = 'deprecated';
				break;

		}

		// E_STRICT is deprecated in PHP 8.4 so it needs to be behind a version check.
		if ( \PHP_VERSION_ID < 80400 ) {
			if ( null === $level && E_STRICT === $errno ) {
				$level = 'strict';
			}
		}

		if ( null === $level ) {
			return false;
		}

		if ( ! class_exists( 'QM_Backtrace' ) ) {
			return false;
		}

		$suppressed = false;

		if ( $this->is_error_suppressed() && 0 !== $this->error_reporting ) {
			// This is most likely an @-suppressed error
			$suppressed = true;
		}

		if ( ! isset( self::$unexpected_error ) ) {
			// These strings are from core. They're passed through `__()` as variables so they get translated at runtime
			// but do not get seen by GlotPress when it populates its database of translatable strings for QM.
			$unexpected_error = 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.';
			$wordpress_forums = 'https://wordpress.org/support/forums/';

			self::$unexpected_error = sprintf(
				call_user_func( '__', $unexpected_error ),
				call_user_func( '__', $wordpress_forums )
			);
		}

		// Intentionally skip reporting these core warnings. They're a distraction when developing offline.
		// The failed HTTP request will still appear in QM's output so it's not a big problem hiding these warnings.
		if ( false !== strpos( $message, self::$unexpected_error ) ) {
			return false;
		}

		$callsite = new QM_Data_Callsite();
		$callsite->file = $file;
		$callsite->line = $line;

		$trace = new QM_Backtrace( array(
			'callsite' => $callsite,
		) );
		$caller = $trace->get_caller();

		if ( $caller ) {
			$key = md5( $message . $file . $line . $caller->id );
		} else {
			$key = md5( $message . $file . $line );
		}

		if ( isset( $this->data->errors[ $key ] ) ) {
			$this->data->errors[ $key ]->count++;
		} else {
			$error = new QM_Data_PHP_Error();
			$error->errno = $errno;
			$error->level = $level;
			$error->suppressed = $suppressed;
			$error->message = wp_strip_all_tags( $message );
			$error->count = 1;

			if ( $do_trace ) {
				$error->trace = $trace;
			}

			$this->data->errors[ $key ] = $error;
		}

		$this->log_type( $level );

		/**
		 * Filters the PHP error handler return value. This can be used to control whether or not the default error
		 * handler is called after Query Monitor's.
		 *
		 * @since 2.7.0
		 *
		 * @param bool $return_value Error handler return value. Default false.
		 */
		return apply_filters( 'qm/collect/php_errors_return_value', false );
	}

	private function is_error_suppressed(): bool {
		$current_level = error_reporting();

		if ( PHP_MAJOR_VERSION > 7 ) {
			// https://www.php.net/manual/en/language.operators.errorcontrol.php
			return $current_level === self::NON_SILENT_ERROR_TYPES;
		}

		return 0 === $current_level;
	}

	/**
	 * @param string $error
	 * @param mixed[] $e
	 * @phpstan-param array{
	 *   message: string,
	 *   file: string,
	 *   line: int,
	 *   type?: int,
	 *   trace?: mixed|null,
	 * } $e
	 * @return void
	 */
	protected function output_fatal( $error, array $e ) {
		$is_rest_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		$is_ajax_request = ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		if ( $is_rest_request ) {
			$dispatcher = QM_Dispatchers::get( 'rest' );
		} elseif ( $is_ajax_request ) {
			$dispatcher = QM_Dispatchers::get( 'ajax' );
		} else {
			$dispatcher = QM_Dispatchers::get( 'html' );
		}

		if ( empty( $dispatcher ) ) {
			return;
		}

		if ( empty( $this->display_errors ) && ! $dispatcher::user_can_view() ) {
			return;
		}

		if ( ! headers_sent() ) {
			status_header( 500 );
		}

		$message = sprintf(
			'%s in %s on line %d',
			$e['message'],
			$e['file'],
			$e['line']
		);

		$dispatcher->output_fatal( $message, $e );
		exit;
	}

	/**
	 * Runs post-processing on the collected errors and updates the
	 * errors collected in the data->errors property.
	 *
	 * Any unreportable errors are placed in the data->filtered_errors
	 * property.
	 *
	 * @return void
	 */
	public function process() {
		if ( ! empty( $this->data->errors ) ) {
			/**
			 * Filters the levels used for reported PHP errors on a per-component basis.
			 *
			 * Error levels can be specified in order to silence certain error levels from
			 * plugins or the current theme. Most commonly, you may wish to use this filter
			 * in order to silence annoying notices from third party plugins that you do not
			 * have control over.
			 *
			 * Silenced errors will still appear in Query Monitor's output, but will not
			 * cause highlighting to appear in the top level admin toolbar.
			 *
			 * For example, to show all errors in the 'foo' plugin except PHP notices use:
			 *
			 *     add_filter( 'qm/collect/php_error_levels', function( array $levels ) {
			 *         $levels['plugin']['foo'] = ( E_ALL & ~E_NOTICE );
			 *         return $levels;
			 *     } );
			 *
			 * Errors from themes, WordPress core, and other components can also be filtered:
			 *
			 *     add_filter( 'qm/collect/php_error_levels', function( array $levels ) {
			 *         $levels['theme']['stylesheet'] = ( E_WARNING & E_USER_WARNING );
			 *         $levels['theme']['template']   = ( E_WARNING & E_USER_WARNING );
			 *         $levels['core']['core']        = ( 0 );
			 *         return $levels;
			 *     } );
			 *
			 * Any component which doesn't have an error level specified via this filter is
			 * assumed to have the default level of `E_ALL`, which shows all errors.
			 *
			 * Valid PHP error level bitmasks are supported for each component, including `0`
			 * to silence all errors from a component. See the PHP documentation on error
			 * reporting for more info: http://php.net/manual/en/function.error-reporting.php
			 *
			 * @since 2.7.0
			 *
			 * @param array<string,array<string,int>> $levels The error levels, keyed by component type (eg. 'plugin'),
			 *                                                then by component context (eg. 'foo').
			 */
			$levels = apply_filters( 'qm/collect/php_error_levels', array() );

			array_map( array( $this, 'filter_reportable_errors' ), $levels, array_keys( $levels ) );
		}
	}

	/**
	 * Filters the reportable PHP errors using the table specified. Users can customize the levels
	 * using the `qm/collect/php_error_levels` filter.
	 *
	 * @param array<string, int> $components     The error levels keyed by component context.
	 * @param string             $component_type The component type, for example 'plugin' or 'theme'.
	 * @return void
	 */
	public function filter_reportable_errors( array $components, $component_type ) {
		$all_errors = $this->data->errors;

		foreach ( $components as $component_context => $allowed_level ) {
			foreach ( $all_errors as $error_id => $error ) {
				if ( ! isset( $error->trace ) ) {
					continue;
				}

				if ( $this->is_reportable_error( $error->errno, $allowed_level ) ) {
					continue;
				}

				if ( ! $this->is_affected_component( $error->trace->get_component(), $component_type, $component_context ) ) {
					continue;
				}

				unset( $this->data->errors[ $error_id ] );
			}
		}

	}

	/**
	 * Checks if the component is of the given type and has the given context. This is
	 * used to scope an error to a plugin or theme.
	 *
	 * @param QM_Component $component         The component.
	 * @param string       $component_type    The component type for comparison.
	 * @param string       $component_context The component context for comparison.
	 * @return bool
	 */
	public function is_affected_component( $component, $component_type, $component_context ) {
		return ( $component->type === $component_type && $component->context === $component_context );
	}

	/**
	 * Checks if the error number specified is viewable based on the
	 * flags specified.
	 *
	 * Eg:- If a plugin had the config flags,
	 *
	 *     E_ALL & ~E_NOTICE
	 *
	 * then,
	 *
	 *     is_reportable_error( E_NOTICE, E_ALL & ~E_NOTICE ) is false
	 *     is_reportable_error( E_WARNING, E_ALL & ~E_NOTICE ) is true
	 *
	 * If the `$flag` is null, all errors are assumed to be
	 * reportable by default.
	 *
	 * @param int      $error_no The errno from PHP
	 * @param int|null $flags The config flags specified by users
	 * @return bool Whether the error is reportable.
	 */
	public function is_reportable_error( $error_no, $flags ) {
		$result = true;

		if ( null !== $flags ) {
			$result = (bool) ( $error_no & $flags );
		}

		return $result;
	}

	/**
	 * For testing purposes only. Sets the errors property manually.
	 * Needed to test the filter since the data property is protected.
	 *
	 * @param array<string, QM_Data_PHP_Error> $errors The list of errors
	 * @return void
	 */
	public function set_php_errors( $errors ) {
		$this->data->errors = $errors;
	}
}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_PHP_Errors() );
