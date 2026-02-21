import { test } from './utils/test-setup';

test.describe( 'Doing It Wrong', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Deprecated argument should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatIsDoingItWrong( 'argument' );
		await QueryMonitor.seeTableRowInQMPanel( 'Doing it Wrong (1)', {
			'Message': 'Function my_function was called with an argument that is deprecated since version 2.0.0 with no alternative available.',
		} );
	} );

	test( 'Deprecated file should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatIsDoingItWrong( 'file' );
		await QueryMonitor.seeTableRowInQMPanel( 'Doing it Wrong (1)', {
			'Message': 'File my_file.php is deprecated since version 2.0.0 with no alternative available.',
		} );
	} );

	test( 'Deprecated function should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatIsDoingItWrong( 'function' );
		await QueryMonitor.seeTableRowInQMPanel( 'Doing it Wrong (1)', {
			'Message': 'Function my_function is deprecated since version 2.0.0 with no alternative available.',
		} );
	} );

	test( 'Deprecated hook should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatIsDoingItWrong( 'hook' );
		await QueryMonitor.seeTableRowInQMPanel( 'Doing it Wrong (1)', {
			'Message': 'Hook my_hook is deprecated since version 2.0.0 with no alternative available.',
		} );
	} );
} );
