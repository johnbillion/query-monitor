<?php declare(strict_types = 1);

namespace QM\Tests;

/**
 * @link https://github.com/johnbillion/query-monitor/issues/555
 */
class LoginPageTest extends Test {

	#[\Override]
	public function tear_down(): void {
		unset( $_COOKIE[ QM_COOKIE ] );
		self::get_html_dispatcher()->did_footer = false;

		parent::tear_down();
	}

	public function testUserCannotViewAfterAuthCookieIsCleared(): void {
		wp_set_current_user( self::create_admin() );

		self::assertTrue( \QM_Dispatcher::user_can_view() );

		do_action( 'clear_auth_cookie' );

		self::assertTrue( is_user_logged_in() );
		self::assertFalse( \QM_Dispatcher::user_can_view() );
	}

	public function testHtmlDispatcherIsInactiveAfterAuthCookieIsCleared(): void {
		wp_set_current_user( self::create_admin() );

		$dispatcher = self::get_html_dispatcher();
		$dispatcher->did_footer = true;

		// Core's callbacks send headers, which fails in the CLI.
		remove_all_actions( 'login_init' );
		do_action( 'login_init' );

		self::assertTrue( $dispatcher->is_active() );

		do_action( 'clear_auth_cookie' );

		self::assertFalse( $dispatcher->is_active() );
	}

	public function testUserWithValidCookieCanViewAfterAuthCookieIsCleared(): void {
		$user_id = self::create_admin();

		wp_set_current_user( $user_id );

		$_COOKIE[ QM_COOKIE ] = wp_generate_auth_cookie( $user_id, time() + HOUR_IN_SECONDS, 'logged_in' );

		do_action( 'clear_auth_cookie' );

		self::assertTrue( \QM_Dispatcher::user_can_view() );
	}

	private static function create_admin(): int {
		$user_id = self::factory()->user->create( array(
			'role' => 'administrator',
		) );

		self::assertIsInt( $user_id );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}

		return $user_id;
	}

	private static function get_html_dispatcher(): \QM_Dispatcher_Html {
		$dispatcher = \QM_Dispatchers::get( 'html' );

		self::assertInstanceOf( \QM_Dispatcher_Html::class, $dispatcher );

		return $dispatcher;
	}

}
