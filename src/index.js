import React from 'react';
import ReactDOM from 'react-dom';

import Conditionals from '../output/html/conditionals.js';
import Caps from '../output/html/caps.js';
import Transients from '../output/html/transients.js';
import Languages from '../output/html/languages.js';
import DBQueries from '../output/html/db_queries.js';
import DBDupes from '../output/html/db_dupes.js';
import DBCallers from '../output/html/db_callers.js';
import DBComponents from '../output/html/db_components.js';
import BlockEditor from '../output/html/block_editor.js';
import Environment from '../output/html/environment.js';
import Scripts from '../output/html/assets_scripts.js';
import Styles from '../output/html/assets_styles.js';
import Hooks from '../output/html/hooks.js';
import Admin from '../output/html/admin.js';

jQuery(function($) {
	ReactDOM.render(<Conditionals data={qm_data.conditionals.data} enabled={qm_data.conditionals.enabled} id="conditionals" /> , document.getElementById('qm-conditionals-container'));
	ReactDOM.render(<Caps data={qm_data.caps.data} enabled={qm_data.caps.enabled} id="caps" /> , document.getElementById('qm-caps-container'));
	ReactDOM.render(<Transients data={qm_data.transients.data} enabled={qm_data.transients.enabled} id="transients" /> , document.getElementById('qm-transients-container'));
	ReactDOM.render(<Languages data={qm_data.languages.data} enabled={qm_data.languages.enabled} id="languages" /> , document.getElementById('qm-languages-container'));

	console.log(Object.keys(qm_data.db_queries.data.dbs));

	Object.keys(qm_data.db_queries.data.dbs).map(function(key){
		let data = qm_data.db_queries.data.dbs[key];
		ReactDOM.render(<DBQueries data={data} enabled={qm_data.db_queries.enabled} id="db_queries-wpdb" /> , document.getElementById('qm-db_queries-container'));
	});

	ReactDOM.render(<DBDupes data={qm_data.db_dupes.data} enabled={qm_data.db_dupes.enabled} id="db_dupes" /> , document.getElementById('qm-db_dupes-container'));
	ReactDOM.render(<DBCallers data={qm_data.db_callers.data} enabled={qm_data.db_callers.enabled} id="db_callers" /> , document.getElementById('qm-db_callers-container'));
	ReactDOM.render(<DBComponents data={qm_data.db_components.data} enabled={qm_data.db_components.enabled} id="db_components" /> , document.getElementById('qm-db_components-container'));
	ReactDOM.render(<BlockEditor data={qm_data.block_editor.data} enabled={qm_data.block_editor.enabled} id="block_editor" /> , document.getElementById('qm-block_editor-container'));
	ReactDOM.render(<Environment data={qm_data.environment.data} enabled={qm_data.environment.enabled} id="environment" /> , document.getElementById('qm-environment-container'));
	ReactDOM.render(<Scripts data={qm_data.assets_scripts.data} enabled={qm_data.assets_scripts.enabled} id="assets_scripts" /> , document.getElementById('qm-assets_scripts-container'));
	ReactDOM.render(<Styles data={qm_data.assets_styles.data} enabled={qm_data.assets_styles.enabled} id="assets_styles" /> , document.getElementById('qm-assets_styles-container'));
	qm_data.hooks && ReactDOM.render(<Hooks data={qm_data.hooks.data} enabled={qm_data.hooks.enabled} id="hooks" /> , document.getElementById('qm-hooks-container'));
	qm_data.admin && ReactDOM.render(<Admin data={qm_data.admin.data} enabled={qm_data.admin.enabled} id="admin" /> , document.getElementById('qm-admin-container'));

} );
