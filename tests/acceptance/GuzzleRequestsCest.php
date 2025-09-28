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
		$I->seeInQMPanel( 'HTTP API Calls (1)', 'http://httpbin/json' );
		$I->seeInQMPanel( 'HTTP API Calls (1)', '200 OK' );
	}

	public function ErrorGuzzleRequestShouldBeLogged( AcceptanceTester $I ): void {
		$I->amOnAPageThatMakesGuzzleRequest( 'error_request' );
		$I->seeInQMPanelWithAlert( 'HTTP API Calls (1)', 'http://httpbin/status/404' );
		$I->seeInQMPanelWithAlert( 'HTTP API Calls (1)', '404 Not Found' );
	}
}
