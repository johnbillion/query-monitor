import { Icon } from '../components/icon';
import { NonTabularPanel } from '../panels/non-tabular-panel';
import { MainContext } from '../contexts/main-context';
import * as Utils from '../utils';
import { useState, useContext } from 'preact/hooks';

import {
	__,
	_x,
} from '@wordpress/i18n';
import { iSettings } from '../panels/panels';

interface SettingsProps {
	settings: iSettings;
}

export const Settings = ( {settings}: SettingsProps ) => {
	const [ verified, setVerified ] = useState( settings.verified );
	const {
		editor,
		setEditor,
		theme,
		setTheme,
		fabulous,
		setFabulous,
	} = useContext( MainContext );

	const setVerify = () => {
		const action = ( verified ? 'off' : 'on' );
		const formData = new FormData();

		formData.append( 'action', `qm_auth_${ action }` );
		formData.append( 'nonce', settings.auth_nonce[ action ] );

		window.fetch( settings.ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} ).then( () => {
			setVerified( ! verified );
		} );
	};

	const colours = {
		'auto': _x( 'Auto', 'colour scheme', 'query-monitor' ),
		'light': _x( 'Light', 'colour scheme', 'query-monitor' ),
		'dark': _x( 'Dark', 'colour scheme', 'query-monitor' ),
	};

	return (
		<NonTabularPanel>
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
								setEditor( e.currentTarget.value );
							} }
						>
							{ Utils.getEditors().map( ( { label, name } ) => (
								<option
									key={ label }
									value={ name }
								>
									{ ( name === '' && settings.file_link_format )
										? _x( 'Default', 'editor option', 'query-monitor' )
										: label
									}
								</option>
							) ) }
						</select>
					</p>
					{ editor && (
						<p>
							<Icon name="yes-alt"/>
							{ __( 'Editor is set', 'query-monitor' ) }
						</p>
					) }
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
										defaultChecked={ (theme || 'auto') === key }
										onChange={ ( e ) => {
											setTheme( e.currentTarget.value );
										} }
									/>
									{ value }
								</label>
							</li>
						) ) }
					</ul>
					<p className="qm-fabulous-toggle">
						<label>
							<input
								type="checkbox"
								className="qm-checkbox"
								name="qm-fabulous"
								defaultChecked={ fabulous }
								onChange={ ( e ) => {
									setFabulous( e.currentTarget.checked );
								} }
							/>
							{ __( 'Fabulous', 'query-monitor' ) }
						</label>
					</p>
				</section>
			</div>
		</NonTabularPanel>
	);
};
