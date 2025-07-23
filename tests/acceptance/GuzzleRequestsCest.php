<?php declare(strict_types = 1);
/**
 * Acceptance tests for Guzzle requests.
 */

class GuzzleRequestsCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function SuccessfulGuzzleRequestShouldBeLogged( AcceptanceTester $I ): void {
		$I->amOnAPageThatMakesGuzzleRequest( 'successful_request' );
		$I->seeQMMenu();
		$I->seeInQMPanel( 'HTTP API Calls (1)', 'https://httpbin.org/json' );
	}

	public function ErrorGuzzleRequestShouldBeLogged( AcceptanceTester $I ): void {
		$I->amOnAPageThatMakesGuzzleRequest( 'error_request' );
		$I->seeQMMenuWithWarning();
		$I->seeInQMPanel( 'HTTP API Calls (1)', 'https://httpbin.org/status/404' );
	}
}
