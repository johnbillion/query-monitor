<?php declare(strict_types = 1);

namespace QM\Tests;

class UserAccessTest extends Test {

	/**
	 * @dataProvider dataUserRolesAccess
	 */
	public function testUserAccessToQueryMonitor( string $role, bool $can_access ): void {
		$user = self::factory()->user->create( array(
			'role' => $role,
		) );

		if ( $can_access && is_multisite() ) {
			grant_super_admin( $user );
		}

		wp_set_current_user( $user );
		$this->go_to( home_url() );

		self::assertSame( $can_access, \QM_Dispatcher::user_can_view() );

		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$admin_bar = new \WP_Admin_Bar();
		$dispatcher = \QM_Dispatchers::get( 'html' );
		self::assertInstanceOf( \QM_Dispatcher_Html::class, $dispatcher );
		$dispatcher->action_admin_bar_menu( $admin_bar );

		if ( $can_access ) {
			self::assertNotNull( $admin_bar->get_node( 'query-monitor' ) );
			self::assertNotNull( $admin_bar->get_node( 'query-monitor-placeholder' ) );
		} else {
			self::assertNull( $admin_bar->get_node( 'query-monitor' ) );
		}

		// Isolate the user check from the other is_active() conditions.
		$dispatcher->did_footer = true;
		self::assertSame( $can_access, $dispatcher->is_active() );
	}

	/**
	 * @return array<string, array{
	 *   0: string,
	 *   1: bool,
	 * }>
	 */
	public function dataUserRolesAccess() {
		return array(
			'administrator' => array(
				'administrator',
				true,
			),
			'editor' => array(
				'editor',
				false,
			),
			'author' => array(
				'author',
				false,
			),
			'contributor' => array(
				'contributor',
				false,
			),
			'subscriber' => array(
				'subscriber',
				false,
			),
		);
	}

}
