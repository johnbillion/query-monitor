import * as React from 'react';
import { createRoot } from 'react-dom/client';

import { iQMConfig } from '../output/html/settings';
import { QM } from '../output/qm';

declare const qm: iQMConfig;

document.addEventListener( 'DOMContentLoaded', function () {
	const panelKey = `qm-${ document.body.classList.contains( 'wp-admin' ) ? 'admin' : 'front' }-panel`;
	const positionKey = 'qm-container-position';
	const themeKey = 'qm-theme';
	const editorKey = 'qm-editor';

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

	const active = localStorage.getItem( panelKey );
	const side = localStorage.getItem( positionKey ) === 'right';
	const editor = localStorage.getItem( editorKey );
	const theme = localStorage.getItem( themeKey );

	createRoot( document.getElementById( 'query-monitor-container' ) ).render(
		<QM
			active={ active }
			adminMenuElement={ document.getElementById( 'wp-admin-bar-query-monitor' ) }
			menu={ qm.menu }
			panel_menu={ qm.panel_menu }
			panels={ panels }
			side={ side }
			theme={ theme }
			editor={ editor }
			onPanelChange={ onPanelChange }
			onSideChange={ onSideChange }
			onThemeChange={ onThemeChange }
			onEditorChange={ onEditorChange }
		/>
	);
} );
