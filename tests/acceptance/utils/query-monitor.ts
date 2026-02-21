import { Page, Locator, expect } from '@playwright/test';
import { GlobalUtils } from '@johnbillion/plugin-infrastructure/acceptance';

// Simple admin utility interface to match what we need
interface Admin {
	visitAdminPage( path?: string, queryString?: string ): Promise<void>;
}

export class QueryMonitorUtils {
	private page: Page;
	private admin: Admin;
	private globalUtils: GlobalUtils;

	constructor( page: Page, admin: Admin, globalUtils: GlobalUtils ) {
		this.page = page;
		this.admin = admin;
		this.globalUtils = globalUtils;
	}

	/**
	 * Login via the wp-login.php page
	 */
	async loginViaPage( username: string, password: string ) {
		await this.page.goto( '/wp-login.php' );
		await this.page.fill( 'input[name="log"]', username );
		await this.page.fill( 'input[name="pwd"]', password );
		await this.page.locator( '#wp-submit' ).click();

		// @todo verify we're logged in (can't use HTTP status code as WP returns 200 even on failed login)
	}

	/**
	 * Check for admin success notice
	 */
	async seeAdminSuccessNotice( text: string ) {
		await expect( this.page.locator( '.notice-success' ) ).toContainText( text );
	}

	/**
	 * Check for admin warning notice
	 */
	async seeAdminWarningNotice( text: string ) {
		await expect( this.page.locator( '.notice-warning' ) ).toContainText( text );
	}

	/**
	 * Check for admin error notice
	 */
	async seeAdminErrorNotice( text: string ) {
		await expect( this.page.locator( '.notice-error' ) ).toContainText( text );
	}

	/**
	 * Check for admin info notice
	 */
	async seeAdminInfoNotice( text: string ) {
		await expect( this.page.locator( '.notice-info' ) ).toContainText( text );
	}

	/**
	 * Create a user with the specified username, role, and optional custom data
	 */
	createUser( username: string, role: string, customData: { email?: string; name?: string; locale?: string } = {} ) {
		const email = customData.email || `${username}@example.com`;
		const displayName = customData.name || username;

		this.globalUtils.runWPCLICommand( `user create ${username} ${email} --role=${role} --display_name="${displayName}" --user_pass=password` );

		// Set user locale if provided
		if ( customData.locale ) {
			this.globalUtils.runWPCLICommand( `user meta update ${username} locale ${customData.locale}` );
		}
	}

	/**
	 * Navigate to a page that triggers a "doing it wrong" scenario
	 */
	async amOnAPageThatIsDoingItWrong( test: string ) {
		await this.page.goto( `/?_qm_acceptance_group=doing_it_wrong&_qm_acceptance_test=${test}` );
	}

	/**
	 * Navigate to a page that triggers a PHP error
	 */
	async amOnAPageThatTriggersPhpError( test: string ) {
		await this.page.goto( `/?_qm_acceptance_group=php_errors&_qm_acceptance_test=${test}` );
	}

	/**
	 * Navigate to a page that triggers a suppressed PHP error
	 */
	async amOnAPageThatTriggersSuppressedPhpError( test: string ) {
		await this.page.goto( `/?_qm_acceptance_group=php_errors&_qm_acceptance_test=suppressed-${test}` );
	}

	/**
	 * Navigate to a page that makes a Guzzle HTTP request
	 */
	async amOnAPageThatMakesGuzzleRequest( test: string ) {
		await this.page.goto( `/?_qm_acceptance_group=guzzle_requests&_qm_acceptance_test=${test}` );
	}

	/**
	 * Navigate to a page that triggers a specific callback type
	 */
	async amOnAPageThatTriggersCallbackType( test: string ) {
		await this.page.goto( `/?_qm_acceptance_group=callback_types&_qm_acceptance_test=${test}` );
	}

