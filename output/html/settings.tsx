import {
	Icon,
	NonTabular,
	Context,
	Utils,
} from 'qmi';
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

import { iNavMenu } from '../nav';
import { iPanelsProps } from '../panels';

interface iSettingsProps {
	verified: boolean;
}

export interface iQMConfig {
	menu: any;
	ajax_errors: any;
	settings: iSettingsProps;
	panel_menu: iNavMenu;
	data: iPanelsProps;
}

interface il10nConfig {
	ajaxurl: string;
	auth_nonce: {
		on: string;
		off: string;
	}
}

declare const qm_l10n: il10nConfig;

export const Settings = ( props: iSettingsProps ) => {
	const [ verified, setVerified ] = React.useState( props.verified );
	const {
		editor,
		setEditor,
		theme,
		setTheme,
	} = React.useContext( Context );

	const setVerify = () => {
		const action = ( verified ? 'off' : 'on' );
		const formData = new FormData();

		formData.append( 'action', `qm_auth_${ action }` );
		formData.append( 'nonce', qm_l10n.auth_nonce[ action ] );

		window.fetch( qm_l10n.ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} ).then( () => {
			setVerified( ! verified );
		} );
	};

	const editors = {
		'None': '',
		'Atom': 'atom',
		'Netbeans': 'netbeans',
		'Nova': 'nova',
		'PhpStorm': 'phpstorm',
		'Sublime Text': 'sublime',
		'TextMate': 'textmate',
		'Visual Studio Code': 'vscode',
	};

	const colours = {
		'auto': _x( 'Auto', 'colour scheme', 'query-monitor' ),
		'light': _x( 'Light', 'colour scheme', 'query-monitor' ),
		'dark': _x( 'Dark', 'colour scheme', 'query-monitor' ),
	};

	return (
		<NonTabular id="settings">
			<h2 className="qm-screen-reader-text">
				{ __( 'Settings', 'query-monitor' ) }
			</h2>
			<div className="qm-grid">
				<section>
					<h3>
						{ __( 'Authentication', 'query-monitor' ) }
					</h3>
					<p>
						{ __( 'You can set an authentication cookie which allows you to view Query Monitor output when you are not logged in, or when you are logged in as a different user.', 'query-monitor' ) }
					</p>
					<p>
						<button className="qm-button" onClick={ setVerify }>
							{ verified ? (
								__( 'Clear authentication cookie', 'query-monitor' )
							) : (
								__( 'Set authentication cookie', 'query-monitor' )
							) }
						</button>
					</p>
					{ verified && (
						<p>
							<Icon name="yes-alt"/>
							{ __( 'Authentication cookie is set', 'query-monitor' ) }
						</p>
					) }
				</section>
				<section>
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
							value={ editor ?? '' }
							onChange={ ( e ) => {
								setEditor( e.target.value );
							} }
						>
							{ Utils.getEditors().map( ( { label, name } ) => (
								<option
									key={ label }
									value={ name }
								>
									{ label }
								</option>
							) ) }
						</select>
					</p>
				</section>
				<section>
					<h3>
						{ __( 'Appearance', 'query-monitor' ) }
					</h3>
					<p>
						{ __( 'Your browser color scheme is respected by default. You can override it here.', 'query-monitor' ) }
					</p>
					<ul>
						{ Object.entries( colours ).map( ( [ key, value ] ) => (
							<li key={ key }>
								<label>
									<input
										type="radio"
										className="qm-theme-toggle qm-radio"
										name="qm-theme"
										value={ key }
										defaultChecked={ theme === key }
										onChange={ ( e ) => {
											setTheme( e.target.value );
										} }
									/>
									{ value }
								</label>
							</li>
						) ) }
					</ul>
				</section>
			</div>
		</NonTabular>
	);
};
