<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorAssetsScriptsTest extends Test {

	/**
	 * @var mixed
	 */
	private $original_wp_scripts;

	/**
	 * @var mixed
	 */
	private $original_wp_script_modules;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->original_wp_scripts = $GLOBALS['wp_scripts'] ?? null;
		$this->original_wp_script_modules = $GLOBALS['wp_script_modules'] ?? null;

		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_script_modules'] = null;
	}

	#[\Override]
	public function tear_down(): void {
		$GLOBALS['wp_scripts'] = $this->original_wp_scripts;
		$GLOBALS['wp_script_modules'] = $this->original_wp_script_modules;

		parent::tear_down();
	}

	public function testFooterOnlyScriptsAreDetected(): void {
		wp_enqueue_script( 'qm-test-footer', home_url( 'qm-test-footer.js' ), array(), '1.0', true );

		$data = self::collect_page_load();
		$assets = self::get_assets_by_handle( $data );

		self::assertArrayHasKey( 'qm-test-footer', $assets );
		self::assertSame( 'footer', $assets['qm-test-footer']['position'] );
		self::assertNotContains( 'header', array_column( $data->assets ?? array(), 'position' ) );
	}

	public function testScriptModuleDependenciesAreHandled(): void {
		if ( ! function_exists( 'wp_register_script_module' ) || ! function_exists( 'wp_enqueue_script_module' ) ) {
			self::markTestSkipped( 'Script modules require WordPress 6.5 or later.' );
		}

		wp_register_script_module( 'qm-test-bottom', home_url( 'qm-test-bottom.js' ), array(), '3' );
		wp_register_script_module( 'qm-test-middle', home_url( 'qm-test-middle.js' ), array( 'qm-test-bottom' ), '2' );
		wp_enqueue_script_module( 'qm-test-top', home_url( 'qm-test-module.js' ), array( 'qm-test-middle' ), '1' );

		wp_scripts();

		$collector = new \QM_Collector_Assets_Scripts();
		$collector->process();

		/** @var \QM_Data_Assets $data */
		$data = $collector->get_data();
		$assets = self::get_assets_by_handle( $data );

		$expected = array(
			'qm-test-top' => array(
				'dependencies' => array( 'qm-test-middle' ),
				'dependents' => array(),
			),
			'qm-test-middle' => array(
				'dependencies' => array( 'qm-test-bottom' ),
				'dependents' => array( 'qm-test-top' ),
			),
			'qm-test-bottom' => array(
				'dependencies' => array(),
				'dependents' => array( 'qm-test-middle' ),
			),
		);

		foreach ( $expected as $handle => $deps ) {
			self::assertArrayHasKey( $handle, $assets );
			self::assertSame( 'modules', $assets[ $handle ]['position'], $handle );
			self::assertSame( $deps['dependencies'], $assets[ $handle ]['dependencies'], $handle );
			self::assertSame( $deps['dependents'], $assets[ $handle ]['dependents'], $handle );
		}
	}

	public function testScriptWithNoSourceHasAnEmptySource(): void {
		wp_register_script( 'qm-test-no-src-dep', '/qm-test-no-src-dep.js', array(), '1.0' );
		wp_register_script( 'qm-test-no-src', false, array( 'qm-test-no-src-dep' ), '1.0' );
		wp_enqueue_script( 'qm-test-no-src' );

		$assets = self::get_assets_by_handle( self::collect_page_load() );

		self::assertArrayHasKey( 'qm-test-no-src', $assets );
		self::assertSame( '', $assets['qm-test-no-src']['source'] );
	}

	public function testMissingDependenciesAreFlagged(): void {
		if ( version_compare( $GLOBALS['wp_version'], '6.9.1', '>=' ) ) {
			$this->setExpectedIncorrectUsage( 'WP_Scripts::add' );
		}

		wp_enqueue_script( 'qm-test-missing-dep', home_url( 'qm-test-missing-dep.js' ), array( 'qm-nonexistent-dependency' ), '1.0' );

		$data = self::collect_page_load();
		$assets = self::get_assets_by_handle( $data );

		self::assertArrayHasKey( 'qm-test-missing-dep', $assets );
		self::assertTrue( $assets['qm-test-missing-dep']['warning'] );
		self::assertArrayHasKey( 'qm-nonexistent-dependency', $data->missing_dependencies );
	}

	private static function collect_page_load(): \QM_Data_Assets {
		$collector = new \QM_Collector_Assets_Scripts();
		$collector->set_up();

		ob_start();

		try {
			do_action( 'wp_head' );
			do_action( 'wp_print_footer_scripts' );
		} finally {
			ob_end_clean();
			$collector->tear_down();
		}

		$collector->process();

		/** @var \QM_Data_Assets $data */
		$data = $collector->get_data();

		return $data;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_assets_by_handle( \QM_Data_Assets $data ): array {
		return array_column( $data->assets ?? array(), null, 'handle' );
	}

}