	/**
	 * Navigate to a page with enqueued scripts
	 */
	async amOnAPageWithEnqueuedScripts( test: string ) {
		await this.page.goto( `/?_qm_acceptance_group=enqueued_scripts&_qm_acceptance_test=${test}` );
	}

	/**
	 * Assert that the QM menu is visible with a warning indicator
	 */
	async seeQMMenuWithWarning() {
		await expect( this.page.locator( '#wp-admin-bar-query-monitor.qm-warning' ) ).toBeVisible();
	}

	/**
	 * Assert that the QM menu is visible with a notice indicator
	 */
	async seeQMMenuWithNotice() {
		await expect( this.page.locator( '#wp-admin-bar-query-monitor.qm-notice' ) ).toBeVisible();
	}

	/**
	 * Assert that the QM menu is visible without any warning/notice indicators
	 */
	async seeQMMenu() {
		await expect( this.page.locator( '#wp-admin-bar-query-monitor' ) ).toBeVisible();
		await expect( this.page.locator( '#wp-admin-bar-query-monitor.qm-warning' ) ).not.toBeVisible();
		await expect( this.page.locator( '#wp-admin-bar-query-monitor.qm-notice' ) ).not.toBeVisible();
	}

	/**
	 * Open a specific QM panel by its name
	 */
	async openQMPanel( panel: string ) {
		// Only click the admin bar menu if QM is not already open
		const isOpen = await this.page.evaluate( () => {
			return document.getElementById( 'query-monitor-main' )?.classList.contains( 'qm-show' ) ?? false;
		} );
		if ( ! isOpen ) {
			await this.page.locator( '#wp-admin-bar-query-monitor' ).click();
		}
		// Find a button where the text starts with the panel name
		const buttonLocator = this.page.locator( '#qm-panel-menu button' ).filter( {
			hasText: new RegExp( `^${panel.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )}` )
		} );
		await buttonLocator.click();
	}

	/**
	 * Assert that text is visible within a specific QM panel
	 */
	async seeInQMPanel( panel: string, text: string ) {
		await this.openQMPanel( panel );
		await expect( this.page.locator( '.qm-panel-show' ) ).toContainText( text );
	}

	/**
	 * Get the visible QM panel locator
	 */
	getVisiblePanel(): Locator {
		return this.page.locator( '.qm-panel-show' );
	}

	/**
	 * Verifies that a table within the visible QM panel contains a row with the given cell values.
	 *
	 * Example usage:
	 * ```typescript
	 * await QueryMonitor.seeTableRowInQMPanel( 'PHP Errors', {
	 *     'Type': 'Warning',
	 *     'Message': 'Undefined variable: foo',
	 * } );
	 * ```
	 */
	async seeTableRowInQMPanel( panel: string, row: Record<string, string> ) {
		await this.openQMPanel( panel );

		const visiblePanel = this.page.locator( '.qm-panel-show' );

		// Build the row matcher
		for ( const [ header, expectedValue ] of Object.entries( row ) ) {
			// Find the column index by looking at the table headers
			const columnIndex = await visiblePanel.evaluate( ( panelEl, headerText ) => {
				const headers = panelEl.querySelectorAll( 'table thead th' );
				for ( let i = 0; i < headers.length; i++ ) {
					const th = headers[i];
					const text = th.textContent?.trim() || '';
					const labelText = th.querySelector( 'label' )?.textContent?.trim() || '';
					if ( text === headerText || labelText === headerText ) {
						return i + 1; // 1-based for nth-child
					}
				}
				return 0;
			}, header );

			if ( columnIndex === 0 ) {
				throw new Error( `Column header "${header}" not found in panel "${panel}"` );
			}

			// Find a row with matching cell value in this column
			const matchingRow = visiblePanel.locator( 'table tbody tr' ).filter( {
				has: this.page.locator( `td:nth-child(${columnIndex})` ).filter( {
					hasText: expectedValue
				} )
			} );

			await expect( matchingRow.first() ).toBeVisible();
		}
	}
}
