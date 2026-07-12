import { setLocaleData } from '@wordpress/i18n';

import { iNavMenu, iNavMenuItem, normaliseMenu } from '../output/nav';
import { iPanelData, iSettings } from '../output/panels/panels';
import { PanelDataMap } from '../output/types';
import { setQMGlobals } from '../output/utils';
import { registerPanel, registerOverview, registerSettings, collectMenuContributions, getMenuOrder, PanelMenuItem } from '../output/panels/panel-registry';

import { Admin, adminMenu } from '../output/html/admin';
import { BlockEditor, blockEditorMenu } from '../output/html/block_editor';
import { Caps, capsMenu } from '../output/html/caps';
import { Conditionals, conditionalsMenu } from '../output/html/conditionals';
import { DBCallers } from '../output/html/db_callers';
import { DBComponents } from '../output/html/db_components';
import { DBDupes } from '../output/html/db_dupes';
import { DBErrors } from '../output/html/db_errors';
import { DBExpensive } from '../output/html/db_expensive';
import { DBQueries, dbQueriesMenu } from '../output/html/db_queries';
import { DoingItWrong, doingItWrongMenu } from '../output/html/doing_it_wrong';
import { Environment, environmentMenu } from '../output/html/environment';
import { Hooks, hooksMenu } from '../output/html/hooks';
import { HTTP, httpMenu } from '../output/html/http';
import { Languages, languagesMenu } from '../output/html/languages';
import { Logger, loggerMenu } from '../output/html/logger';
import { Multisite, multisiteMenu } from '../output/html/multisite';
import { Overview, overviewMenu } from '../output/html/overview';
import { Timeline, timelineMenu } from '../output/html/timeline';
import { PHPErrors, phpErrorsMenu } from '../output/html/php_errors';
import { Request, requestMenu } from '../output/html/request';
import { Headers } from '../output/html/headers';
import { Settings } from '../output/html/settings';
import { Scripts, scriptsMenu } from '../output/html/assets_scripts';
import { Styles, stylesMenu } from '../output/html/assets_styles';
import { Theme, themeMenu } from '../output/html/theme';
import { Timing, timingMenu } from '../output/html/timing';
import { Transients, transientsMenu } from '../output/html/transients';

/**
 * Raw settings from PHP, before merging with l10n values.
 */
export type iQMSettings = Pick<iSettings, 'verified' | 'color_scheme'>;

/**
 * Localization data from PHP.
 */
export type iQML10n = Pick<iSettings, 'ajaxurl' | 'admin_url' | 'auth_nonce' | 'file_path_map' | 'file_link_format' | 'abspath' | 'contentpath'>;

/**
 * Menu item in the admin bar submenu.
 */
export type iQMMenuItem = {
	id: string;
	panel: string;
	title: string;
	ok_count?: number | null;
	notice_count?: number | null;
	warning_count?: number | null;
};

/**
 * Admin bar menu structure.
 */
export type iQMMenu = {
	top: {
		title: string[];
		classname: string;
	};
	sub: Record<string, iQMMenuItem>;
};

/**
 * The global QueryMonitorData object injected by PHP.
 */
export type iQM = {
	menu: iQMMenu;
	settings: iQMSettings;
	panel_menu?: iNavMenu;
	data: iPanelData;
	l10n: iQML10n;
	number_format: {
		thousands_sep: string;
		decimal_point: string;
	};
	locale_data?: Record<string, unknown> | null;
};

export type iQMData = iQM | false;

/**
 * Initialise lookup tables, globals, and translations from a QM data object.
 * Call once before rendering, and again when data changes (e.g. after navigation
 * in the browser extension).
 */
export function initializeQMData( data: iQM ): void {
	setQMGlobals( {
		number_format: data.number_format,
		l10n: data.l10n,
	} );

	if ( data.locale_data ) {
		setLocaleData( data.locale_data, 'query-monitor' );
	}
}

/**
 * Merge raw settings and l10n into the combined iSettings object.
 */
export function mergeSettings( data: iQM ): iSettings {
	return {
		...data.settings,
		ajaxurl: data.l10n.ajaxurl,
		admin_url: data.l10n.admin_url,
		auth_nonce: data.l10n.auth_nonce,
		file_path_map: data.l10n.file_path_map,
		file_link_format: data.l10n.file_link_format,
		abspath: data.l10n.abspath,
		contentpath: data.l10n.contentpath,
	};
}

