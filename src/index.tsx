import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { iQMConfig } from '../output/html/settings';
import { QM } from '../output/qm';

declare const qm: iQMConfig;

document.addEventListener( 'DOMContentLoaded', function () {
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
		transients: qm.data.transients,
		active: '', // @TODO put the localStorage selected panel value here
	};

	ReactDOM.render(
		<QM menu={ qm.menu } panel_menu={ qm.panel_menu } panels={ panels } />,
		document.getElementById( 'query-monitor-main' )
	);
} );
