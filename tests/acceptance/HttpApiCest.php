<?php declare(strict_types = 1);
/**
 * Acceptance tests for WordPress HTTP API requests.
 */

class HttpApiCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function SuccessfulHttpRequestShouldBeLogged( AcceptanceTester $I ): void {
		$I->amOnAPageThatMakesHttpRequest( 'successful_request' );
		$I->seeInQMPanel( 'HTTP API Calls (1)', 'http://httpbin/status/200' );
		$I->seeInQMPanel( 'HTTP API Calls (1)', '200 OK' );
	}

	public function ErrorHttpRequestShouldBeLogged( AcceptanceTester $I ): void {
		$I->amOnAPageThatMakesHttpRequest( '404_request' );
		$I->seeInQMPanelWithAlert( 'HTTP API Calls (1)', 'http://httpbin/status/404' );
		$I->seeInQMPanelWithAlert( 'HTTP API Calls (1)', '404 Not Found' );
	}
}