const orderOf = ( panel: string ): number => getMenuOrder( panel ) ?? Infinity;
const byOrder = ( a: PanelMenuItem, b: PanelMenuItem ) => orderOf( a.panel ) - orderOf( b.panel );

function toNavItem( item: PanelMenuItem, children: iNavMenu ): iNavMenuItem {
	const navItem: iNavMenuItem = {
		panel: item.panel,
		title: item.title,
		ok_count: item.ok_count ?? null,
		notice_count: item.notice_count ?? null,
		warning_count: item.warning_count ?? null,
		new: item.new ?? false,
	};

	if ( Object.keys( children ).length > 0 ) {
		navItem.children = children;
	}

	return navItem;
}

/**
 * Converts the nested menu contributions into the panel navigation menu,
 * preserving each item's authored child order.
 */
function buildNav( items: PanelMenuItem[] ): iNavMenu {
	const menu: iNavMenu = {};

	for ( const item of items ) {
		if ( item.nav === false ) {
			continue;
		}

		menu[ item.id ] = toNavItem( item, buildNav( item.children ?? [] ) );
	}

	return menu;
}

/**
 * Builds the panel navigation menu from the active request's data.
 *
 * The admin toolbar menu (title, submenu, and CSS class) is generated
 * server-side for the current page load and passed through unchanged — it must
 * always be visible and always represent the page load, so it can't depend on a
 * request's data file being fetched. Only the panel navigation is built
 * client-side, so it reflects whichever request is currently being viewed.
 */
export function buildMenus(
	server: iQM,
	data: PanelDataMap,
): { menu: iQMMenu; panel_menu: iNavMenu } {
	const { items } = collectMenuContributions( data );

	// Top-level items come from independent panels, so order them by their
	// registration order. Nested children keep the order their panel authored them in.
	const tops = [ ...items ].sort( byOrder );

	// Client-registered built-in panels own the nav; any server-registered
	// entries (third-party PHP panels using the `qm/output/panel_menus` filter)
	// fill in behind them. Server entries may embed a count in their title string
	// (e.g. "Logs (5)"), so normalise those.
	const panel_menu: iNavMenu = { ...normaliseMenu( server.panel_menu ?? {} ), ...buildNav( tops ) };

	return {
		menu: server.menu,
		panel_menu,
	};
}

let panelsRegistered = false;

/**
 * Register all built-in panels. Safe to call multiple times.
 */
