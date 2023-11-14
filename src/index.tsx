import * as React from 'react';
import { createRoot } from 'react-dom/client';

import { iQMConfig } from '../output/html/settings';
import { QM } from '../output/qm';
import { Fatal } from '../output/fatal';
import {
	MainContextType,
} from 'qmi';

declare const qm: iQMConfig;

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

	const panels = {
		admin: qm.data.admin || null,
		assets_scripts: qm.data.assets_scripts,
		assets_styles: qm.data.assets_styles,
		block_editor: qm.data.block_editor,
		caps: qm.data.caps,
		conditionals: qm.data.conditionals,
		db_callers: qm.data.db_queries,
		db_components: qm.data.db_queries,
		db_dupes: qm.data.db_dupes,
		db_queries: qm.data.db_queries,
		doing_it_wrong: qm.data.doing_it_wrong,
		environment: qm.data.environment,
		hooks: qm.data.hooks,
		http: qm.data.http,
		languages: qm.data.languages,
		logger: qm.data.logger || null,
		php_errors: qm.data.php_errors || null,
		request: qm.data.request || null,
		response: qm.data.response || null,
		timing: qm.data.timing || null,
		transients: qm.data.transients,
	};

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
			panels={ panels }
			side={ side }
			theme={ theme }
			editor={ editor }
			filters={ filters }
			onPanelChange={ onPanelChange }
			onSideChange={ onSideChange }
			onThemeChange={ onThemeChange }
			onEditorChange={ onEditorChange }
			onFiltersChange={ onFiltersChange }
		/>
	);
} );
