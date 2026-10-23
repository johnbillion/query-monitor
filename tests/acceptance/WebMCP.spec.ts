import { test as base, expect } from './utils/test-setup';

type WebMCPMode = 'document' | 'navigator' | 'none' | 'throw' | 'reject';

const test = base.extend<{ webMCP: WebMCPMode }>( {
	webMCP: [ 'document', { option: true } ],
} );

type Tool = {
	name: string;
	annotations: { readOnlyHint: boolean; untrustedContentHint: boolean; debugging: boolean };
	execute: (input: { offset?: number; limit?: number }) => Promise<{
		request: { url: string; started_at: string };
		available: { php_errors: boolean; doing_it_wrong: boolean };
		time_taken?: number | null;
		memory?: number | null;
		php_errors?: number | null;
		doing_it_wrong?: number | null;
		total?: number;
		next_offset?: number | null;
		errors?: { type: string; message: string; message_truncated: boolean; count?: number; suppressed?: boolean; trace: unknown }[];
	}>;
};

declare global {
	interface Window {
		qmWebMCPTools: Record<string, Tool>;
	}
}

test.describe( 'WebMCP', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { page, webMCP } ) => {
		// Install one mock per navigation so tests do not depend on init-script ordering.
		await page.addInitScript( ( mode ) => {
			window.qmWebMCPTools = {};
			const context = {
				registerTool: ( tool: Tool ) => {
					if ( mode === 'throw' ) {
						throw new Error( 'Registration failed' );
					}
					if ( mode === 'reject' ) {
						return Promise.reject( new Error( 'Registration failed' ) );
					}
					window.qmWebMCPTools[ tool.name ] = tool;
				},
			};
			Object.defineProperty( document, 'modelContext', {
				configurable: true,
				value: mode === 'none' || mode === 'navigator' ? undefined : context,
			} );
			Object.defineProperty( navigator, 'modelContext', {
				configurable: true,
				value: mode === 'navigator' ? context : undefined,
			} );
		}, webMCP );
	} );

	test( 'Only exposes read-only tools to users who can view Query Monitor', async ( { page, QueryMonitor } ) => {
		await page.goto( '/' );
		expect( await page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toEqual( [] );

		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await expect.poll( () => page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toEqual( [
			'qm_get_summary',
			'qm_get_errors',
		] );
		expect( await page.evaluate( () => Object.values( window.qmWebMCPTools ).map( ( tool ) => tool.annotations ) ) ).toEqual( [
			{ readOnlyHint: true, untrustedContentHint: true, debugging: true },
			{ readOnlyHint: true, untrustedContentHint: true, debugging: true },
		] );
	} );

	test( 'Does not expose tools to logged-in users without access', async ( { page, QueryMonitor } ) => {
		QueryMonitor.createUser( 'webmcp-subscriber', 'subscriber' );
		await QueryMonitor.loginViaPage( 'webmcp-subscriber', 'password' );
		await page.goto( '/' );
		await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
		await expect( page.locator( '#wp-admin-bar-query-monitor' ) ).not.toBeAttached();
		expect( await page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toEqual( [] );
	} );

	test( 'Respects the existing Query Monitor authentication cookie', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await page.locator( '#wp-admin-bar-query-monitor' ).click();
		await page.getByRole( 'button', { name: 'Settings', exact: true } ).click();
		await page.getByRole( 'button', { name: 'Set authentication cookie', exact: true } ).click();
		await expect( page.getByText( 'Authentication cookie is set', { exact: true } ) ).toBeVisible();
		const cookies = await page.context().cookies();
		await page.context().clearCookies();
		await page.context().addCookies( cookies.filter( ( cookie ) => cookie.name.startsWith( 'wp-query_monitor_' ) ) );
		await page.goto( '/' );
		await expect.poll( () => page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toHaveLength( 2 );
		await page.context().clearCookies();
		await page.reload();
		expect( await page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toEqual( [] );
	} );

	test( 'Returns PHP errors with resolved call stacks', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'warning' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const result = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( {} ) );
		expect( result.errors ).toEqual( expect.arrayContaining( [ expect.objectContaining( {
			type: 'php_errors',
			message: 'This is a test warning',
			count: 1,
			suppressed: false,
			trace: expect.objectContaining( {
				callsite: expect.objectContaining( { file: expect.stringContaining( 'acceptance.php' ), line: expect.any( Number ) } ),
				frames: expect.arrayContaining( [ expect.objectContaining( { id: expect.any( String ), file: expect.any( String ) } ) ] ),
			} ),
		} ) ] ) );
		expect( result.request.url ).not.toContain( '?' );
		expect( Number.isNaN( Date.parse( result.request.started_at ) ) ).toBe( false );
		expect( JSON.stringify( result ) ).not.toMatch( /"(?:args|auth_nonce|l10n)":/ );
	} );

	test( 'Preserves suppressed errors and repeated occurrence counts', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageThatTriggersSuppressedPhpError( 'warning' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const suppressed = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( {} ) );
		expect( suppressed.errors ).toEqual( expect.arrayContaining( [ expect.objectContaining( {
			message: 'This is a test suppressed warning',
			suppressed: true,
		} ) ] ) );

		await QueryMonitor.amOnAPageThatTriggersPhpError( 'buffet' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const errors = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( {} ) );
		expect( errors.errors ).toEqual( expect.arrayContaining( [ expect.objectContaining( {
			message: 'This is a repeated test warning',
			count: 2,
		} ) ] ) );
		const summary = await page.evaluate( () => window.qmWebMCPTools.qm_get_summary.execute( {} ) );
		// The fixture also triggers two suppressed errors, which remain part of the total.
		expect( summary.php_errors ).toBe( 6 );
	} );

	test( 'Omits embedded diagnostic dumps without changing the original messages', async ( { page, QueryMonitor } ) => {
		await page.addInitScript( () => {
			let value: unknown;
			Object.defineProperty( window, 'QueryMonitorData', {
				configurable: true,
				get: () => value,
				set: ( data: { data: { php_errors?: { data: { errors?: Record<string, { message: string }> } } } } ) => {
					for ( const error of Object.values( data.data.php_errors?.data.errors ?? {} ) ) {
						error.message = 'Error sending trace with data: {"metadata":{"cookies":"test-cookie-value"}}\nMore private details';
					}
					value = data;
				},
			} );
		} );
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'warning' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const result = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( {} ) );
		expect( result.errors ).toEqual( expect.arrayContaining( [ expect.objectContaining( {
			message: 'Error sending trace with data:',
			message_truncated: true,
		} ) ] ) );
		expect( JSON.stringify( result ) ).not.toContain( 'test-cookie-value' );
		expect( JSON.stringify( result ) ).not.toContain( 'More private details' );
		await QueryMonitor.seeInQMPanel( 'PHP Errors', 'test-cookie-value' );
	} );

	test( 'Returns Doing it Wrong diagnostics', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageThatIsDoingItWrong( 'function' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const result = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( {} ) );
		expect( result.errors ).toEqual( expect.arrayContaining( [ expect.objectContaining( {
			type: 'doing_it_wrong',
			message: expect.stringContaining( 'Function my_function' ),
			trace: expect.objectContaining( { component: expect.objectContaining( { type: expect.any( String ), name: expect.any( String ) } ) } ),
		} ) ] ) );
	} );

	test( 'Paginates entries without silently dropping errors', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'buffet' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const first = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( { limit: 1 } ) );
		const rest = await page.evaluate( () => window.qmWebMCPTools.qm_get_errors.execute( { offset: 1, limit: 100 } ) );
		expect( first.errors ).toHaveLength( 1 );
		expect( first.next_offset ).toBe( 1 );
		expect( rest.next_offset ).toBeNull();
		expect( first.errors!.length + rest.errors!.length ).toBe( first.total );
		expect( rest.errors ).not.toContainEqual( first.errors![ 0 ] );
	} );

	test( 'Rejects invalid pagination arguments', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		for ( const input of [ { limit: 0 }, { limit: 101 }, { offset: -1 }, { offset: 0.5 } ] ) {
			await expect( page.evaluate( ( input ) => window.qmWebMCPTools.qm_get_errors.execute( input ), input ) ).rejects.toThrow( 'Invalid offset or limit.' );
		}
	} );

	test( 'Does not change the collected data when tools are called', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await QueryMonitor.amOnAPageThatTriggersPhpError( 'warning' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_errors ) ).toBe( true );
		const unchanged = await page.evaluate( async () => {
			const original = JSON.stringify( Reflect.get( window, 'QueryMonitorData' ) );
			await window.qmWebMCPTools.qm_get_summary.execute( {} );
			await window.qmWebMCPTools.qm_get_errors.execute( {} );
			return original === JSON.stringify( Reflect.get( window, 'QueryMonitorData' ) );
		} );
		expect( unchanged ).toBe( true );
	} );

	test.describe( 'Unsupported browser', () => {
		test.use( { webMCP: 'none' } );

		test( 'Works normally without WebMCP', async ( { page, QueryMonitor } ) => {
			await QueryMonitor.loginViaPage( 'admin', 'password' );
			await QueryMonitor.amOnAPageThatTriggersPhpError( 'warning' );
			await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test warning' );
			expect( await page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toEqual( [] );
		} );
	} );

	test( 'Marks unavailable collectors instead of reporting zero errors', async ( { page, QueryMonitor } ) => {
		await page.addInitScript( () => {
			let value: unknown;
			Object.defineProperty( window, 'QueryMonitorData', {
				configurable: true,
				get: () => value,
				set: ( data: { data: Record<string, unknown> } ) => {
					delete data.data.php_errors;
					delete data.data.doing_it_wrong;
					value = data;
				},
			} );
		} );
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_summary ) ).toBe( true );
		const summary = await page.evaluate( () => window.qmWebMCPTools.qm_get_summary.execute( {} ) );
		expect( summary.available ).toEqual( { php_errors: false, doing_it_wrong: false } );
		expect( summary.php_errors ).toBeNull();
		expect( summary.doing_it_wrong ).toBeNull();
	} );

	test( 'Reports zero errors when the collectors are available but empty', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
		await expect.poll( () => page.evaluate( () => !!window.qmWebMCPTools.qm_get_summary ) ).toBe( true );
		const summary = await page.evaluate( () => window.qmWebMCPTools.qm_get_summary.execute( {} ) );
		expect( summary.available ).toEqual( { php_errors: true, doing_it_wrong: true } );
		expect( summary.time_taken ).toEqual( expect.any( Number ) );
		expect( summary.memory ).toEqual( expect.any( Number ) );
		expect( summary.php_errors ).toBe( 0 );
		expect( summary ).toHaveProperty( 'doing_it_wrong', 0 );
	} );

	test.describe( 'Earlier API location', () => {
		test.use( { webMCP: 'navigator' } );

		test( 'Supports navigator.modelContext', async ( { page, QueryMonitor } ) => {
			await QueryMonitor.loginViaPage( 'admin', 'password' );
			await expect.poll( () => page.evaluate( () => Object.keys( window.qmWebMCPTools ) ) ).toHaveLength( 2 );
		} );
	} );

	for ( const mode of [ 'throw', 'reject' ] as const ) {
		test.describe( `Registration failure: ${mode}`, () => {
			test.use( { webMCP: mode } );

			test( 'Does not prevent the panel from loading', async ( { page, QueryMonitor } ) => {
				const errors: string[] = [];
				page.on( 'pageerror', ( error ) => errors.push( error.message ) );
				await QueryMonitor.loginViaPage( 'admin', 'password' );
				await QueryMonitor.amOnAPageThatTriggersPhpError( 'warning' );
				await QueryMonitor.seeInQMPanel( 'PHP Errors', 'This is a test warning' );
				expect( errors ).toEqual( [] );
			} );
		} );
	}
} );
