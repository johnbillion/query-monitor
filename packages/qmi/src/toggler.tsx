import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface TogglerProps {
	children: React.ReactNode;
}

export class Toggler extends React.Component<TogglerProps, Record<string, unknown>> {

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
