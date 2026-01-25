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
		$I->seeTableRowInQMPanel( 'Doing it Wrong (1)', [
			'Message' => 'Function my_function was called with an argument that is deprecated since version 2.0.0 with no alternative available.',
		] );
	}

	// public function DeprecatedClassShouldBeHandled( AcceptanceTester $I ): void {
	// 	$I->amOnAPageThatIsDoingItWrong( 'class' );
	// 	$I->seeTableRowInQMPanel( 'Doing it Wrong (1)', [
	// 		'Message' => 'Class My_Class is deprecated since version 2.0.0 with no alternative available.',
	// 	] );
	// }

	// public function DeprecatedConstructorShouldBeHandled( AcceptanceTester $I ): void {
	// 	$I->amOnAPageThatIsDoingItWrong( 'constructor' );
	// 	$I->seeTableRowInQMPanel( 'Doing it Wrong (1)', [
	// 		'Message' => 'The called constructor method for My_Class class is deprecated since version 2.0.0! Use __construct() instead.',
	// 	] );
	// }

	public function DeprecatedFileShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'file' );
		$I->seeTableRowInQMPanel( 'Doing it Wrong (1)', [
			'Message' => 'File my_file.php is deprecated since version 2.0.0 with no alternative available.',
		] );
	}

	public function DeprecatedFunctionShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'function' );
		$I->seeTableRowInQMPanel( 'Doing it Wrong (1)', [
			'Message' => 'Function my_function is deprecated since version 2.0.0 with no alternative available.',
		] );
	}

	public function DeprecatedHookShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageThatIsDoingItWrong( 'hook' );
		$I->seeTableRowInQMPanel( 'Doing it Wrong (1)', [
			'Message' => 'Hook my_hook is deprecated since version 2.0.0 with no alternative available.',
		] );
	}
}
