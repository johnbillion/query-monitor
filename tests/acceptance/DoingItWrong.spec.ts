import { test } from './utils/test-setup';

test.describe( 'Doing It Wrong', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		QueryMonitor.loginOnNextVisit( 'admin', 'password' );
	} );

	test( 'Deprecated function should be handled', async ( { QueryMonitor } ) => {
		await QueryMonitor.amOnAPageThatIsDoingItWrong( 'function' );
		await QueryMonitor.seeTableRowInQMPanel( 'Doing it Wrong', {
			'Message': 'Function my_function is deprecated since version 2.0.0 with no alternative available.',
		} );
	} );
} );
