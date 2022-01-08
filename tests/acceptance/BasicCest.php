<?php
/**
 * Acceptance tests for basic user access.
 */

use Codeception\Example;

class BasicCest {
	/**
	 * @dataProvider dataRoles
	 */
	public function BasicAccessShouldWorkAsExpected( AcceptanceTester $I, Example $data ): void {
		$I->haveUserInDatabase( $data['role'], $data['role'] );
		$I->loginAs( $data['role'], $data['role'] );
		$I->see(
			$data['role'],
			'#wpadminbar .display-name'
		);

		if ( $data['access'] ) {
			$I->seeElement( '#wp-admin-bar-query-monitor' );
			$I->seeElementInDOM( '#query-monitor-main');
		} else {
			$I->dontSeeElement( '#wp-admin-bar-query-monitor' );
			$I->dontSeeElementInDOM( '#query-monitor-main');
		}
	}

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
