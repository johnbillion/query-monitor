<?php declare(strict_types = 1);
/**
 * Acceptance tests for doing it wrong.
 */

class DoingItWrongCest {
	public function _before( AcceptanceTester $I ): void {
		$I->haveUserInDatabase( 'administrator', 'administrator' );
		$I->loginAs( 'administrator', 'administrator' );
	}

	public function DeprecatedArgumentShouldBeHandled( AcceptanceTester $I ): void {
		$I->amDoingItWrong( 'argument' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'Function my_function was called with an argument that is deprecated since version 2.0.0' );
	}

	public function DeprecatedConstructorShouldBeHandled( AcceptanceTester $I ): void {
		$I->amDoingItWrong( 'constructor' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'The called constructor method for My_Class class is deprecated since version 2.0.0' );
	}

	public function DeprecatedFileShouldBeHandled( AcceptanceTester $I ): void {
		$I->amDoingItWrong( 'file' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'my_file.php is deprecated since version 2.0.0' );
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
