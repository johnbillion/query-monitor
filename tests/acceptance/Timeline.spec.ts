import { expect } from '@playwright/test';
import { test } from './utils/test-setup';

test.describe( 'Timeline', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Selecting a query shows its details without leaving the timeline', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersDBQuery( 'duplicate' );
		await QueryMonitor.openQMPanel( 'Timeline' );

		const panel = QueryMonitor.getVisiblePanel();
		const details = panel.locator( '.timeline-detail' );

		await expect( details ).toHaveCount( 0 );

		await panel.locator( '.timeline-bar-label-text button' ).filter( { hasText: 'SELECT 123' } ).first().click();

		await expect( details ).toContainText( 'SELECT 123' );
		await expect( details ).toContainText( 'Started' );
		await expect( details ).toBeInViewport();
		await expect( page.locator( '#qm-timeline' ) ).toBeVisible();

		await details.getByRole( 'button', { name: 'View in Database Queries' } ).click();

		await expect( page.locator( '#qm-db_queries' ) ).toBeVisible();
	} );

	test( 'The details sidebar can be closed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatTriggersDBQuery( 'duplicate' );
		await QueryMonitor.openQMPanel( 'Timeline' );

		const panel = QueryMonitor.getVisiblePanel();
		const details = panel.locator( '.timeline-detail' );

		const label = panel.locator( '.timeline-bar-label-text button' ).filter( { hasText: 'SELECT 123' } ).first();

		await label.click();
		await expect( details ).toBeFocused();

		await details.getByRole( 'button', { name: 'Close' } ).click();
		await expect( details ).toHaveCount( 0 );
		await expect( label ).toBeFocused();
	} );
} );
