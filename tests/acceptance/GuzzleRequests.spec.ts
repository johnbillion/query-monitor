import { test } from './utils/test-setup';

test.describe( 'Guzzle HTTP Requests', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Successful Guzzle request should be logged', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatMakesGuzzleRequest( 'successful_request' );
		await QueryMonitor.seeQMMenu();
		await QueryMonitor.seeInQMPanel( 'HTTP API Calls (1)', 'https://example.org/json' );
	} );

	test( 'Error Guzzle request should be logged', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatMakesGuzzleRequest( 'error_request' );
		await QueryMonitor.seeQMMenuWithWarning();
		await QueryMonitor.seeInQMPanel( 'HTTP API Calls (1)', 'https://example.org/status/404' );
	} );
} );
