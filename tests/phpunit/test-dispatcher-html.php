<?php

class TestDispatcherHTML extends QM_UnitTestCase {

	/** @var QM_Dispatcher_Html|null */
	protected $html = null;

	public function setUp() {

		parent::setUp();

		$admin = $this->factory->user->create_and_get( array(
			'role' => 'administrator',
		) );

		if ( is_multisite() ) {
			grant_super_admin( $admin->ID );
		}

		wp_set_current_user( $admin->ID );

		/** @var QM_Dispatcher_Html */
		$html = QM_Dispatchers::get( 'html' );

		$this->html = $html;
		$this->html->init();

	}

	/**
	 * https://github.com/johnbillion/query-monitor/issues/137
	 */
	public function testDispatcherRespectsLateChangeOfHttps() {
		global $wp_scripts;

		if ( isset( $_SERVER['HTTPS'] ) ) {
			$https = $_SERVER['HTTPS'];
		}

		$_SERVER['HTTPS'] = 'on';

		do_action( 'wp_enqueue_scripts' );

		$registered = $wp_scripts->registered;

		self::assertArrayHasKey( 'query-monitor', $registered );
		self::assertInstanceOf( '_WP_Dependency', $registered['query-monitor'] );
		self::assertSame( 'https', parse_url( $registered['query-monitor']->src, PHP_URL_SCHEME ) );

		if ( isset( $https ) ) {
			$_SERVER['HTTPS'] = $https;
		} else {
			unset( $_SERVER['HTTPS'] );
		}
	}

}
