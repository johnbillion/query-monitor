import { test } from './utils/test-setup';

test.describe( 'Callback Types in Hooks & Actions', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		QueryMonitor.loginOnNextVisit( 'admin', 'password' );
	} );

	test( 'Closure callback should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersCallbackType( 'closure' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'acceptance.php:' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	} );
} );
