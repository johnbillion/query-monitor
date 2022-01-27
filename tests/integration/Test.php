<?php

declare(strict_types = 1);

namespace QM\Tests;

/**
 * @property \WP_UnitTest_Factory $factory
 */
abstract class Test extends \Codeception\TestCase\WPTestCase {

	public function _before(): void {
		if ( ! defined( 'WP_USE_THEMES' ) ) {
			define( 'WP_USE_THEMES', true );
		}

		if ( true !== WP_USE_THEMES ) {
			self::fail( 'WP_USE_THEMES should not be false' );
		}
	}

	public function _after(): void {}

	/**
	 * @return string
	 */
	public function go_to_with_template( string $url ) {

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}

		remove_action( 'template_redirect', 'redirect_canonical' );

		$this->go_to( $url );

		ob_start();
		require ABSPATH . WPINC . '/template-loader.php';
		return ob_get_clean();
	}

}
