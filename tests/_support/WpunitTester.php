<?php
/**
 * Integration testing actor.
 */

/**
 * Inherited Methods
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
class WpunitTester extends \Codeception\Actor {
	use _generated\WpunitTesterActions;

	/**
	 * Define custom actions here
	 */
}
