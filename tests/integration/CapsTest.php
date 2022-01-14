<?php

declare(strict_types = 1);

namespace QM\Tests;

class Capabilities extends Test {

	/**
	 * @dataProvider dataUserRolesAccess
	 */
	public function testUsersCanAccessQueryMonitor( string $role, bool $can_access ): void {
		$user = self::factory()->user->create( array(
			'role' => $role,
		) );

		$actual = user_can( $user, 'view_query_monitor' );
		self::assertSame( $can_access, $actual );
	}

	/**
	 * @return array<string, array{
	 *   0: string,
	 *   1: bool,
	 * }>
	 */
	public function dataUserRolesAccess() {
		$roles = array(
			'administrator' => array(
				'administrator',
				( ! is_multisite() ),
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

		return $roles;
	}

}
