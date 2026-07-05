import { test } from './utils/test-setup';

test.describe( 'PHP Errors', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Warning should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'warning' );
		await QueryMonitor.seeQMMenuWithWarning();
		await QueryMonitor.seeQMSubMenuItemWithText( 'php_errors', 'PHP Errors' );
		await QueryMonitor.seeQMSubMenuItemWithBadge( 'php_errors', 'warning', '1' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test warning' );
	} );

	test( 'Notice should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'notice' );
		await QueryMonitor.seeQMMenuWithNotice();
		await QueryMonitor.seeQMSubMenuItemWithText( 'php_errors', 'PHP Errors' );
		await QueryMonitor.seeQMSubMenuItemWithBadge( 'php_errors', 'notice', '1' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test notice' );
	} );

	test( 'Suppressed warning should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersSuppressedPhpError( 'warning' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'Warning (suppressed)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test suppressed warning' );
	} );

	test( 'Suppressed notice should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersSuppressedPhpError( 'notice' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'Notice (suppressed)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test suppressed notice' );
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
	} );
} );
