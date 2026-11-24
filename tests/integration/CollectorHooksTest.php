<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorHooksTest extends Test {

	/**
	 * @return array{
	 *   name: string,
	 *   actions: array<int, array{
	 *     priority: int,
	 *     callback: \QM_Data_Callback,
	 *   }>,
	 *   components: array<string, \QM_Component>,
	 * }|null
	 */
	protected static function find_hook( string $name ): ?array {
		$collector = new \QM_Collector_Hooks();
		$collector->process();

		/** @var \QM_Data_Hooks $data */
		$data = $collector->get_data();

		foreach ( $data->hooks as $hook ) {
			if ( $name === $hook['name'] ) {
				return $hook;
			}
		}

		return null;
	}

	/**
	 * @param array{
	 *   actions: array<int, array{
	 *     priority: int,
	 *     callback: \QM_Data_Callback,
	 *   }>,
	 * } $hook
	 * @return list<array{0: int, 1: ?string}>
	 */
	protected static function get_actions( array $hook ): array {
		return array_map(
			static function ( array $action ): array {
				return array( $action['priority'], $action['callback']->name );
			},
			array_values( $hook['actions'] )
		);
	}

	public function testFiredHookIsCollectedWithItsCallbacks(): void {
		add_action( 'qm/tests/hooks', 'wp_reset_postdata' );
		add_action( 'qm/tests/hooks', array( new \WP_Query(), 'reset_postdata' ), 5 );
		add_action( 'qm/tests/hooks', array( \WP_Theme_JSON_Resolver::class, 'clean_cached_data' ), 20 );

		// Callbacks from this file belong to the Query Monitor component, which is hidden by default.
		add_action( 'qm/tests/hooks', array( Supports\TestObject::class, 'greet' ) );

		do_action( 'qm/tests/hooks' );

		$hook = self::find_hook( 'qm/tests/hooks' );

		self::assertNotNull( $hook );
		self::assertSame(
			array(
				array( 5, 'WP_Query->reset_postdata()' ),
				array( 10, 'wp_reset_postdata()' ),
				array( 20, 'WP_Theme_JSON_Resolver::clean_cached_data()' ),
			),
			self::get_actions( $hook )
		);
		self::assertSame( array( 'core-core' ), array_keys( $hook['components'] ) );
	}

	public function testUnfiredHookIsNotCollected(): void {
		add_action( 'qm/tests/hooks/unfired', 'wp_reset_postdata' );

		self::assertNull( self::find_hook( 'qm/tests/hooks/unfired' ) );
	}

	public function testCoreAndQueryMonitorCallbacksCanBeHidden(): void {
		add_action( 'qm/tests/hooks/hidden', 'wp_reset_postdata' );
		add_action( 'qm/tests/hooks/hidden', array( Supports\TestObject::class, 'greet' ) );

		$wp_hook = $GLOBALS['wp_filter']['qm/tests/hooks/hidden'];

		$all = \QM_Hook::process( 'qm/tests/hooks/hidden', 'action', $wp_hook );
		$no_core = \QM_Hook::process( 'qm/tests/hooks/hidden', 'action', $wp_hook, false, true );
		$no_qm = \QM_Hook::process( 'qm/tests/hooks/hidden', 'action', $wp_hook, true, false );

		self::assertSame(
			array(
				array( 10, 'wp_reset_postdata()' ),
				array( 10, 'QM\Tests\Supports\TestObject::greet()' ),
			),
			self::get_actions( $all )
		);
		self::assertSame(
			array(
				array( 10, 'QM\Tests\Supports\TestObject::greet()' ),
			),
			self::get_actions( $no_core )
		);
		self::assertSame(
			array(
				array( 10, 'wp_reset_postdata()' ),
			),
			self::get_actions( $no_qm )
		);

		self::assertSame( array( 'core-core', 'plugin-query-monitor' ), array_keys( $all['components'] ) );
		self::assertSame( array( 'plugin-query-monitor' ), array_keys( $no_core['components'] ) );
		self::assertSame( array( 'core-core' ), array_keys( $no_qm['components'] ) );
	}

}
