import * as fs from 'fs';
import { createRequire } from 'module';
import { test, expect } from './utils/test-setup';
import type { AxeResults, Result } from 'axe-core';

const require = createRequire( import.meta.url );

const axeSource = fs.readFileSync(
	require.resolve( 'axe-core/axe.min.js' ),
	'utf8',
);

/**
 * Injects axe-core into the page and runs it against the QM shadow DOM.
 */
async function runAxeInShadowDom( page: import( '@playwright/test' ).Page, selector: string ): Promise<AxeResults> {
	return await page.evaluate( async ( { axeSrc, sel } ) => {
		// Inject axe-core if not already present.
		if ( ! ( window as any ).axe ) {
			const script = document.createElement( 'script' );
			script.textContent = axeSrc;
			document.head.appendChild( script );
		}

		const shadow = document.getElementById( 'query-monitor-container' )?.shadowRoot;
		if ( ! shadow ) {
			throw new Error( 'QM shadow root not found' );
		}

		const target = shadow.querySelector( sel );
		if ( ! target ) {
			throw new Error( `Element "${ sel }" not found in shadow root` );
		}

		return ( window as any ).axe.run( target );
	}, { axeSrc: axeSource, sel: selector } );
}

function formatViolations( violations: Result[] ) {
	return violations.map( ( v ) => ( {
		id: v.id,
		impact: v.impact,
		description: v.description,
		nodes: v.nodes.map( ( n ) => n.html ).slice( 0, 3 ),
	} ) );
}

test.describe( 'Accessibility', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'Each panel should have no accessibility violations', async ( {
		page,
		QueryMonitor,
	} ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await page.goto( '/' );

		// Open QM
		await page.locator( '#wp-admin-bar-query-monitor' ).click();
		await expect( page.locator( '#query-monitor-main' ) ).toBeVisible();

		// Get all top-level nav button labels.
		const panels = await page.evaluate( () => {
			const shadow = document.getElementById( 'query-monitor-container' )?.shadowRoot;
			if ( ! shadow ) {
				return [];
			}

			const buttons = shadow.querySelectorAll( '#qm-panel-menu > ul > li > button[role="tab"]' );
			return Array.from( buttons ).map( ( b ) => {
				// Get only the direct text content, excluding badge spans.
				const clone = b.cloneNode( true ) as Element;
				clone.querySelectorAll( '.qm-menu-badge' ).forEach( ( badge ) => badge.remove() );
				return clone.textContent?.trim() ?? '';
			} ).filter( Boolean );
		} );

		expect( panels.length ).toBeGreaterThan( 0 );

		for ( const panel of panels ) {
			await QueryMonitor.openQMPanel( panel );

			const results = await runAxeInShadowDom( page, '#qm-panels' );
			const violations = formatViolations( results.violations );

			expect( violations, `Accessibility violations in "${ panel }":\n${ JSON.stringify( violations, null, 2 ) }` ).toEqual( [] );
		}
	} );
} );
