<?php

class TestCapabilities extends QM_UnitTestCase {

	/**
	 * @dataProvider dataUserRolesAccess
	 */
	public function testUsersCanAccessQueryMonitor( $role, $can_access ) {
		$user = self::factory()->user->create( array(
			'role' => $role,
		) );

		$actual = user_can( $user, 'view_query_monitor' );
		$this->assertSame( $can_access, $actual );
	}

	public function dataUserRolesAccess() {
		$roles = array(
			array(
				'administrator',
				( ! is_multisite() ),
			),
			array(
				'editor',
				false,
			),
			array(
				'author',
				false,
			),
			array(
				'contributor',
				false,
			),
			array(
				'subscriber',
				false,
			),
		);

		return $roles;
	}

}
