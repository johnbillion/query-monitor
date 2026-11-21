import { test, expect } from './utils/test-setup';
import * as fs from 'fs';

const dropin = 'vendor/wordpress/wordpress/wp-content/db.php';

// existsSync() follows the symlink, and its target path only exists inside the container.
const dropinExists = () => fs.lstatSync( dropin, { throwIfNoEntry: false } ) !== undefined;

test.describe( 'Deactivation', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.afterAll( () => {
		fs.rmSync( dropin, { force: true } );
	} );

	test( 'The db.php drop-in should be deleted when deactivated with WP-CLI', async ( { globalUtils } ) => {
		expect( dropinExists() ).toBe( true );

		globalUtils.runWPCLICommand( 'plugin deactivate query-monitor' );

		expect( dropinExists() ).toBe( false );
	} );

	test( 'A db.php drop-in that does not belong to Query Monitor should not be deleted', async ( { globalUtils } ) => {
		fs.rmSync( dropin, { force: true } );
		fs.writeFileSync( dropin, '<?php // Another drop-in' );

		globalUtils.runWPCLICommand( 'plugin activate query-monitor' );
		globalUtils.runWPCLICommand( 'plugin deactivate query-monitor' );

		expect( dropinExists() ).toBe( true );
	} );
} );
