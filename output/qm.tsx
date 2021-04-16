import { Icon } from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

import { Nav, iNavMenu, NavSelect } from './nav';
import { Panels, iPanelsProps } from './panels';

export interface iQMProps {
	panels: iPanelsProps;
}

declare const qm_menu: iNavMenu;

export class QM extends React.Component<iQMProps, Record<string, unknown>> {
	render() {
		return (
			<>
				<div className="qm-resizer" id="qm-side-resizer"></div>
				<div className="qm-resizer" id="qm-title">
					<h1 className="qm-title-heading">
						{ __( 'Query Monitor', 'query-monitor' ) }
					</h1>
					<div className="qm-title-heading">
						<NavSelect menu={ qm_menu }/>
					</div>
					<button
						aria-label={ __( 'Settings', 'query-monitor' ) }
						className="qm-title-button qm-button-container-settings"
					>
						<Icon name="admin-generic"/>
					</button>
					<button
						aria-label={ __( 'Toggle panel position', 'query-monitor' ) }
						className="qm-title-button qm-button-container-position"
					>
						<Icon name="image-rotate-left"/>
					</button>
					<button
						aria-label={ __( 'Close Panel', 'query-monitor' ) }
						className="qm-title-button qm-button-container-close"
					>
						<Icon name="no-alt"/>
					</button>
				</div>
				<div id="qm-wrapper">
					<Nav menu={ qm_menu }/>
					<Panels { ...this.props.panels }/>
				</div>
			</>
		);
	}
}
