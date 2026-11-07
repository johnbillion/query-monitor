import { test, expect } from './utils/test-setup';

test.describe( 'Bundle globals', () => {

	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'Loading the panel does not define global formatting functions', async ( { page, QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password', '/wp-admin/' );
		await QueryMonitor.openQMPanel( 'Overview' );

		for ( const name of [ 'sprintf', 'vsprintf' ] ) {
			expect( await page.evaluate( ( key ) => typeof Reflect.get( window, key ), name ) ).toBe( 'undefined' );
		}
	} );

	test( 'Loading the panel preserves formatting functions defined by other scripts', async ( { page, QueryMonitor } ) => {
		await page.addInitScript( () => {
			for ( const name of [ 'sprintf', 'vsprintf' ] ) {
				Reflect.set( window, name, () => 'existing formatter' );
			}
		} );

		await QueryMonitor.loginViaPage( 'admin', 'password', '/wp-admin/' );
		await QueryMonitor.openQMPanel( 'Overview' );

		for ( const name of [ 'sprintf', 'vsprintf' ] ) {
			expect( await page.evaluate( ( key ) => Reflect.get( window, key )(), name ) ).toBe( 'existing formatter' );
		}
	} );
} );
