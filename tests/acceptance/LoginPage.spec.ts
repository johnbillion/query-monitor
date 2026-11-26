import { test, expect } from './utils/test-setup';

test.describe( 'Login Page', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'Query Monitor is not shown on the login page when the auth cookie is invalid', async ( {
		page,
		context,
		QueryMonitor,
	} ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );

		// Remove the auth cookies but keep the logged_in cookie, which is what happens when
		// the auth cookie is lost or expires before the logged_in cookie.
		// See https://github.com/johnbillion/query-monitor/issues/555
		await context.clearCookies( { name: /^wordpress_(sec_)?[0-9a-f]{32}$/ } );

		await page.goto( '/wp-admin/' );

		await expect( page ).toHaveURL( /\/wp-login\.php\?.*reauth=1/ );
		await expect( page.locator( '#loginform' ) ).toBeVisible();
		await expect( page.locator( '#query-monitor-container' ) ).not.toBeAttached();
	} );
} );
