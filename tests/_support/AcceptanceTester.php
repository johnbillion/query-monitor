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
		$this->click( $panel, '#qm-panel-menu' );
	}

	public function seeInQMPanel( string $panel, string $text ): void {
		$this->openQMPanel( $panel );
		$this->see( $text, '.qm-panel-show' );
	}
}
