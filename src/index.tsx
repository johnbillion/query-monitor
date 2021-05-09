import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { iQMConfig } from '../output/html/settings';
import { QM } from '../output/qm';

declare const qm: iQMConfig;

document.addEventListener( 'DOMContentLoaded', function () {
	const panelKey = `qm-${ document.body.classList.contains( 'wp-admin' ) ? 'admin' : 'front' }-panel`;
	const positionKey = 'qm-container-position';

	const panels = {
		admin: qm.data.admin || null,
		assets_scripts: qm.data.assets_scripts,
		assets_styles: qm.data.assets_styles,
		block_editor: qm.data.block_editor,
		caps: qm.data.caps,
		conditionals: qm.data.conditionals,
		db_callers: qm.data.db_callers,
		db_components: qm.data.db_components,
		db_dupes: qm.data.db_dupes,
		db_queries: qm.data.db_queries,
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

	ReactDOM.render(
		<QM
			active={ localStorage.getItem( panelKey ) }
			adminMenuElement={ document.getElementById( 'wp-admin-bar-query-monitor' ) }
			menu={ qm.menu }
			panel_key={ panelKey }
			panel_menu={ qm.panel_menu }
			panels={ panels }
			position_key={ positionKey }
			side={ localStorage.getItem( positionKey ) === 'right' }
		/>,
		document.getElementById( 'query-monitor-container' )
	);
} );
