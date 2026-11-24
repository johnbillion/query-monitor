import { test, expect } from './utils/test-setup';

test.describe( 'Enqueued Scripts', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		QueryMonitor.loginOnNextVisit( 'admin', 'password' );
	} );

	test( 'Protocol-relative source should be displayed as a path', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageWithEnqueuedScripts( 'protocol-relative' );

		// The source should display the path only, not the full protocol-relative URL.
		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Handle': 'qm-test-protocol-relative',
			'Source': 'qm-test-protocol-relative.js',
		} );

		// No source should be displayed as a protocol-relative or absolute URL.
		await QueryMonitor.dontSeeColumnValueInQMPanel( 'Scripts', 'Source', '//' );
	} );

	test( 'A script with no source should display an empty source', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageWithEnqueuedScripts( 'no-src' );

		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Handle': 'qm-test-no-src',
		} );

		// The source should be empty, not a bogus path such as "wp-admin/null".
		await QueryMonitor.dontSeeColumnValueInQMPanel( 'Scripts', 'Source', 'null' );
	} );

	test( 'Missing dependencies should be flagged', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageWithEnqueuedScripts( 'missing-dependency' );

		// The toolbar menu shows a warning indicator.
		await QueryMonitor.seeQMMenuWithWarning();

		// The Scripts panel menu entry shows a warning count bubble.
		await QueryMonitor.seeWarningBubbleInQMPanelMenu( 'Scripts' );

		// The asset's row is highlighted as a warning.
		await QueryMonitor.openQMPanel( 'Scripts' );

		const warningRow = QueryMonitor.getVisiblePanel().locator( 'tr.qm-warn' ).filter( {
			hasText: 'qm-test-missing-dep',
		} );

		await expect( warningRow ).toBeVisible();
	} );

	test( 'Asset transfer size should be displayed', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageWithEnqueuedScripts( 'with-size' );

		// The Size column shows the transferred size in kB for a script the browser loaded.
		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Handle': 'qm-test-with-size',
			'Size': 'kB',
		} );

		// The panel footer shows a total transfer size in kB.
		await expect( QueryMonitor.getVisiblePanel().locator( 'tfoot' ) ).toContainText( 'kB' );
	} );
} );
