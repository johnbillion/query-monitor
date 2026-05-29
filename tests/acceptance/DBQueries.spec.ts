import { test } from './utils/test-setup';

test.describe( 'Database Queries', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Non-UTF8 query should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersDBQuery( 'non_utf8' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Database Queries', 'qm_binary_ip_test' );
	} );
} );