export function registerAllPanels(): void {
	if ( panelsRegistered ) {
		return;
	}

	panelsRegistered = true;

	registerOverview(
		'overview',
		{
			render: ( data, settings ) => <Overview data={ data } settings={ settings } />,
			order: 0,
			data: 'overview',
			menu: overviewMenu,
		}
	);
	registerOverview(
		'timeline',
		{
			render: ( data, settings ) => <Timeline data={ data } settings={ settings } />,
			order: 5,
			menu: timelineMenu,
		}
	);

	registerPanel(
		'admin',
		{
			render: ( data, enabled ) => <Admin data={ data } enabled={ enabled } />,
			data: 'admin',
			order: 60,
			menu: adminMenu
		}
	);
	registerPanel(
		'block_editor',
		{
			render: ( data, enabled ) => <BlockEditor data={ data } enabled={ enabled } />,
			data: 'block_editor',
			order: 55,
			menu: blockEditorMenu
		}
	);
	registerPanel(
		'caps',
		{
			render: ( data, enabled ) => <Caps data={ data } enabled={ enabled } />,
			data: 'caps',
			order: 105,
			menu: capsMenu
		}
	);
	registerPanel(
		'conditionals',
		{
			render: ( data, enabled ) => <Conditionals data={ data } enabled={ enabled } />,
			data: 'conditionals',
			menu: conditionalsMenu
		}
	);
	registerPanel(
		'db_callers',
		{
			render: ( data, enabled ) => <DBCallers data={ data } enabled={ enabled } />,
			data: 'db_queries',
		}
	);
	registerPanel(
		'db_components',
		{
			render: ( data, enabled ) => <DBComponents data={ data } enabled={ enabled } />,
			data: 'db_queries',
		}
	);
	registerPanel(
		'db_dupes',
		{
			render: ( data, enabled ) => <DBDupes data={ data } enabled={ enabled } />,
			data: 'db_queries',
		}
	);
	registerPanel(
		'db_errors',
		{
			render: ( data, enabled ) => <DBErrors data={ data } enabled={ enabled } />,
			data: 'db_queries',
		}
	);
	registerPanel(
		'db_expensive',
		{
			render: ( data, enabled ) => <DBExpensive data={ data } enabled={ enabled } />,
			data: 'db_queries',
		}
	);
	registerPanel(
		'db_queries',
		{
			render: ( data, enabled ) => <DBQueries data={ data } enabled={ enabled } />,
			data: 'db_queries',
			order: 20,
			menu: dbQueriesMenu,
		}
	);
	registerPanel(
		'doing_it_wrong',
		{
			render: ( data, enabled ) => <DoingItWrong data={ data } enabled={ enabled } />,
			data: 'doing_it_wrong',
			order: 15,
			menu: doingItWrongMenu,
		}
	);
	registerPanel(
		'environment',
		{
			render: ( data, enabled ) => <Environment data={ data } enabled={ enabled } />,
			data: 'environment',
			order: 110,
			menu: environmentMenu
		}
	);
	registerPanel(
		'hooks',
		{
			render: ( data, enabled ) => <Hooks data={ data } enabled={ enabled } />,
			data: 'hooks',
			order: 80,
			menu: hooksMenu
		}
	);
	registerPanel(
		'http',
		{
			render: ( data, enabled ) => <HTTP data={ data } enabled={ enabled } />,
			data: 'http',
			order: 90,
			menu: httpMenu,
		}
	);
	registerPanel(
		'languages',
		{
			render: ( data, enabled ) => <Languages data={ data } enabled={ enabled } />,
			data: 'languages',
			order: 80,
			menu: languagesMenu
		}
	);
	registerPanel(
		'logger',
		{
			render: ( data, enabled ) => <Logger data={ data } enabled={ enabled } />,
			data: 'logger',
			order: 47,
			menu: loggerMenu,
		}
	);
	registerPanel(
		'multisite',
		{
			render: ( data, enabled ) => <Multisite data={ data } enabled={ enabled } />,
			data: 'multisite',
			order: 55,
			menu: multisiteMenu
		}
	);
	registerPanel(
		'php_errors',
		{
			render: ( data, enabled ) => <PHPErrors data={ data } enabled={ enabled } />,
			data: 'php_errors',
			order: 10,
			menu: phpErrorsMenu,
		}
	);
	registerPanel(
		'request',
		{
			render: ( data, enabled ) => <Request data={ data } enabled={ enabled } />,
			data: 'request',
			order: 50,
			menu: requestMenu
		}
	);
	registerPanel(
		'raw_request',
		{
			render: ( data, enabled ) => <Headers data={ data } enabled={ enabled } type="request" />,
			data: 'raw_request',
		}
	);
	registerPanel(
		'raw_request-response',
		{
			render: ( data, enabled ) => <Headers data={ data } enabled={ enabled } type="response" />,
			data: 'raw_request',
		}
	);
	registerPanel(
		'assets_scripts',
		{
			render: ( data, enabled ) => <Scripts data={ data } enabled={ enabled } />,
			data: 'assets_scripts',
			order: 70,
			menu: scriptsMenu,
		}
	);
	registerPanel(
		'assets_styles',
		{
			render: ( data, enabled ) => <Styles data={ data } enabled={ enabled } />,
			data: 'assets_styles',
			order: 71,
			menu: stylesMenu,
		}
	);
	registerPanel(
		'response',
		{
			render: ( data, enabled ) => <Theme data={ data } enabled={ enabled } />,
			data: 'response',
			order: 60,
			menu: themeMenu
		}
	);
	registerPanel(
		'timing',
		{
			render: ( data, enabled ) => <Timing data={ data } enabled={ enabled } />,
			data: 'timing',
			order: 46,
			menu: timingMenu
		}
	);
	registerPanel(
		'transients',
		{
			render: ( data, enabled ) => <Transients data={ data } enabled={ enabled } />,
			data: 'transients',
			order: 100,
			menu: transientsMenu
		}
	);

	registerSettings(
		{
			render: ( settings ) => <Settings settings={ settings } />,
		}
	);
}
