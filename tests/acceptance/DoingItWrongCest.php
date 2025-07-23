<?php declare(strict_types = 1);
/**
 * Acceptance tests for doing it wrong.
 */

class DoingItWrongCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function DeprecatedArgumentShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'argument' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'Function my_function was called with an argument that is deprecated since version 2.0.0' );
	}

	public function DeprecatedClassShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'class' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'My_Class is deprecated since version 2.0.0' );
	}

	public function DeprecatedConstructorShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'constructor' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'The called constructor method for My_Class class is deprecated since version 2.0.0' );
	}

	public function DeprecatedFileShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'file' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'my_file.php is deprecated since version 2.0.0' );
	}

	public function DeprecatedFunctionShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'function' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'my_function is deprecated since version 2.0.0' );
	}

	public function DeprecatedHookShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'hook' );
		$I->seeInQMPanel( 'Doing it Wrong (1)', 'my_hook is deprecated since version 2.0.0' );
	}
}
