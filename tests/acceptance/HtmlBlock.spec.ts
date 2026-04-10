import { test, expect } from './utils/test-setup';
import { GlobalUtils } from './utils/global-utils';

test.describe( 'HTML Block', () => {
	let postUrl: string;

	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();

		// Create a post with a wp:html block containing a script tag.
		// The </script> closing tag within the block innerHTML ends up in
		// the QM JSON data. Without proper encoding this breaks out of the
		// inline script tag that delivers QueryMonitorData.
		const postId = GlobalUtils.runWPCLICommand(
			'post create --post_status=publish --post_title="HTML Block Test" --post_content="<!-- wp:html -->\n<script src=\"https://player.vimeo.com/api/player.js\"></script>\n<!-- /wp:html -->" --porcelain'
		);
		postUrl = GlobalUtils.runWPCLICommand( `post url ${postId.trim()}` );

		// Convert absolute URL to relative path
		const url = new URL( postUrl );
		postUrl = url.pathname;
	} );

	test.beforeEach( async ( { QueryMonitor } ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password' );
	} );

	test( 'Post with HTML block containing script tag should not break QM output', async ( { page } ) => {
		await page.goto( postUrl );

		// The QueryMonitorData variable should be a valid object.
		// If the </script> in the block innerHTML broke out of the
		// inline script tag, this variable will be undefined.
		const qmData = await page.evaluate( () => typeof window.QueryMonitorData );
		expect( qmData ).toBe( 'object' );
	} );
} );
