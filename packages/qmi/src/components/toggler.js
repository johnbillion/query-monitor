import React, { Component } from 'react';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

export class Toggler extends Component {

	render() {
		return (
			<>
				<button
					className="qm-toggle"
					data-on="+"
					data-off="-"
					aria-expanded="false"
					aria-label={__( 'Toggle more information', 'query-monitor' )}
				>
					<span aria-hidden="true">+</span>
				</button>
				<div className="qm-toggled">
					{this.props.children}
				</div>
			</>
		);
	}

}
