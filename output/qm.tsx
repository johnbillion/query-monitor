import { Icon } from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

import { Nav, iNavMenu, NavSelect } from './nav';
import { Panels, iPanelsProps } from './panels';

export interface iQMProps {
	panels: iPanelsProps;
	panel_menu: iNavMenu;
}

interface iState {
	active: string;
}

export class QM extends React.Component<iQMProps, iState> {
	constructor( props: iQMProps ) {
		super( props );

		this.state = {
			active: props.panels.active,
		};
	}

	render() {
		const setActivePanel = ( active: string ) => {
			this.setState( {
				active,
			} );
		};

		return (
			<>
				<div className="qm-resizer" id="qm-side-resizer"></div>
				<div className="qm-resizer" id="qm-title">
					<h1 className="qm-title-heading">
						{ __( 'Query Monitor', 'query-monitor' ) }
					</h1>
					<div className="qm-title-heading">
						<NavSelect menu={ this.props.panel_menu } onSwitch={ setActivePanel }/>
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
					<Nav menu={ this.props.panel_menu } onSwitch={ setActivePanel } />
					<Panels { ...this.props.panels } active={ this.state.active }/>
				</div>
			</>
		);
	}
}
