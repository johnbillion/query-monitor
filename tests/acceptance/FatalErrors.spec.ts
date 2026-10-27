import { test, expect } from './utils/test-setup';
import type { QueryMonitorGlobalUtils } from './utils/query-monitor-global-utils';

const logFile = 'WP_CONTENT_DIR . "/qm-acceptance-fatal.log"';
const message = 'This is a test fatal error';
const coreMessage = 'There has been a critical error on this website.';

function clearErrorLog( globalUtils: QueryMonitorGlobalUtils ) {
	globalUtils.runWPCLICommand( `eval '@unlink( ${logFile} );'` );
}

function readErrorLog( globalUtils: QueryMonitorGlobalUtils ): string {
	return globalUtils.runWPCLICommand( `eval 'echo (string) @file_get_contents( ${logFile} );'` );
}

// QM must pass fatal errors on to PHP once it has displayed them, otherwise they don't get
// logged and core's fatal error handler (which handles recovery mode) never sees them.
test.describe( 'Fatal Errors', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { globalUtils } ) => {
		clearErrorLog( globalUtils );
	} );

	test.describe( 'Logged in', () => {
		test( 'Fatal error on the front end should be displayed and logged', async ( { page, QueryMonitor, globalUtils } ) => {
			QueryMonitor.loginOnNextVisit( 'admin', 'password' );
			await QueryMonitor.amOnAPageThatTriggersFatalError( 'uncaught' );
			await expect( page.locator( '#qm-fatal' ) ).toContainText( message );
			expect( readErrorLog( globalUtils ) ).toContain( `Uncaught Error: ${message}` );
		} );

		test( 'Fatal error in the admin area should be displayed, logged, and handled by core', async ( { page, QueryMonitor, globalUtils } ) => {
			QueryMonitor.loginOnNextVisit( 'admin', 'password' );
			await QueryMonitor.amOnAPageThatTriggersFatalError( 'uncaught', '/wp-admin/' );
			await expect( page.locator( '#qm-fatal' ) ).toContainText( message );
			expect( readErrorLog( globalUtils ) ).toContain( `Uncaught Error: ${message}` );

			// Core's output is present but hidden by QM.
			await expect( page.locator( '.wp-die-message' ) ).toContainText( coreMessage );
			await expect( page.locator( '.wp-die-message' ) ).toBeHidden();
		} );

		test( 'Fatal error during an Ajax request should be output as JSON and logged', async ( { page, QueryMonitor, globalUtils } ) => {
			await QueryMonitor.loginViaPage( 'admin', 'password' );
			const response = await page.request.get( '/wp-admin/admin-ajax.php?action=qm_test&_qm_acceptance_group=fatal_errors&_qm_acceptance_test=uncaught' );
			expect( response.status() ).toBe( 500 );
			expect( await response.json() ).toMatchObject( { code: 'qm_fatal' } );
			expect( readErrorLog( globalUtils ) ).toContain( `Uncaught Error: ${message}` );
		} );

		test( 'Fatal error during a REST API request should be output as JSON and logged', async ( { page, QueryMonitor, globalUtils } ) => {
			await QueryMonitor.loginViaPage( 'admin', 'password' );
			const response = await page.request.get( '/wp-json/?_qm_acceptance_group=fatal_errors&_qm_acceptance_test=uncaught-rest' );
			expect( response.status() ).toBe( 500 );
			expect( await response.json() ).toMatchObject( { code: 'qm_fatal' } );
			expect( readErrorLog( globalUtils ) ).toContain( `Uncaught Error: ${message}` );
		} );
	} );

	test.describe( 'Logged out', () => {
		test( 'Fatal error should be logged and handled by core when display_errors is off', async ( { page, QueryMonitor, globalUtils } ) => {
			await QueryMonitor.amOnAPageThatTriggersFatalError( 'uncaught', '/', '0' );
			await expect( page.locator( '#qm-fatal' ) ).not.toBeAttached();
			await expect( page.locator( '.wp-die-message' ) ).toContainText( coreMessage );
			expect( readErrorLog( globalUtils ) ).toContain( `Uncaught Error: ${message}` );
		} );

		test( 'Fatal error should be displayed and logged when display_errors is on', async ( { page, QueryMonitor, globalUtils } ) => {
			await QueryMonitor.amOnAPageThatTriggersFatalError( 'uncaught', '/', '1' );
			await expect( page.locator( '#qm-fatal' ) ).toContainText( message );
			expect( readErrorLog( globalUtils ) ).toContain( `Uncaught Error: ${message}` );
		} );
	} );
} );
