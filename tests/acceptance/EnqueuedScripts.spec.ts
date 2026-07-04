import { test, expect } from './utils/test-setup';

test.describe( 'Enqueued Scripts', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Footer-only scripts should be detected', async ( { QueryMonitor, globalUtils } ) => {
		// Skip on older WordPress versions which enqueue scripts in the header by default
		if ( ! globalUtils.isWordPressVersionAtLeast( 6.7 ) ) {
			test.skip();
			return;
		}

		await QueryMonitor.amOnAPageWithEnqueuedScripts( 'footer-only' );

		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Position': 'Footer',
			'Handle': 'qm-test-footer',
		} );

		await QueryMonitor.dontSeeColumnValueInQMPanel( 'Scripts', 'Position', 'Header' );
	} );

	test( 'Script module dependencies should be handled', async ( { page, QueryMonitor, globalUtils } ) => {
		// Skip if WordPress doesn't support script modules (< 6.5)
		if ( ! globalUtils.isWordPressVersionAtLeast( 6.5 ) ) {
			test.skip();
			return;
		}

		await QueryMonitor.amOnAPageWithEnqueuedScripts( 'script-modules' );

		// Skip if we see "Uncaught DomainException" as it indicates an unsupported WP version
		const pageContent = await page.content();
		if ( pageContent.includes( 'Uncaught DomainException' ) ) {
			test.skip();
			return;
		}

		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Position': 'Module',
			'Handle': 'qm-test-top',
			'Dependencies': 'qm-test-middle',
			'Dependents': '',
		} );
		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Position': 'Module',
			'Handle': 'qm-test-middle',
			'Dependencies': 'qm-test-bottom',
			'Dependents': 'qm-test-top',
		} );
		await QueryMonitor.seeTableRowInQMPanel( 'Scripts', {
			'Position': 'Module',
			'Handle': 'qm-test-bottom',
			'Dependencies': '',
			'Dependents': 'qm-test-middle',
		} );
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

		// The asset is listed with its missing dependency flagged.
		await QueryMonitor.openQMPanel( 'Scripts' );

		const warningRow = QueryMonitor.getVisiblePanel().locator( 'tr.qm-warn' ).filter( {
			hasText: 'qm-test-missing-dep',
		} );

		await expect( warningRow ).toBeVisible();
		await expect( warningRow ).toContainText( 'qm-nonexistent-dependency' );
	} );
} );
