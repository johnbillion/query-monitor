<?php
/**
 * PHP error collector.
 *
 * @package query-monitor
 */

defined( 'ABSPATH' ) || exit;

define( 'QM_ERROR_FATALS', E_ERROR | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );

class QM_Collector_PHP_Errors extends QM_Collector {

	public $id               = 'php_errors';
	public $types            = array();
	private $error_reporting = null;
	private $display_errors  = null;
	private $exception_handler = null;
	private static $unexpected_error;

	public function __construct() {
		if ( defined( 'QM_DISABLE_ERROR_HANDLER' ) && QM_DISABLE_ERROR_HANDLER ) {
			return;
		}

		parent::__construct();

		// Capture the last error that occurred before QM loaded:
		$prior_error = error_get_last();

		// Non-fatal error handler for all PHP versions:
		set_error_handler( array( $this, 'error_handler' ), ( E_ALL ^ QM_ERROR_FATALS ) );

		if ( ! interface_exists( 'Throwable' ) ) {
			// Fatal error handler for PHP < 7:
			register_shutdown_function( array( $this, 'shutdown_handler' ) );
		}

		// Fatal error handler for PHP >= 7, and uncaught exception handler for all PHP versions:
		$this->exception_handler = set_exception_handler( array( $this, 'exception_handler' ) );

		$this->error_reporting = error_reporting();
		$this->display_errors  = ini_get( 'display_errors' );
		ini_set( 'display_errors', 0 );

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
	 * Uncaught exception handler.
	 *
	 * In PHP >= 7 this will receive a Throwable object.
	 * In PHP < 7 it will receive an Exception object.
	 *
	 * @param Throwable|Exception $e The error or exception.
	 */
	public function exception_handler( $e ) {
		if ( is_a( $e, 'Exception' ) ) {
			$error = 'Uncaught Exception';
		} else {
			$error = 'Uncaught Error';
		}

		$this->output_fatal( 'Fatal error', array(
			'message' => sprintf(
				'%s: %s',
				$error,
				$e->getMessage()
			),
			'file'    => $e->getFile(),
			'line'    => $e->getLine(),
			'trace'   => $e->getTrace(),
		) );

		// The exception must be re-thrown or passed to the previously registered exception handler so that the error
		// is logged appropriately instead of discarded silently.
		if ( $this->exception_handler ) {
			call_user_func( $this->exception_handler, $e );
		} else {
			throw $e;
		}

		exit( 1 );
	}

	public function error_handler( $errno, $message, $file = null, $line = null, $context = null, $do_trace = true ) {

		/**
		 * Fires before logging the PHP error in Query Monitor.
		 *
		 * @since 2.7.0
		 *
		 * @param int    $errno   The error number.
		 * @param string $message The error message.
		 * @param string $file    The file location.
		 * @param string $line    The line number.
		 * @param string $context The context being passed.
		 */
		do_action( 'qm/collect/new_php_error', $errno, $message, $file, $line, $context );

		switch ( $errno ) {

			case E_WARNING:
			case E_USER_WARNING:
				$type = 'warning';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'notice';
				break;

			case E_STRICT:
				$type = 'strict';
				break;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$type = 'deprecated';
				break;

			default:
				return false;
				break;

		}

		if ( ! class_exists( 'QM_Backtrace' ) ) {
			return false;
		}

		$error_group = 'errors';

		if ( 0 === error_reporting() && 0 !== $this->error_reporting ) {
			// This is most likely an @-suppressed error
			$error_group = 'suppressed';
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

		$trace  = new QM_Backtrace( array(
			'ignore_current_filter' => false,
		) );
		$caller = $trace->get_caller();
		$key    = md5( $message . $file . $line . $caller['id'] );

		if ( isset( $this->data[ $error_group ][ $type ][ $key ] ) ) {
			$this->data[ $error_group ][ $type ][ $key ]['calls']++;
		} else {
			$this->data[ $error_group ][ $type ][ $key ] = array(
				'errno'    => $errno,
				'type'     => $type,
				'message'  => wp_strip_all_tags( $message ),
				'file'     => $file,
				'filename' => QM_Util::standard_dir( $file, '' ),
				'line'     => $line,
				'trace'    => ( $do_trace ? $trace : null ),
				'calls'    => 1,
			);
		}

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

	/**
	 * Displays fatal error output for sites running PHP < 7.
	 */
	public function shutdown_handler() {

		$e = error_get_last();

		if ( empty( $e ) || ! ( $e['type'] & QM_ERROR_FATALS ) ) {
			return;
		}

		if ( $e['type'] & E_RECOVERABLE_ERROR ) {
			$error = 'Catchable fatal error';
		} else {
			$error = 'Fatal error';
		}

		$this->output_fatal( $error, $e );
	}

	protected function output_fatal( $error, array $e ) {
		$dispatcher = QM_Dispatchers::get( 'html' );

		if ( empty( $dispatcher ) ) {
			return;
		}

		if ( empty( $this->display_errors ) && ! $dispatcher::user_can_view() ) {
			return;
		}

		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		require_once dirname( __DIR__ ) . '/output/Html.php';

		// This hides the subsequent message from the fatal error handler in core. It cannot be
		// disabled by a plugin so we'll just hide its output.
		echo '<style type="text/css"> .wp-die-message { display: none; } </style>';

		printf(
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
			'<link rel="stylesheet" href="%s" media="all" />',
			esc_url( includes_url( 'css/dashicons.css' ) )
		);
		printf(
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
			'<link rel="stylesheet" href="%s" media="all" />',
			esc_url( QueryMonitor::init()->plugin_url( 'assets/query-monitor.css' ) )
		);

		// This unused wrapper with ann attribute serves to help the #qm-fatal div break out of an
		// attribute if a fatal has occured within one.
		echo '<div data-qm="qm">';

		printf(
			'<div id="qm-fatal" data-qm-message="%1$s" data-qm-file="%2$s" data-qm-line="%3$d">',
			esc_attr( $e['message'] ),
			esc_attr( QM_Util::standard_dir( $e['file'], '' ) ),
			esc_attr( $e['line'] )
		);

		echo '<div class="qm-fatal-wrap">';

		if ( QM_Output_Html::has_clickable_links() ) {
			$file = QM_Output_Html::output_filename( $e['file'], $e['file'], $e['line'], true );
		} else {
			$file = esc_html( $e['file'] );
		}

		printf(
			'<p><span class="dashicons dashicons-warning" aria-hidden="true"></span> <b>%1$s</b>: %2$s<br>in <b>%3$s</b> on line <b>%4$d</b></p>',
			esc_html( $error ),
			nl2br( esc_html( $e['message'] ), false ),
			$file,
			intval( $e['line'] )
		); // WPCS: XSS ok.

		if ( ! empty( $e['trace'] ) ) {
			echo '<p>' . esc_html__( 'Call stack:', 'query-monitor' ) . '</p>';
			echo '<ol>';
			foreach ( $e['trace'] as $frame ) {
				$callback = QM_Util::populate_callback( $frame );

				printf(
					'<li>%s</li>',
					QM_Output_Html::output_filename( $callback['name'], $frame['file'], $frame['line'] )
				); // WPCS: XSS ok.
			}
			echo '</ol>';
		}

		echo '</div>';

		echo '<h2>' . esc_html__( 'Query Monitor', 'query-monitor' ) . '</h2>';

		echo '</div>';
		echo '</div>';
	}

	public function post_process() {
		ini_set( 'display_errors', $this->display_errors );
		restore_error_handler();
		restore_exception_handler();
	}

	/**
	 * Runs post-processing on the collected errors and updates the
	 * errors collected in the data->errors property.
	 *
	 * Any unreportable errors are placed in the data->filtered_errors
	 * property.
	 */
	public function process() {
		$this->types = array(
			'errors'     => array(
				'warning'    => _x( 'Warning', 'PHP error level', 'query-monitor' ),
				'notice'     => _x( 'Notice', 'PHP error level', 'query-monitor' ),
				'strict'     => _x( 'Strict', 'PHP error level', 'query-monitor' ),
				'deprecated' => _x( 'Deprecated', 'PHP error level', 'query-monitor' ),
			),
			'suppressed' => array(
				'warning'    => _x( 'Warning (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
				'notice'     => _x( 'Notice (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
				'strict'     => _x( 'Strict (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
				'deprecated' => _x( 'Deprecated (Suppressed)', 'Suppressed PHP error level', 'query-monitor' ),
			),
			'silenced'   => array(
				'warning'    => _x( 'Warning (Silenced)', 'Silenced PHP error level', 'query-monitor' ),
				'notice'     => _x( 'Notice (Silenced)', 'Silenced PHP error level', 'query-monitor' ),
				'strict'     => _x( 'Strict (Silenced)', 'Silenced PHP error level', 'query-monitor' ),
				'deprecated' => _x( 'Deprecated (Silenced)', 'Silenced PHP error level', 'query-monitor' ),
			),
		);
		$components  = array();

		if ( ! empty( $this->data ) && ! empty( $this->data['errors'] ) ) {
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
			 * @param int[] $levels The error levels used for each component.
			 */
			$levels = apply_filters( 'qm/collect/php_error_levels', array() );

			/**
			 * Controls whether silenced PHP errors are hidden entirely by Query Monitor.
			 *
			 * To hide silenced errors, use:
			 *
			 *     add_filter( 'qm/collect/hide_silenced_php_errors', '__return_true' );
			 *
			 * @since 2.7.0
			 *
			 * @param bool $hide Whether to hide silenced PHP errors. Default false.
			 */
			$this->hide_silenced_php_errors = apply_filters( 'qm/collect/hide_silenced_php_errors', false );

			array_map( array( $this, 'filter_reportable_errors' ), $levels, array_keys( $levels ) );

			foreach ( $this->types as $error_group => $error_types ) {
				foreach ( $error_types as $type => $title ) {
					if ( isset( $this->data[ $error_group ][ $type ] ) ) {
						foreach ( $this->data[ $error_group ][ $type ] as $error ) {
							if ( $error['trace'] ) {
								$component                      = $error['trace']->get_component();
								$components[ $component->name ] = $component->name;
							}
						}
					}
				}
			}
		}

		$this->data['components'] = $components;
	}

	/**
	 * Filters the reportable PHP errors using the table specified. Users can customize the levels
	 * using the `qm/collect/php_error_levels` filter.
	 *
	 * @param int[]  $components     The error levels keyed by component name.
	 * @param string $component_type The component type, for example 'plugin' or 'theme'.
	 */
	public function filter_reportable_errors( array $components, $component_type ) {
		$all_errors = $this->data['errors'];

		foreach ( $components as $component_context => $allowed_level ) {
			foreach ( $all_errors as $error_level => $errors ) {
				foreach ( $errors as $error_id => $error ) {
					if ( $this->is_reportable_error( $error['errno'], $allowed_level ) ) {
						continue;
					}

					if ( ! $error['trace'] ) {
						continue;
					}

					if ( ! $this->is_affected_component( $error['trace']->get_component(), $component_type, $component_context ) ) {
						continue;
					}

					unset( $this->data['errors'][ $error_level ][ $error_id ] );

					if ( $this->hide_silenced_php_errors ) {
						continue;
					}

					$this->data['silenced'][ $error_level ][ $error_id ] = $error;
				}
			}
		}

		$this->data['errors'] = array_filter( $this->data['errors'] );
	}

	/**
	 * Checks if the component is of the given type and has the given context. This is
	 * used to scope an error to a plugin or theme.
	 *
	 * @param object $component         The component.
	 * @param string $component_type    The component type for comparison.
	 * @param string $component_context The component context for comparison.
	 * @return bool
	 */
	public function is_affected_component( $component, $component_type, $component_context ) {
		if ( empty( $component ) ) {
			return false;
		}
		return ( $component->type === $component_type && $component->context === $component_context );
	}

	/**
	 * Checks if the error number specified is viewable based on the
	 * flags specified.
	 *
	 * @param int $error_no The errno from PHP
	 * @param int $flags The config flags specified by users
	 * @return int Truthy int value if reportable else 0.
	 *
	 * Eg:- If a plugin had the config flags,
	 *
	 * E_ALL & ~E_NOTICE
	 *
	 * then,
	 *
	 * is_reportable_error( E_NOTICE, E_ALL & ~E_NOTICE ) is false
	 * is_reportable_error( E_WARNING, E_ALL & ~E_NOTICE ) is true
	 *
	 * If the $flag is null, all errors are assumed to be
	 * reportable by default.
	 */
	public function is_reportable_error( $error_no, $flags ) {
		if ( ! is_null( $flags ) ) {
			$result = $error_no & $flags;
		} else {
			$result = 1;
		}

		return (bool) $result;
	}

	/**
	 * For testing purposes only. Sets the errors property manually.
	 * Needed to test the filter since the data property is protected.
	 *
	 * @param array $errors The list of errors
	 */
	public function set_php_errors( $errors ) {
		$this->data['errors'] = $errors;
	}
}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_PHP_Errors() );
