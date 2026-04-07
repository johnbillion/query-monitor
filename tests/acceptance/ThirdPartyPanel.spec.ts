import { test, expect } from './utils/test-setup';

test.describe( 'Third-Party Panel Menus', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageWithThirdPartyPanel();
	} );

	test( 'Third-party top-level menu appears in the panel menu', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.seeInQMPanel( 'Test Third Party', 'Test third-party parent panel content.' );
	} );

	test( 'Third-party child menu appears as a sub-menu item', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.openQMPanel( 'Test Third Party' );

		// The child menu item should be visible as a nested item under the parent
		const childButton = page.locator( '#qm-panel-menu li li button' ).filter( {
			hasText: 'Test Child Panel',
		} );
		await expect( childButton ).toBeVisible();
	} );

	test( 'Third-party child panel content is accessible', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.seeInQMPanel( 'Test Child Panel', 'Test third-party child panel content.' );
	} );
} );
