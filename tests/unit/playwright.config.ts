import { defineConfig } from '@playwright/test';

/**
 * Config for the unit tests. These are pure Node tests that don't use a
 * browser, so unlike the acceptance tests they don't need the Docker
 * containers or a WP_BASE_URL.
 */
export default defineConfig( {
	testDir: '.',
	outputDir: '../test-results',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	reporter: 'list',
} );
