<?php declare(strict_types = 1);
/**
 * Acceptance tests for PHP errors.
 */

class PhpErrorsCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function WarningShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersPhpError( 'warning' );
		$I->seeInQMPanel( 'PHP Errors (1)', 'This is a test warning' );
	}

	public function NoticeShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersPhpError( 'notice' );
		$I->seeInQMPanel( 'PHP Errors (1)', 'This is a test notice' );
	}
}
