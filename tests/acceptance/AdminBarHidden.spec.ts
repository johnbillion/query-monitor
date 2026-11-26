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

} );

test.describe( 'Admin Toolbar Hidden By User', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Launcher can be turned off in the settings', async ( { page } ) => {
		await page.goto( '/wp-admin/profile.php' );
		await page.locator( '#admin_bar_front' ).uncheck();
		await page.locator( '#submit' ).click();
		await expect( page.locator( '#message.updated' ) ).toBeVisible();

		await page.goto( '/' );

		const launcher = page.locator( '.qm-launcher' );
		const checkbox = page.locator( 'input[name="qm-launcher"]' );

		await expect( page.locator( '#wpadminbar' ) ).not.toBeAttached();
		await launcher.click();
		await page.locator( '.qm-button-container-settings' ).click();
		await expect( checkbox ).toBeChecked();

		await checkbox.uncheck();
		await page.locator( '.qm-button-container-close' ).click();

		await expect( page.locator( '#query-monitor-main' ) ).not.toBeAttached();
		await expect( launcher ).not.toBeAttached();

		await page.reload();
		// Wait for QM to render before checking that none of its UI is shown.
		await expect( page.locator( '#query-monitor-container link[rel="stylesheet"]' ) ).toBeAttached();
		await expect( page.locator( '#wpadminbar' ) ).not.toBeAttached();
		await expect( page.locator( '#query-monitor-main' ) ).not.toBeAttached();
		await expect( launcher ).not.toBeAttached();
	} );
} );

test.describe( 'Admin Toolbar Visible', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Launcher is not shown when the admin toolbar is visible', async ( { page } ) => {
		await page.goto( '/' );

		await expect( page.locator( '#wp-admin-bar-query-monitor' ) ).toBeVisible();
		await expect( page.locator( '.qm-launcher' ) ).not.toBeAttached();
	} );
} );
