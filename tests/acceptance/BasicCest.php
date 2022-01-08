<?php
/**
 * Acceptance tests for basic user access.
 */

class BasicCest {
	public function BasicAccessShouldWorkAsExpected( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}
}
