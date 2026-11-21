import { test, expect } from './utils/test-setup';

test.describe( 'Admin Toolbar Hidden', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();

		// Hide the admin toolbar on the front end for the admin user.
		globalUtils.runWPCLICommand( 'user meta update admin show_admin_bar_front false' );
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Panel can be opened and closed with the launcher', async ( { page } ) => {
		await page.goto( '/' );

		const launcher = page.locator( '.qm-launcher' );

		await expect( page.locator( '#wpadminbar' ) ).not.toBeAttached();
		await expect( page.locator( '#query-monitor-main' ) ).not.toBeAttached();
		await expect( launcher ).toBeVisible();

		await launcher.click();
		await expect( page.locator( '#query-monitor-main' ) ).toBeVisible();
		await expect( launcher ).not.toBeAttached();

		await page.locator( '.qm-button-container-close' ).click();
		await expect( page.locator( '#query-monitor-main' ) ).not.toBeAttached();
		await expect( launcher ).toBeVisible();
	} );

	test( 'Launcher is not shown when the admin toolbar is visible', async ( { page } ) => {
		await page.goto( '/wp-admin/' );

		await expect( page.locator( '#wp-admin-bar-query-monitor' ) ).toBeVisible();
		await expect( page.locator( '.qm-launcher' ) ).not.toBeAttached();
	} );
} );
