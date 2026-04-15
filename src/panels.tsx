import { setLocaleData } from '@wordpress/i18n';

import { iNavMenu } from '../output/nav';
import { iPanelData, iSettings } from '../output/panels/panels';
import { DataTypes } from '../output/data-types';
import { FrameLookupEntry, setFrameLookup } from '../output/frame-lookup';
import { setQMGlobals } from '../output/utils';
import { registerPanel, registerOverview, registerSettings } from '../output/panels/panel-registry';

import { Admin } from '../output/html/admin';
import { BlockEditor } from '../output/html/block_editor';
import { Caps } from '../output/html/caps';
import { Conditionals } from '../output/html/conditionals';
import { DBCallers } from '../output/html/db_callers';
import { DBComponents } from '../output/html/db_components';
import { DBDupes } from '../output/html/db_dupes';
import { DBErrors } from '../output/html/db_errors';
import { DBExpensive } from '../output/html/db_expensive';
import { DBQueries } from '../output/html/db_queries';
import { DoingItWrong } from '../output/html/doing_it_wrong';
import { Environment } from '../output/html/environment';
import { Hooks } from '../output/html/hooks';
import { HTTP } from '../output/html/http';
import { Languages } from '../output/html/languages';
import { Logger } from '../output/html/logger';
import { Multisite } from '../output/html/multisite';
import { Overview } from '../output/html/overview';
import { PHPErrors } from '../output/html/php_errors';
import { Request } from '../output/html/request';
import { Headers } from '../output/html/headers';
import { Settings } from '../output/html/settings';
import { ConcernedHooks } from '../output/html/concerned_hooks';
import { Scripts } from '../output/html/assets_scripts';
import { Styles } from '../output/html/assets_styles';
import { Theme } from '../output/html/theme';
import { Timing } from '../output/html/timing';
import { Transients } from '../output/html/transients';
import { Timeline } from '../output/html/timeline';

/**
 * Raw settings from PHP, before merging with l10n values.
 */
export type iQMSettings = Pick<iSettings, 'verified' | 'extended_query_prompt_reason' | 'color_scheme'>;

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
	meta?: {
		classname: string;
	};
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
	panel_menu: iNavMenu;
	data: iPanelData;
	frames: FrameLookupEntry[];
	l10n: iQML10n;
	number_format: {
		thousands_sep: string;
		decimal_point: string;
	};
	locale_data?: Record<string, unknown> | null;
};

/**
 * Initialise lookup tables, globals, and translations from a QM data object.
 * Call once before rendering, and again when data changes (e.g. after navigation
 * in the browser extension).
 */
