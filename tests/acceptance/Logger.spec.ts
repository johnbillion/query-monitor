import { test, expect } from './utils/test-setup';

test.describe( 'Log messages', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		globalUtils.installWordPress();
	} );

	test( 'a word can be selected without selecting the whole message', async ( {
		page,
		QueryMonitor,
	} ) => {
		await QueryMonitor.loginViaPage( 'admin', 'password', '/?_qm_acceptance_group=logger&_qm_acceptance_test=selection' );
		await QueryMonitor.openQMPanel( 'Logs' );

		const message = page.locator( '#qm-logger tr pre' ).filter( {
			hasText: 'Select only this word from a longer log message.',
		} );
		await expect( message ).toBeVisible();
		await message.scrollIntoViewIfNeeded();
		const point = await message.evaluate( ( element ) => {
			const text = element.firstChild!;
			const range = document.createRange();
			range.setStart( text, 2 );
			range.setEnd( text, 3 );
			const rect = range.getBoundingClientRect();
			return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 };
		} );
		await page.mouse.dblclick( point.x, point.y );
		// Some platforms include the trailing space when double-clicking a word.
		await expect.poll( () => page.evaluate( () => window.getSelection()?.toString().trim() ) ).toBe( 'Select' );
	} );
} );
