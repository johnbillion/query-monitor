import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { iQMConfig } from '../output/html/settings';
import { iPanelsProps } from '../output/panels';
import { QM } from '../output/qm';

declare const qm_data: iPanelsProps;
declare const qm: iQMConfig;

document.addEventListener( 'DOMContentLoaded', function () {
	const panels = {
		admin: qm_data.admin || null,
		assets_scripts: qm_data.assets_scripts,
		assets_styles: qm_data.assets_styles,
		block_editor: qm_data.block_editor,
		caps: qm_data.caps,
		conditionals: qm_data.conditionals,
		db_callers: qm_data.db_callers,
		db_components: qm_data.db_components,
		db_dupes: qm_data.db_dupes,
		db_queries: qm_data.db_queries,
		environment: qm_data.environment,
		hooks: qm_data.hooks,
		http: qm_data.http,
		languages: qm_data.languages,
		logger: qm_data.logger || null,
		php_errors: qm_data.php_errors || null,
		request: qm_data.request || null,
		response: qm_data.response || null,
		transients: qm_data.transients,
	};

	ReactDOM.render(
		<QM panels={ panels } panel_menu={ qm.panel_menu } />,
		document.getElementById( 'query-monitor-main' )
	);
} );
