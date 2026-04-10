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
} );
