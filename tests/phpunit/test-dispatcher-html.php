<?php

class Test_Dispatcher_HTML extends QM_UnitTestCase {

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

		$this->html = QM_Dispatchers::get( 'html' );

		$this->assertInstanceOf( 'QM_Dispatcher_Html', $this->html );

		$this->html->init();

	}

	/**
	 * https://github.com/johnbillion/query-monitor/issues/137
	 */
	public function test_dispatcher_respects_late_change_of_https() {
		global $wp_scripts;

		if ( isset( $_SERVER['HTTPS'] ) ) {
			$https = $_SERVER['HTTPS'];
		}

		$_SERVER['HTTPS'] = 'on';

		do_action( 'wp_enqueue_scripts' );

		$registered = $wp_scripts->registered;

		$this->assertArrayHasKey( 'query-monitor', $registered );
		$this->assertInstanceOf( '_WP_Dependency', $registered['query-monitor'] );
		$this->assertSame( 'https', parse_url( $registered['query-monitor']->src, PHP_URL_SCHEME ) );

		if ( isset( $https ) ) {
			$_SERVER['HTTPS'] = $https;
		} else {
			unset( $_SERVER['HTTPS'] );
		}

	}

	public function test_admin_toolbar_for_home_page() {
		global $wpdb;

		$this->go_to_with_template( home_url() );

		$this->assertTrue( $this->html->is_active() );
		$this->assertTrue( $this->html->should_dispatch() );

		ob_start();
		$this->html->dispatch();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );

		$expected = array(
			'assets'        => false,
			'cache'         => false,
			'caps'          => true,
			'conditionals'  => false,
			'db_callers'    => true,
			'db_dupes'      => true,
			'db_queries'    => true,
			'debug_bar'     => false,
			'environment'   => true,
			'hooks'         => true,
			'http'          => true,
			'languages'     => true,
			'logger'        => false,
			'overview'      => false,
			'php_errors'    => false,
			'redirects'     => false,
			'request'       => true,
			'response'      => true,
			'rewrites'      => true,
			'timing'        => false,
			'transients'    => true,
		);

		$expected['db_components'] = ( $wpdb instanceof QM_DB );

		$collectors = QM_Collectors::init();
		$menu = $this->html->js_admin_bar_menu();

		$this->assertInternalType( 'array', $menu );
		$this->assertArrayHasKey( 'top', $menu );
		$this->assertArrayHasKey( 'sub', $menu );
		$this->assertNotEmpty( $menu['sub'] );

		foreach ( $collectors as $collector ) {
			$this->assertArrayHasKey( $collector->id, $expected, sprintf( '%s is not present in the test menu', $collector->id ) );
			if ( $expected[ $collector->id ] ) {
				$this->assertArrayHasKey( 'query-monitor-' . $collector->id, $menu['sub'] );
			} else {
				$this->assertArrayNotHasKey( 'query-monitor-' . $collector->id, $menu['sub'] );
			}
		}

		$exceptions = array(
			'assets-scripts',
			'assets-styles',
		);

		foreach ( $exceptions as $expected ) {
			$this->assertArrayHasKey( 'query-monitor-' . $expected, $menu['sub'] );
		}

	}

}
