import { test, expect } from './utils/test-setup';

const roles = [
	{ role: 'administrator', access: true },
	{ role: 'editor', access: false },
	{ role: 'author', access: false },
	{ role: 'contributor', access: false },
	{ role: 'subscriber', access: false },
];

test.describe( 'Basic User Access', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();

		// Create test users for each role
		for ( const { role } of roles ) {
			globalUtils.runWPCLICommand( `user create ${role} ${role}@example.com --role=${role} --user_pass=${role}` );
		}
	} );

	for ( const { role, access } of roles ) {
		test( `${role} should ${access ? '' : 'not '}have access to Query Monitor`, async ( {
			page,
			QueryMonitor,
		} ) => {
			await QueryMonitor.loginViaPage( role, role );
			await page.goto( '/' );

			// Verify we're logged in as the correct user
			await expect( page.locator( '#wp-admin-bar-my-account .display-name' ).first() ).toContainText( role );

			if ( access ) {
				// User should have access to QM
				await expect( page.locator( '#query-monitor-main' ) ).not.toBeVisible();
				await page.locator( '#wp-admin-bar-query-monitor' ).click();
				await expect( page.locator( '#query-monitor-main' ) ).toBeVisible();
			} else {
				// User should not have access to QM
				await expect( page.locator( '#wp-admin-bar-query-monitor' ) ).not.toBeVisible();
				await expect( page.locator( '#query-monitor-main' ) ).not.toBeAttached();
			}
		} );
	}
} );
