import { test, expect } from './utils/test-setup';

test.describe( 'Basic User Access', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'administrator should have access to Query Monitor', async ( {
		page,
		QueryMonitor,
	} ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password', '/' );

		await page.locator( '#wp-admin-bar-query-monitor' ).click();
		await expect( page.locator( '#query-monitor-main' ) ).toBeVisible();
	} );

	test( 'administrator should have access to Query Monitor on a small screen', async ( {
		page,
		QueryMonitor,
	} ) => {
		await page.setViewportSize( { width: 390, height: 844 } );
		await QueryMonitor.loginViaPage( 'admin', 'password', '/' );

		await page.locator( '#wp-admin-bar-query-monitor' ).click();
		await expect( page.locator( '#wp-admin-bar-query-monitor .ab-submenu' ) ).toBeVisible();
	} );
} );
