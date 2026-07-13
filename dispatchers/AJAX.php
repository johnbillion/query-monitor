<?php declare(strict_types = 1);
/**
 * Ajax request dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @phpstan-import-type FatalError from QM_Dispatcher
 */
class QM_Dispatcher_AJAX extends QM_Dispatcher {

	public $id = 'ajax';

	/**
	 * @return bool
	 */
	public function is_active() {

		if ( ! QM_Util::is_ajax() ) {
			return false;
		}

		if ( ! self::user_can_view() ) {
			return false;
		}

		# Don't process if the minimum required actions haven't fired:
		if ( is_admin() ) {
			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}
		} else {
			if ( ! did_action( 'wp' ) ) {
				return false;
			}
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
function register_qm_dispatcher_ajax( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['ajax'] = new QM_Dispatcher_AJAX( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_ajax', 10, 2 );
