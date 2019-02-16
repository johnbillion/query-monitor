<?php

class Test_Caps extends QM_UnitTestCase {

	/**
	 * @dataProvider user_roles_access
	 */
	public function test_users_can_access_query_monitor( $role, $can_access ) {
		$user = self::factory()->user->create( array(
			'role' => $role,
		) );

		$actual = user_can( $user, 'view_query_monitor' );
		$this->assertSame( $can_access, $actual );
	}

	public function user_roles_access() {
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
