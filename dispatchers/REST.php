<?php declare(strict_types = 1);
/**
 * REST API request dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @phpstan-import-type FatalError from QM_Dispatcher
 */
class QM_Dispatcher_REST extends QM_Dispatcher {

	public $id = 'rest';

	/**
	 * @return bool
	 */
	public function is_active() {

		# If the headers have already been sent then we can't do anything about it
		if ( headers_sent() ) {
			return false;
		}

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		if ( ! self::user_can_view() ) {
			return false;
		}

		return true;

	}

	/**
	 * @param string $message
	 * @param mixed[] $e
	 * @phpstan-param FatalError $e
	 */
	public function output_fatal( $message, array $e ): void {
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		}

		echo wp_json_encode(
			array(
				'code' => 'qm_fatal',
				'message' => $message,
				'data' => $e,
			),
			JSON_UNESCAPED_SLASHES
		);
	}
}

/**
 * @param array<string, QM_Dispatcher> $dispatchers
 * @param QM_Plugin $qm
 * @return array<string, QM_Dispatcher>
 */
function register_qm_dispatcher_rest( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['rest'] = new QM_Dispatcher_REST( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_rest', 10, 2 );
