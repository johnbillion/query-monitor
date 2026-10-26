import { test, expect } from './utils/test-setup';

test.describe( 'Third-Party Panel Menus', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageWithThirdPartyPanel();
	} );

	test( 'Third-party top-level menu appears and its panel content is accessible', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.openQMPanel( 'Test Third Party' );
		await expect( page.locator( '#qm-panels' ) ).toContainText( 'Test third-party parent panel content.' );
	} );

	test( 'Third-party child sub-menu appears and its panel content is accessible', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.openQMPanel( 'Test Third Party' );

		// The child menu item should be visible as a nested item under the parent
		const childButton = page.locator( '#qm-panel-menu li li button' ).filter( {
			hasText: 'Test Child Panel',
		} );
		await expect( childButton ).toBeVisible();

		// Click the child and verify its panel content
		await childButton.click();
		await expect( page.locator( '#qm-panels' ) ).toContainText( 'Test third-party child panel content.' );
	} );
} );
