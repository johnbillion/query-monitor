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
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test warning' );
	} );

	test( 'Notice should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'notice' );
		await QueryMonitor.seeQMMenuWithNotice();
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
} );
