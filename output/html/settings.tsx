import 'whatwg-fetch';
import {
	Icon,
	NonTabular,
} from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

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
		const formData = new FormData();

		formData.append( 'action', `qm_auth_${ action }` );
		formData.append( 'nonce', qm_l10n.auth_nonce[ action ] );

		window.fetch( qm_l10n.ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} ).then( () => {
			this.setState( {
				verified: ( ! this.state.verified ),
			} );
		} );
	}

	render() {
		const editors = {
			'Default/Xdebug': '',
			'Atom': 'atom',
			'Netbeans': 'netbeans',
			'PhpStorm': 'phpstorm',
			'Sublime Text': 'sublime',
			'Visual Studio Code': 'vscode',
		};

		const editor = localStorage.getItem( 'qm-editor' );

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
				<div className="qm-boxed">
					<section className="qm-editor">
						<h3>
							{ __( 'Editor', 'query-monitor' ) }
						</h3>
						<p>
							{ __( 'You can set your editor here, so that when you click on stack trace links the file opens in your editor.', 'query-monitor' ) }
						</p>
						<p>
							<select
								className="qm-filter"
								id="qm-editor-select"
								name="qm-editor-select"
								onChange={ ( e ) => {
									localStorage.setItem( 'qm-editor', e.target.value );
								} }
							>
								{ Object.keys( editors ).map( ( key: keyof typeof editors ) => (
									<>
										<option
											selected={ editors[ key ] === editor }
											value={ editors[ key ] }
										>
											{ key }
										</option>
									</>
								) ) }
							</select>
						</p>
					</section>
				</div>
			</NonTabular>
		);
	}

}
