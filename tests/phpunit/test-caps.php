<?php

class TestCapabilities extends QM_UnitTestCase {

	/**
	 * @dataProvider dataUserRolesAccess
	 */
	public function testUsersCanAccessQueryMonitor( string $role, bool $can_access ) {
		$user = self::factory()->user->create( array(
			'role' => $role,
		) );

		$actual = user_can( $user, 'view_query_monitor' );
		self::assertSame( $can_access, $actual );
	}

	/**
	 * @return array<int, array{
	 *   0: string,
	 *   1: bool,
	 * }>
	 */
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
