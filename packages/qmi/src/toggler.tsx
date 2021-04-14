import * as React from 'react';

import { __ } from '@wordpress/i18n';

export class Toggler extends React.Component {

	render() {
		return (
			<>
				<button
					aria-expanded="false"
					aria-label={ __( 'Toggle more information', 'query-monitor' ) }
					className="qm-toggle"
					data-off="-"
					data-on="+"
				>
					<span aria-hidden="true">+</span>
				</button>
				<div className="qm-toggled">
					{ this.props.children }
				</div>
			</>
		);
	}

}
