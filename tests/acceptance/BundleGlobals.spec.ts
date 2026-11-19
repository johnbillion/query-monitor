import { test, expect } from './utils/test-setup';

test.describe( 'Bundle globals', () => {

	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'Loading the panel does not define global formatting functions', async ( { page, QueryMonitor } ) => {
		// Older WordPress versions load their own global sprintf functions in wp-admin.
		await QueryMonitor.loginViaPage( 'admin', 'password', '/' );
		await QueryMonitor.openQMPanel( 'Overview' );

		expect( await page.evaluate( () => typeof Reflect.get( window, 'sprintf' ) ) ).toBe( 'undefined' );
		expect( await page.evaluate( () => typeof Reflect.get( window, 'vsprintf' ) ) ).toBe( 'undefined' );
	} );

	test( 'Loading the panel preserves formatting functions defined by other scripts', async ( { page, QueryMonitor } ) => {
		await page.addInitScript( () => {
			for ( const name of [ 'sprintf', 'vsprintf' ] ) {
				Reflect.set( window, name, () => 'existing formatter' );
			}
		} );

		await QueryMonitor.loginViaPage( 'admin', 'password', '/' );
		await QueryMonitor.openQMPanel( 'Overview' );

		expect( await page.evaluate( () => Reflect.get( window, 'sprintf' )() ) ).toBe( 'existing formatter' );
		expect( await page.evaluate( () => Reflect.get( window, 'vsprintf' )() ) ).toBe( 'existing formatter' );
	} );
} );
