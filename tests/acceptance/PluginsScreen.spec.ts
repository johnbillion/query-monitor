import { test, expect } from './utils/test-setup';

test.describe( 'Plugins Screen', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'The Settings action link should open the settings panel', async ( {
		page,
		QueryMonitor,
	} ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password', '/wp-admin/plugins.php' );

		await expect( page.locator( '#query-monitor-main' ) ).not.toBeVisible();

		await page.locator( 'tr[data-plugin="query-monitor/query-monitor.php"] .row-actions .settings a' ).click();

		await expect( page.locator( '#query-monitor-main #qm-settings' ) ).toBeVisible();
		expect( new URL( page.url() ).hash ).toBe( '' );
	} );
} );
