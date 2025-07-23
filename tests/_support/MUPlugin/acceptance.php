<?php

add_action( 'init', function() {
	if ( ! isset( $_GET['_qm_acceptance_group'], $_GET['_qm_acceptance_test'] ) ) {
		return;
	}

	switch ( $_GET['_qm_acceptance_group'] ) {
		case 'doing_it_wrong':
			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'argument':
					_deprecated_argument( 'my_function', '2.0.0' );
					break;
				case 'class':
					if ( function_exists( '_deprecated_class' ) ) {
						_deprecated_class( 'My_Class', '2.0.0' );
					} else {
						// Fallback for WordPress < 6.4.0
						_doing_it_wrong( 'My_Class', 'Class is deprecated since version 2.0.0', '2.0.0' );
					}
					break;
				case 'constructor':
					_deprecated_constructor( 'My_Class', '2.0.0' );
					break;
				case 'file':
					_deprecated_file( 'my_file.php', '2.0.0' );
					break;
				case 'function':
					_deprecated_function( 'my_function', '2.0.0' );
					break;
				case 'hook':
					_deprecated_hook( 'my_hook', '2.0.0' );
					break;
				default:
					throw new \InvalidArgumentException( 'Unknown test: ' . $_GET['_qm_acceptance_test'] );
					break;
			}
			break;
		case 'php_errors':
			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'warning':
					trigger_error( 'This is a test warning', E_USER_WARNING );
					break;
				case 'notice':
					trigger_error( 'This is a test notice', E_USER_NOTICE );
					break;
				case 'suppressed-warning':
					@trigger_error( 'This is a test suppressed warning', E_USER_WARNING );
					break;
				case 'suppressed-notice':
					@trigger_error( 'This is a test suppressed notice', E_USER_NOTICE );
					break;
				default:
					throw new \InvalidArgumentException( 'Unknown test: ' . $_GET['_qm_acceptance_test'] );
					break;
			}
			break;
		case 'guzzle_requests':
			if ( ! class_exists( 'GuzzleHttp\Client' ) ) {
				throw new \Exception( 'Guzzle not available' );
			}

			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'successful_request':
					$stack = \GuzzleHttp\HandlerStack::create();
					$stack->push( QM_Collector_HTTP::guzzle_middleware() );
					$client = new \GuzzleHttp\Client( [ 'handler' => $stack ] );
					$client->get( 'https://httpbin.org/json' );
					break;
				case 'error_request':
					$stack = \GuzzleHttp\HandlerStack::create();
					$stack->push( QM_Collector_HTTP::guzzle_middleware() );
					$client = new \GuzzleHttp\Client( [ 'handler' => $stack ] );
					try {
						$client->get( 'https://httpbin.org/status/404' );
					} catch ( \GuzzleHttp\Exception\ClientException $e ) {
						// Expected 404 error
					}
					break;
				default:
					throw new \InvalidArgumentException( 'Unknown test: ' . $_GET['_qm_acceptance_test'] );
					break;
			}
			break;
		case 'callback_types':
			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'function':
					// Regular function callback
					add_action( 'qm_test_hook', '__return_true' );
					do_action( 'qm_test_hook' );
					break;
				case 'method':
					// Object method callback
					$obj = new class {
						public function test_method() {
							return true;
						}
					};
					add_action( 'qm_test_hook', [ $obj, 'test_method' ] );
					do_action( 'qm_test_hook' );
					break;
				case 'static_method':
					// Static method callback - create our own test class
					if ( ! class_exists( 'QM_Test_Static_Class' ) ) {
						class QM_Test_Static_Class {
							public static function test_static_method() {
								return 'static test result';
							}
						}
					}
					add_action( 'qm_test_hook', [ 'QM_Test_Static_Class', 'test_static_method' ] );
					do_action( 'qm_test_hook' );
					break;
				case 'closure':
					// Closure callback
					add_action( 'qm_test_hook', function() {
						// Test closure for acceptance testing
						return 'test closure result';
					} );
					do_action( 'qm_test_hook' );
					break;
				case 'invokable':
					// Invokable object callback
					add_action( 'qm_test_hook', new class {
						public function __invoke() {
							return 'invokable result';
						}
					} );
					do_action( 'qm_test_hook' );
					break;
				default:
					throw new \InvalidArgumentException( 'Unknown test: ' . $_GET['_qm_acceptance_test'] );
					break;
			}
			break;
		default:
			throw new \InvalidArgumentException( 'Unknown group: ' . $_GET['_qm_acceptance_group'] );
			break;
	}
} );
