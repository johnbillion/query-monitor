import * as React from 'react';
import { createRoot } from 'react-dom/client';

import { QM } from '../output/qm';
import { Fatal } from '../output/fatal';
import { iNavMenu } from '../output/nav';
import { iPanelData } from '../output/panels';
import {
	MainContextType,
	registerPanel,
} from 'qmi';

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
import { PHPErrors } from '../output/html/php_errors';
import { Request } from '../output/html/request';
import { Scripts } from '../output/html/assets_scripts';
import { Styles } from '../output/html/assets_styles';
import { Theme } from '../output/html/theme';
import { Timing } from '../output/html/timing';
import { Transients } from '../output/html/transients';

// what is this?
type iQM = {
	menu: any;
	settings: {
		verified: boolean;
	};
	panel_menu: iNavMenu;
	data: iPanelData;
}

// what is this?
declare const qm: iQM;

registerPanel( 'admin', {
	render: ( data, enabled ) => <Admin data={ data } enabled={ enabled } />,
	data: 'admin',
} );
// registerPanel( 'block_editor', {
// 	render: ( data, enabled ) => <BlockEditor data={ data } enabled={ enabled } />,
// 	data: 'block_editor',
// } );
registerPanel( 'caps', {
	render: ( data, enabled ) => <Caps data={ data } enabled={ enabled } />,
	data: 'caps',
} );
registerPanel( 'conditionals', {
	render: ( data, enabled ) => <Conditionals data={ data } enabled={ enabled } />,
	data: 'conditionals',
} );
registerPanel( 'db_callers', {
	render: ( data, enabled ) => <DBCallers data={ data } enabled={ enabled } />,
	data: 'db_queries',
} );
registerPanel( 'db_components', {
	render: ( data, enabled ) => <DBComponents data={ data } enabled={ enabled } />,
	data: 'db_queries',
} );
registerPanel( 'db_dupes', {
	render: ( data, enabled ) => <DBDupes data={ data } enabled={ enabled } />,
	data: 'db_queries',
} );
registerPanel( 'db_errors', {
	render: ( data, enabled ) => <DBErrors data={ data } enabled={ enabled } />,
	data: 'db_queries',
} );
registerPanel( 'db_expensive', {
	render: ( data, enabled ) => <DBExpensive data={ data } enabled={ enabled } />,
	data: 'db_queries',
} );
registerPanel( 'db_queries', {
	render: ( data, enabled ) => <DBQueries data={ data } enabled={ enabled } />,
	data: 'db_queries',
} );
registerPanel( 'doing_it_wrong', {
	render: ( data, enabled ) => <DoingItWrong data={ data } enabled={ enabled } />,
	data: 'doing_it_wrong',
} );
registerPanel( 'environment', {
	render: ( data, enabled ) => <Environment data={ data } enabled={ enabled } />,
	data: 'environment',
} );
registerPanel( 'hooks', {
	render: ( data, enabled ) => <Hooks data={ data } enabled={ enabled } />,
	data: 'hooks',
} );
registerPanel( 'http', {
	render: ( data, enabled ) => <HTTP data={ data } enabled={ enabled } />,
	data: 'http',
} );
registerPanel( 'languages', {
	render: ( data, enabled ) => <Languages data={ data } enabled={ enabled } />,
	data: 'languages',
} );
registerPanel( 'logger', {
	render: ( data, enabled ) => <Logger data={ data } enabled={ enabled } />,
	data: 'logger',
} );
registerPanel( 'multisite', {
	render: ( data, enabled ) => <Multisite data={ data } enabled={ enabled } />,
	data: 'multisite',
} );
registerPanel( 'php_errors', {
	render: ( data, enabled ) => <PHPErrors data={ data } enabled={ enabled } />,
	data: 'php_errors',
} );
registerPanel( 'request', {
	render: ( data, enabled ) => <Request data={ data } enabled={ enabled } />,
	data: 'request',
} );
// registerPanel( 'assets_scripts', {
// 	render: ( data, enabled ) => <Scripts data={ data } enabled={ enabled } />,
// 	data: 'assets_scripts',
// } );
// registerPanel( 'assets_styles', {
// 	render: ( data, enabled ) => <Styles data={ data } enabled={ enabled } />,
// 	data: 'assets_styles',
// } );
registerPanel( 'theme', {
	render: ( data, enabled ) => <Theme data={ data } enabled={ enabled } />,
	data: 'theme',
} );
registerPanel( 'timing', {
	render: ( data, enabled ) => <Timing data={ data } enabled={ enabled } />,
	data: 'timing',
} );
registerPanel( 'transients', {
	render: ( data, enabled ) => <Transients data={ data } enabled={ enabled } />,
	data: 'transients',
} );

document.addEventListener( 'DOMContentLoaded', function () {
	const fatalElement = document.getElementById( 'qm-fatal-component' );
	const containerElement = document.getElementById( 'query-monitor-container' );
	const adminMenuElement = document.getElementById( 'wp-admin-bar-query-monitor' );

	if ( fatalElement ) {
		createRoot( fatalElement ).render(
			<Fatal
				adminMenuElement={ adminMenuElement }
			/>
		);
		return;
	}

	const panelKey = `qm-${ document.body.classList.contains( 'wp-admin' ) ? 'admin' : 'front' }-panel`;
	const positionKey = 'qm-container-position';
	const themeKey = 'qm-theme';
	const editorKey = 'qm-editor';
	const filtersKey = 'qm-filters';
	const containerHeightKey = 'qm-container-height';
	const containerWidthKey = 'qm-container-width';

	const onPanelChange = ( active: string ) => {
		localStorage.setItem( panelKey, active );
	}

	const onSideChange = ( side: boolean ) => {
		localStorage.setItem( positionKey, ( side ? 'right' : '' ) );
	}

	const onThemeChange = ( theme: string ) => {
		localStorage.setItem( themeKey, theme );
	}

	const onEditorChange = ( editor: string ) => {
		localStorage.setItem( editorKey, editor );
	}

	const onFiltersChange = ( filters: MainContextType['filters'] ) => {
		sessionStorage.setItem( filtersKey, JSON.stringify( filters ) );
	}

	const onContainerResize = ( height: number, width: number ) => {
		localStorage.setItem( containerHeightKey, height.toString() );
		localStorage.setItem( containerWidthKey, width.toString() );
	}

	const active = localStorage.getItem( panelKey );
	const side = localStorage.getItem( positionKey ) === 'right';
	const editor = localStorage.getItem( editorKey );
	const theme = localStorage.getItem( themeKey );
	const rawFilters = sessionStorage.getItem( filtersKey );
	const filters = rawFilters ? JSON.parse( rawFilters ) : {};

	createRoot( containerElement ).render(
		<QM
			active={ active }
			adminMenuElement={ adminMenuElement }
			menu={ qm.menu }
			panel_menu={ qm.panel_menu }
			data={ qm.data }
			side={ side }
			theme={ theme }
			editor={ editor }
			filters={ filters }
			onPanelChange={ onPanelChange }
			onContainerResize={ onContainerResize }
			onSideChange={ onSideChange }
			onThemeChange={ onThemeChange }
			onEditorChange={ onEditorChange }
			onFiltersChange={ onFiltersChange }
		/>
	);
} );
