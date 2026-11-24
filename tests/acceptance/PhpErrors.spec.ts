import { test } from './utils/test-setup';

test.describe( 'PHP Errors', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		QueryMonitor.loginOnNextVisit( 'admin', 'password' );
	} );

	test( 'Suppressed warning should not raise the menu above a notice', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersSuppressedPhpError( 'warning' );
		await QueryMonitor.seeQMMenuWithNotice();
		await QueryMonitor.seeQMSubMenuItemWithBadge( 'php_errors', 'notice', '1' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'Warning (suppressed)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test suppressed warning' );
	} );

	test( 'Assortment of errors should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'buffet' );
		await QueryMonitor.seeQMMenuWithWarning();
		await QueryMonitor.seeQMSubMenuItemWithText( 'php_errors', 'PHP Errors' );
		await QueryMonitor.seeQMSubMenuItemWithBadge( 'php_errors', 'warning', '3' );
		await QueryMonitor.seeQMSubMenuItemWithBadge( 'php_errors', 'notice', '1' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a single test warning' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a repeated test warning' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test notice' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'Notice (suppressed)' );
	} );
} );
