import { test } from '@playwright/test';
import { execSync } from 'child_process';

export class GlobalUtils {

	/**
	 * Run a WP-CLI command
	 */
	static runWPCLICommand( command: string ): string {
		const baseURL = test.info().project.use.baseURL;
		const fullCommand = `docker compose exec --user wp_php wpcli wp --url="${baseURL}" ${command}`;

		try {
			const stdout = execSync( fullCommand, { encoding: 'utf8', cwd: process.cwd() } );
			return stdout.trim();
		} catch ( error: any ) {
			throw new Error( `WP-CLI command failed: ${error.message}` );
		}
	}

	installWordPress() {
		// Install WordPress:
		GlobalUtils.runWPCLICommand( 'db reset --yes' );
		GlobalUtils.runWPCLICommand( 'core install --title="Query Monitor" --admin_user="admin" --admin_password="password" --admin_email="admin@example.com" --skip-email' );

		// Set a predictable permalink structure:
		GlobalUtils.runWPCLICommand( 'rewrite structure "/%postname%/"' );

		// Activate the plugin under test:
		GlobalUtils.runWPCLICommand( 'plugin activate query-monitor' );
	}

	/**
	 * Check if current WordPress version meets minimum requirement
	 */
	static isWordPressVersionAtLeast( minVersion: number ): boolean {
		const wpVersion = GlobalUtils.runWPCLICommand( 'core version' );
		// Extract major.minor version from WordPress version string
		// Examples: "6.2.1" -> "6.2", "6.9-alpha-60684" -> "6.9"
		const versionMatch = wpVersion.match( /^(\d+\.\d+)/ );
		if ( ! versionMatch ) {
			throw new Error( `Unable to parse WordPress version: ${wpVersion}` );
		}
		const currentVersion = parseFloat( versionMatch[1] );

		return currentVersion >= minVersion;
	}
}
