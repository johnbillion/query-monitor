import React from 'react';
import ReactDOM from 'react-dom';

import Conditionals from './output/html/conditionals.jsx';
import Caps from './output/html/caps.jsx';
import Transients from './output/html/transients.jsx';

jQuery(function($) {
	ReactDOM.render(<Conditionals data={qm_data.conditionals.data} enabled={qm_data.conditionals.enabled} id="conditionals" /> , document.getElementById('qm-conditionals-container'));
	ReactDOM.render(<Caps data={qm_data.caps.data} enabled={qm_data.caps.enabled} id="caps" /> , document.getElementById('qm-caps-container'));
	ReactDOM.render(<Transients data={qm_data.transients.data} enabled={qm_data.transients.enabled} id="transients" /> , document.getElementById('qm-transients-container'));
} );
