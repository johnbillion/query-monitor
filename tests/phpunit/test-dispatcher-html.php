<?php

class Test_Dispatcher_HTML extends WP_UnitTestCase {

	/**
	 * https://github.com/johnbillion/query-monitor/issues/137
	 */
	public function test_dispatcher_respects_late_change_of_https() {

		$admin = $this->factory->user->create_and_get( array(
			'role' => 'administrator',
		) );
		$admin->add_cap( 'view_query_monitor' );

		wp_set_current_user( $admin->ID );

		QM_Dispatchers::get( 'html' )->init();

		$_SERVER['HTTPS'] = 'on';

		do_action( 'wp_enqueue_scripts' );

		$registered = wp_scripts()->registered;

		$this->assertArrayHasKey( 'query-monitor', $registered );
		$this->assertInstanceOf( '_WP_Dependency', $registered['query-monitor'] );
		$this->assertSame( 'https', parse_url( $registered['query-monitor']->src, PHP_URL_SCHEME ) );

	}

}