export function initializeQMData( data: iQM ): void {
	setFrameLookup( data.frames );
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

let panelsRegistered = false;

/**
 * Register all built-in panels. Safe to call multiple times.
 */
export function registerAllPanels(): void {
	if ( panelsRegistered ) {
		return;
	}

	panelsRegistered = true;

	registerOverview( 'overview', {
		render: ( data, settings ) => <Overview data={ data } settings={ settings } />,
	} );
	registerOverview( 'timeline', {
		render: ( data, settings ) => <Timeline data={ data } settings={ settings } />,
	} );

	registerPanel( 'admin', { render: ( data, enabled ) => <Admin data={ data } enabled={ enabled } />, data: 'admin' } );
	registerPanel( 'block_editor', { render: ( data, enabled ) => <BlockEditor data={ data } enabled={ enabled } />, data: 'block_editor' } );
	registerPanel( 'caps', { render: ( data, enabled ) => <Caps data={ data } enabled={ enabled } />, data: 'caps' } );
	registerPanel( 'conditionals', { render: ( data, enabled ) => <Conditionals data={ data } enabled={ enabled } />, data: 'conditionals' } );
	registerPanel( 'db_callers', { render: ( data, enabled ) => <DBCallers data={ data } enabled={ enabled } />, data: 'db_queries' } );
	registerPanel( 'db_components', { render: ( data, enabled ) => <DBComponents data={ data } enabled={ enabled } />, data: 'db_queries' } );
	registerPanel( 'db_dupes', { render: ( data, enabled ) => <DBDupes data={ data } enabled={ enabled } />, data: 'db_queries' } );
	registerPanel( 'db_errors', { render: ( data, enabled ) => <DBErrors data={ data } enabled={ enabled } />, data: 'db_queries' } );
	registerPanel( 'db_expensive', { render: ( data, enabled ) => <DBExpensive data={ data } enabled={ enabled } />, data: 'db_queries' } );
	registerPanel( 'db_queries', { render: ( data, enabled ) => <DBQueries data={ data } enabled={ enabled } />, data: 'db_queries' } );
	registerPanel( 'doing_it_wrong', { render: ( data, enabled ) => <DoingItWrong data={ data } enabled={ enabled } />, data: 'doing_it_wrong' } );
	registerPanel( 'environment', { render: ( data, enabled ) => <Environment data={ data } enabled={ enabled } />, data: 'environment' } );
	registerPanel( 'hooks', { render: ( data, enabled ) => <Hooks data={ data } enabled={ enabled } />, data: 'hooks' } );
	registerPanel( 'http', { render: ( data, enabled ) => <HTTP data={ data } enabled={ enabled } />, data: 'http' } );
	registerPanel( 'languages', { render: ( data, enabled ) => <Languages data={ data } enabled={ enabled } />, data: 'languages' } );
	registerPanel( 'logger', { render: ( data, enabled ) => <Logger data={ data } enabled={ enabled } />, data: 'logger' } );
	registerPanel( 'multisite', { render: ( data, enabled ) => <Multisite data={ data } enabled={ enabled } />, data: 'multisite' } );
	registerPanel( 'php_errors', { render: ( data, enabled ) => <PHPErrors data={ data } enabled={ enabled } />, data: 'php_errors' } );
	registerPanel( 'request', { render: ( data, enabled ) => <Request data={ data } enabled={ enabled } />, data: 'request' } );
	registerPanel( 'raw_request', { render: ( data, enabled ) => <Headers data={ data } enabled={ enabled } type="request" />, data: 'raw_request' } );
	registerPanel( 'raw_request-response', { render: ( data, enabled ) => <Headers data={ data } enabled={ enabled } type="response" />, data: 'raw_request' } );
	registerPanel( 'assets_scripts', { render: ( data, enabled ) => <Scripts data={ data } enabled={ enabled } />, data: 'assets_scripts' } );
	registerPanel( 'assets_styles', { render: ( data, enabled ) => <Styles data={ data } enabled={ enabled } />, data: 'assets_styles' } );
	registerPanel( 'response', { render: ( data, enabled ) => <Theme data={ data } enabled={ enabled } />, data: 'response' } );
	registerPanel( 'timing', { render: ( data, enabled ) => <Timing data={ data } enabled={ enabled } />, data: 'timing' } );
	registerPanel( 'transients', { render: ( data, enabled ) => <Transients data={ data } enabled={ enabled } />, data: 'transients' } );

	registerSettings( {
		render: ( settings ) => <Settings settings={ settings } />,
	} );

	const panelNamesMap: { [key: string]: string } = {
		'admin': 'Admin',
		'assets_scripts': 'Scripts',
		'assets_styles': 'Styles',
		'block_editor': 'Block Editor',
		'caps': 'Capability Checks',
		'doing_it_wrong': 'Doing It Wrong',
		'http': 'HTTP API Calls',
		'languages': 'Languages',
		'request': 'Request',
		'response': 'Template',
	};

	Object.keys( panelNamesMap ).forEach( ( collectorId ) => {
		registerPanel( `${collectorId}-concerned_hooks`, {
			render: ( data, enabled ) => <ConcernedHooks data={ data } enabled={ enabled } />,
			data: collectorId as keyof DataTypes,
		} );
	} );
}
