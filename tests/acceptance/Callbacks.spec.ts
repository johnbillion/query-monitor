import { test } from './utils/test-setup';

test.describe( 'Callback Types in Hooks & Actions', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Function callback should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersCallbackType( 'function' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', '__return_true()' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	} );

	test( 'Method callback should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersCallbackType( 'method' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'test_method()' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	} );

	test( 'Static method callback should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersCallbackType( 'static_method' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'QM_Test_Static_Class::test_static_method()' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	} );

	test( 'Closure callback should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersCallbackType( 'closure' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'Closure on line' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'acceptance.php' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	} );

	test( 'Invokable callback should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersCallbackType( 'invokable' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', '__invoke()' );
		await QueryMonitor.seeInQMPanel( 'Hooks & Actions', 'qm_test_hook' );
	} );
} );
