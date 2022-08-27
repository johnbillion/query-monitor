<?php
/**
 * Acceptance tests for basic user access.
 */

use Codeception\Example;

/**
 * @phpstan-type dataRole array{
 *   role: string,
 *   access: bool,
 * }
 */
class BasicCest {
	/**
	 * @dataProvider dataRoles
	 *
	 * @param Example<string,mixed> $data
	 */
	public function BasicAccessShouldWorkAsExpected( AcceptanceTester $I, Example $data ): void {
		/** @var dataRole $data */

		$I->haveUserInDatabase( $data['role'], $data['role'] );
		$I->loginAs( $data['role'], $data['role'] );
		$I->see(
			$data['role'],
			'#wpadminbar .display-name'
		);

		if ( $data['access'] ) {
			$I->dontSeeElement( '#query-monitor-main' );
			$I->click( '#wp-admin-bar-query-monitor' );
			$I->seeElement( '#query-monitor-main');
		} else {
			$I->dontSeeElement( '#wp-admin-bar-query-monitor' );
			$I->dontSeeElementInDOM( '#query-monitor-main');
		}
	}

	/**
	 * @return array<string,dataRole>
	 */
	private function dataRoles(): array {
		return [
			'administrator' => [
				'role' => 'administrator',
				'access' => true,
			],
			'editor' => [
				'role' => 'editor',
				'access' => false,
			],
			'author' => [
				'role' => 'author',
				'access' => false,
			],
			'contributor' => [
				'role' => 'contributor',
				'access' => false,
			],
			'subscriber' => [
				'role' => 'subscriber',
				'access' => false,
			],
		];
	}
}
