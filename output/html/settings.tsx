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

interface il10nConfig {
	ajaxurl: string;
	auth_nonce: {
		on: string;
		off: string;
	}
}

declare const qm_l10n: il10nConfig;

export class Settings extends React.Component<iSettingsProps, Record<string, unknown>> {
	constructor( props: iSettingsProps ) {
		super( props );

		this.state = {
			verified: props.verified,
		};
	}

	setVerify() {
		const action = ( this.state.verified ? 'off' : 'on' );

		jQuery.ajax( qm_l10n.ajaxurl, {
			type: 'POST',
			data: {
				action: `qm_auth_${ action }`,
				nonce: qm_l10n.auth_nonce[ action ],
			},
			success: () => {
				this.setState( {
					verified: ( ! this.state.verified ),
				} );
			},
			dataType: 'json',
			xhrFields: {
				withCredentials: true,
			},
		} );
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
							<button className="qm-button" onClick={ () => this.setVerify() }>
								{ this.state.verified ? (
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
						{ this.state.verified && (
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
