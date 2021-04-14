import {
	Icon,
	NonTabular,
} from 'qmi';
import * as React from 'react';

import { __, _x } from '@wordpress/i18n';

export interface iSettingsProps {
	verified: boolean;
	editor: string;
}

export interface iQMConfig {
	menu: any;
	ajax_errors: any;
	settings: iSettingsProps
}

export class Settings extends React.Component<iSettingsProps, Record<string, unknown>> {
	constructor( props: iSettingsProps ) {
		super( props );

		this.state = {
			verified: props.verified,
		};
	}

	render() {
		return (
			<NonTabular id="settings">
				<h2 className="qm-screen-reader-text">
					{ __( 'Settings', 'query-monitor' ) }
				</h2>
				<div className="qm-boxed">
					<section>
						<h3>
							{ __( 'Authentication', 'query-monitor' ) }
						</h3>
						<p>
							{ __( 'You can set an authentication cookie which allows you to view Query Monitor output when you are not logged in, or when you are logged in as a different user.', 'query-monitor' ) }
						</p>
						<p>
							<button className="qm-button">
								{ this.props.verified ? (
									<>
										{ __( 'Clear authentication cookie', 'query-monitor' ) }
									</>
								) : (
									<>
										{ __( 'Set authentication cookie', 'query-monitor' ) }
									</>
								) }
							</button>
						</p>
						{ this.props.verified && (
							<p>
								<Icon name="yes"/>
								{ __( 'Authentication cookie is set', 'query-monitor' ) }
							</p>
						) }
					</section>
				</div>
			</NonTabular>
		);
	}

}
