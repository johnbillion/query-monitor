<?php declare(strict_types = 1);
/**
 * Acceptance tests for enqueued scripts.
 */

class EnqueuedScriptsCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function ScriptModuleDependenciesShouldBeHandled( AcceptanceTester $I ): void {
		$I->amOnAPageWithEnqueuedScripts( 'script-modules' );

		// Skip if we see "Uncaught DomainException" as it indicates an unsupported WP version
		try {
			$I->dontSee( 'Uncaught DomainException' );
		} catch ( Exception $e ) {
			return;
		}

		$I->seeTableRowInQMPanel( 'Scripts', [
			'Position' => 'Module',
			'Handle'   => 'qm-test-top',
			'Dependencies' => 'qm-test-middle',
			'Dependents' => '',
		] );
		$I->seeTableRowInQMPanel( 'Scripts', [
			'Position' => 'Module',
			'Handle'   => 'qm-test-middle',
			'Dependencies' => 'qm-test-bottom',
			'Dependents' => 'qm-test-top',
		] );
		$I->seeTableRowInQMPanel( 'Scripts', [
			'Position' => 'Module',
			'Handle'   => 'qm-test-bottom',
			'Dependencies' => '',
			'Dependents' => 'qm-test-middle',
		] );
	}
}
