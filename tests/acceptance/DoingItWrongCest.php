<?php declare(strict_types = 1);
/**
 * Acceptance tests for doing it wrong.
 */

class DoingItWrongCest {
	public function _before( AcceptanceTester $I ): void {
		$I->haveUserInDatabase( 'administrator', 'administrator' );
		$I->loginAs( 'administrator', 'administrator' );
	}

	public function DeprecatedFunctionShouldBeHandled( AcceptanceTester $I ): void {
		$I->amDoingItWrong( 'function' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'my_function is deprecated since version 2.0.0' );
	}

	public function DeprecatedHookShouldBeHandled( AcceptanceTester $I ): void {
		$I->amDoingItWrong( 'hook' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'my_hook is deprecated since version 2.0.0' );
	}
}
