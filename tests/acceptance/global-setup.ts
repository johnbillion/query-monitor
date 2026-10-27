import { chromium, FullConfig } from '@playwright/test';
import { QueryMonitorGlobalUtils } from './utils/query-monitor-global-utils';

export default async function globalSetup( config: FullConfig ): Promise<void> {
	const { baseURL, channel, userAgent } = config.projects[0].use;

	const globalUtils = new QueryMonitorGlobalUtils( {
		baseURL: baseURL!,
		pluginSlug: 'query-monitor',
	} );

	globalUtils.installWordPressFromScratch();

	// The first load of the dashboard makes blocking update and browser checks to api.wordpress.org. Loading it
	// here caches their results in the snapshot. The browser check is cached per user agent, so this has to use
	// the same one as the tests.
	const browser = await chromium.launch( { channel } );
	const page = await browser.newPage( { baseURL, userAgent } );

	await page.goto( '/wp-login.php' );
	await page.fill( 'input[name="log"]', 'admin' );
	await page.fill( 'input[name="pwd"]', 'password' );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /\/wp-admin\// );
	await page.locator( '#wpadminbar' ).waitFor();

	await browser.close();

	globalUtils.saveSnapshot();
}
