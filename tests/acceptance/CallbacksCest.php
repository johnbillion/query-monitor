<?php declare(strict_types = 1);
/**
 * Acceptance tests for callback types in Hooks and Actions panel.
 */

class CallbacksCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function FunctionCallbackShouldBeDisplayed( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersCallbackType( 'function' );
		$I->seeQMMenu();
		$I->seeInQMPanel( 'Hooks & Actions', '__return_true()' );
		$I->seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	}

	public function MethodCallbackShouldBeDisplayed( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersCallbackType( 'method' );
		$I->seeQMMenu();
		$I->seeInQMPanel( 'Hooks & Actions', 'test_method()' );
		$I->seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	}

	public function StaticMethodCallbackShouldBeDisplayed( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersCallbackType( 'static_method' );
		$I->seeQMMenu();
		$I->seeInQMPanel( 'Hooks & Actions', 'QM_Test_Static_Class::test_static_method()' );
		$I->seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	}

	public function ClosureCallbackShouldBeDisplayed( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersCallbackType( 'closure' );
		$I->seeQMMenu();
		$I->seeInQMPanel( 'Hooks & Actions', 'Closure: ' );
		$I->seeInQMPanel( 'Hooks & Actions', 'acceptance.php' );
		$I->seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	}

	public function InvokableCallbackShouldBeDisplayed( AcceptanceTester $I ): void {
		$I->amOnAPageThatTriggersCallbackType( 'invokable' );
		$I->seeQMMenu();
		$I->seeInQMPanel( 'Hooks & Actions', '__invoke()' );
		$I->seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	}
}
