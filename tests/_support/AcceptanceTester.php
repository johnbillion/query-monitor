<?php declare(strict_types = 1);
/**
 * Acceptance testing actor.
 */

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 */
class AcceptanceTester extends \Codeception\Actor {
	use _generated\AcceptanceTesterActions;

	public function amOnAPageThatIsDoingItWrong( string $test ): void {
		$this->amOnPage( "/?_qm_acceptance_group=doing_it_wrong&_qm_acceptance_test={$test}" );
	}

	public function amOnAPageThatTriggersPhpError( string $test ): void {
		$this->amOnPage( "/?_qm_acceptance_group=php_errors&_qm_acceptance_test={$test}" );
	}

	public function amOnAPageThatTriggersSuppressedPhpError( string $test ): void {
		$this->amOnPage( "/?_qm_acceptance_group=php_errors&_qm_acceptance_test=suppressed-{$test}" );
	}

	public function amOnAPageThatMakesGuzzleRequest( string $test ): void {
		$this->amOnPage( "/?_qm_acceptance_group=guzzle_requests&_qm_acceptance_test={$test}" );
	}

	public function amOnAPageThatTriggersCallbackType( string $test ): void {
		$this->amOnPage( "/?_qm_acceptance_group=callback_types&_qm_acceptance_test={$test}" );
	}

	public function amOnAPageWithEnqueuedScripts( string $test ): void {
		$this->amOnPage( "/?_qm_acceptance_group=enqueued_scripts&_qm_acceptance_test={$test}" );
	}

	public function seeQMMenuWithWarning(): void {
		$this->seeElement( '#wp-admin-bar-query-monitor.qm-warning' );
	}

	public function seeQMMenuWithNotice(): void {
		$this->seeElement( '#wp-admin-bar-query-monitor.qm-notice' );
	}

	public function seeQMMenu(): void {
		$this->seeElement( '#wp-admin-bar-query-monitor' );
		$this->dontSeeElement( '#wp-admin-bar-query-monitor.qm-warning' );
		$this->dontSeeElement( '#wp-admin-bar-query-monitor.qm-notice' );
	}

	public function openQMPanel( string $panel ): void {
		$this->click( '#wp-admin-bar-query-monitor' );
		// Find a button where the value starts with the text
		$buttonXPath = sprintf(
			'//*[@id="qm-panel-menu"]//button[starts-with(normalize-space(text()), "%s")]',
			$panel
		);
		$this->click( $buttonXPath );
	}

	public function seeInQMPanel( string $panel, string $text ): void {
		$this->openQMPanel( $panel );
		$this->see( $text, '.qm-panel-show' );
	}

	/**
	 * Verifies that a table within the visible QM panel contains a row with the given cell values.
	 *
	 * Example usage:
	 *
	 * ```php
	 * $I->seeTableRowInQMPanel( 'PHP Errors', [
	 *     'Type' => 'Warning',
	 *     'Message' => 'Undefined variable: foo',
	 *     'Component' => 'Plugin: My Plugin'
	 * ] );
	 * ```
	 *
	 * @param string $panel The panel to open
	 * @param array<string, string> $row Associative array where keys are column headers and values are expected cell content
	 */
	public function seeTableRowInQMPanel( string $panel, array $row ): void {
		$this->openQMPanel( $panel );

		// Build XPath conditions for each cell
		$conditions = [];
		foreach ( $row as $header => $expectedValue ) {
			// Find the th element with this text or a label with this text
			$headerXPath = sprintf(
				'//div[contains(@class, "qm-panel-show")]//table//thead//th[normalize-space(text())="%s" or .//label[normalize-space(text())="%s"]]',
				$header,
				$header
			);
			$this->seeElement( $headerXPath );

			// Calculate the column index (1-based for XPath)
			$columnIndex = $this->executeJS(
				sprintf(
					'var th = document.evaluate(\'%s\', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; return th ? Array.from(th.parentNode.children).indexOf(th) + 1 : 0;',
					$headerXPath
				)
			);

			$conditions[] = sprintf(
				'td[%d][normalize-space(text())="%s"]',
				$columnIndex,
				$expectedValue
			);
		}

		// Find a row that matches all conditions
		$rowXPath = sprintf(
			'//div[contains(@class, "qm-panel-show")]//table//tbody//tr[%s]',
			implode( ' and ', $conditions )
		);

		try {
			$this->seeElement( $rowXPath );
		} catch ( \Exception $e ) {
			$expected = [];
			foreach ( $row as $header => $value ) {
				$expected[] = sprintf( '"%s" in column "%s"', $value, $header );
			}
			throw new \PHPUnit\Framework\AssertionFailedError(
				sprintf(
					'Failed to find table row with %s.',
					implode( ', ', $expected )
				)
			);
		}
	}
}
