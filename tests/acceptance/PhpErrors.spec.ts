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
		await QueryMonitor.seeQMSubMenuItemHighlighted( 'PHP Errors', 'qm-warning' );
		await QueryMonitor.seeQMSubMenuItemWithText( 'qm-warning', 'PHP Errors (1 Warning)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test warning' );
	} );

	test( 'Notice should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'notice' );
		await QueryMonitor.seeQMMenuWithNotice();
		await QueryMonitor.seeQMSubMenuItemHighlighted( 'PHP Errors', 'qm-notice' );
		await QueryMonitor.seeQMSubMenuItemWithText( 'qm-notice', 'PHP Errors (1 Notice)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test notice' );
	} );

	test( 'Suppressed warning should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersSuppressedPhpError( 'warning' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'warning (suppressed)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test suppressed warning' );
	} );

	test( 'Suppressed notice should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersSuppressedPhpError( 'notice' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'notice (suppressed)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test suppressed notice' );
	} );

	test( 'Assortment of errors should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'buffet' );
		await QueryMonitor.seeQMMenuWithWarning();
		await QueryMonitor.seeQMSubMenuItemHighlighted( 'PHP Errors', 'qm-warning' );
		await QueryMonitor.seeQMSubMenuItemWithText( 'qm-notice', 'PHP Errors (1 Warning, 1 Notice)' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test warning' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test notice' );
	} );
} );
