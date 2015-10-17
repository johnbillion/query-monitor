<?php

abstract class QM_UnitTestCase extends WP_UnitTestCase {

	public function setUp() {
		if ( ! defined( 'WP_USE_THEMES' ) ) {
			define( 'WP_USE_THEMES', true );
		}
		$this->assertTrue( WP_USE_THEMES );
		parent::setUp();
	}

	public function go_to_with_template( $url ) {

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}

		remove_action( 'template_redirect', 'redirect_canonical' );

		$go = $this->go_to( $url );

		ob_start();
		require ABSPATH . WPINC . '/template-loader.php';
		ob_end_clean();

		return $go;

	}

}
