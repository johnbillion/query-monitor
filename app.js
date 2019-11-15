import React from 'react';
import ReactDOM from 'react-dom';

import Conditionals from './output/html/conditionals.jsx';
import Caps from './output/html/caps.jsx';
import Transients from './output/html/transients.jsx';
import Languages from './output/html/languages.jsx';
import DBQueries from './output/html/db_queries.jsx';
import DBDupes from './output/html/db_dupes.jsx';
import DBCallers from './output/html/db_callers.jsx';

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
} );
