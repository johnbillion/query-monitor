import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.WP_BASE_URL;
if ( ! baseURL ) {
	throw new Error( 'WP_BASE_URL environment variable is required. Run tests via "composer test:acceptance".' );
}

export default defineConfig({
	testDir: '.',
	outputDir: '../_output',
	fullyParallel: false,
	forbidOnly: !!process.env.CI,
	workers: 1,
	reporter: 'list',
	use: {
		baseURL,
		...devices['Desktop Chrome'],
		channel: process.env.CI ? 'chrome' : undefined,
		viewport: { width: 1440, height: 900 },
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: process.env.CI ? 'off' : 'retain-on-failure',
	},

	webServer: undefined,
});
