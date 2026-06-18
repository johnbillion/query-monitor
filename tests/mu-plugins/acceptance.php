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
						_doing_it_wrong( 'My_Class', 'My_Class is deprecated since version 2.0.0', '2.0.0' );
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
				case 'buffet':
					trigger_error( 'This is a single test warning', E_USER_WARNING );
					_trigger_warning();
					_trigger_warning();
					trigger_error( 'This is a test notice', E_USER_NOTICE );
					@trigger_error( 'This is a test suppressed warning', E_USER_WARNING );
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
					// Create mock handler with a successful JSON response
					$mock = new \GuzzleHttp\Handler\MockHandler(
						[
							new \GuzzleHttp\Psr7\Response(
								200,
								[
									'Content-Type' => 'application/json',
								],
								json_encode(
									[
										'foo' => 'bar',
									]
								)
							),
						]
					);

					$stack = \GuzzleHttp\HandlerStack::create( $mock );
					$stack->push( QM_Collector_HTTP::guzzle_middleware() );
					$client = new \GuzzleHttp\Client( [ 'handler' => $stack ] );
					$client->get( 'https://example.org/json' );
					break;
				case 'error_request':
					// Create mock handler with a 404 response
					$mock = new \GuzzleHttp\Handler\MockHandler(
						[
							new \GuzzleHttp\Psr7\Response(
								404,
								[
									'Content-Type' => 'text/html',
								],
								'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
									<title>404 Not Found</title>
									<h1>Not Found</h1>
									<p>The requested URL was not found on the server.</p>'
							),
						]
					);

					$stack = \GuzzleHttp\HandlerStack::create( $mock );
					$stack->push( QM_Collector_HTTP::guzzle_middleware() );
					$client = new \GuzzleHttp\Client( [ 'handler' => $stack ] );
					try {
						$client->get( 'https://example.org/status/404' );
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
		case 'enqueued_scripts':
			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'script-modules':
					if ( ! function_exists( 'wp_register_script_module' ) ) {
						throw new \DomainException( 'Script modules are not supported in this version of WordPress' );
					}

					wp_register_script_module( 'qm-test-bottom', home_url( 'qm-test-bottom.js' ), [], '3', [ 'type' => 'module' ] );
					wp_register_script_module( 'qm-test-middle', home_url( 'qm-test-middle.js' ), ['qm-test-bottom'], '2', [ 'type' => 'module' ] );
					wp_enqueue_script_module( 'qm-test-top', home_url( 'qm-test-module.js' ), ['qm-test-middle'], '1', [ 'type' => 'module' ] );
					break;
				case 'footer-only':
					add_action( 'wp_enqueue_scripts', function() {
						wp_enqueue_script( 'qm-test-footer', home_url( 'qm-test-footer.js' ), [], '1.0', true );
					} );
					break;
				case 'protocol-relative':
					add_action( 'wp_enqueue_scripts', function() {
						// A protocol-relative URL on the current host so it's neither broken nor flagged as insecure.
						$host = wp_parse_url( home_url(), PHP_URL_HOST );
						$port = wp_parse_url( home_url(), PHP_URL_PORT );
						$src = '//' . $host . ( $port ? ':' . $port : '' ) . '/qm-test-protocol-relative.js';
						wp_enqueue_script( 'qm-test-protocol-relative', $src, [], '1.0' );
					} );
					break;
				case 'no-src':
					add_action( 'wp_enqueue_scripts', function() {
						// A script registered with a boolean false src, used only to bundle dependencies (like jquery).
						wp_register_script( 'qm-test-no-src-dep', home_url( 'qm-test-no-src-dep.js' ), [], '1.0' );
						wp_register_script( 'qm-test-no-src', false, [ 'qm-test-no-src-dep' ], '1.0' );
						wp_enqueue_script( 'qm-test-no-src' );
					} );
					break;
				case 'insecure-content':
					add_action( 'wp_enqueue_scripts', function() {
						wp_enqueue_script( 'qm-test-insecure', 'http://example.com/qm-test-insecure.js', [], '1.0' );
					} );
					break;
				case 'missing-dependency':
					add_action( 'wp_enqueue_scripts', function() {
						// Enqueue a script that depends on a handle that was never registered.
						wp_enqueue_script( 'qm-test-missing-dep', home_url( 'qm-test-missing-dep.js' ), [ 'qm-nonexistent-dependency' ], '1.0' );
					} );
					break;
				default:
					throw new \InvalidArgumentException( 'Unknown test: ' . $_GET['_qm_acceptance_test'] );
					break;
			}
			break;
		case 'db_queries':
			switch ( $_GET['_qm_acceptance_test'] ) {
				case 'non_utf8':
					global $wpdb;

					// Insert binary IP address data which produces non-UTF8
					// bytes in the logged SQL queries. This tests that QM can
					// gracefully encode the query data as JSON.
					$table = $wpdb->prefix . 'qm_binary_ip_test';

					$wpdb->query(
						"CREATE TABLE IF NOT EXISTS `{$table}` (
							`ip` varbinary(16) NOT NULL
						);"
					);

					$wpdb->insert(
						$table,
						array(
							'ip' => inet_pton( '192.168.1.0' ),
						),
						array( '%s' )
					);

					$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
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

function _trigger_warning() {
	trigger_error( 'This is a repeated test warning', E_USER_WARNING );
}
