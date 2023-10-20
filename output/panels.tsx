import { ErrorBoundary } from 'qmi/src/error-boundary';
import * as React from 'react';

import Admin from './html/admin';
import Scripts from './html/assets_scripts';
import Styles from './html/assets_styles';
import BlockEditor from './html/block_editor';
import Caps from './html/caps';
import Conditionals from './html/conditionals';
import DBCallers from './html/db_callers';
import DBComponents from './html/db_components';
import DBDupes from './html/db_dupes';
import DBQueries from './html/db_queries';
import Environment from './html/environment';
import Hooks from './html/hooks';
import HTTP from './html/http';
import Languages from './html/languages';
import Logger from './html/logger';
import PHPErrors from './html/php_errors';
import Request from './html/request';
import { Settings, iQMConfig } from './html/settings';
import Theme from './html/theme';
import Timing from './html/timing';
import Transients from './html/transients';

interface QMPanelData {
	data: any;
	enabled: boolean;
}

export interface iPanelsProps {
	admin?: QMPanelData;
	assets_scripts: QMPanelData;
	assets_styles: QMPanelData;
	block_editor: QMPanelData;
	caps: QMPanelData;
	conditionals: QMPanelData;
	db_callers: QMPanelData;
	db_components: QMPanelData;
	db_dupes: QMPanelData;
	db_queries: QMPanelData;
	environment: QMPanelData;
	hooks: QMPanelData;
	http: QMPanelData;
	languages: QMPanelData;
	logger?: QMPanelData;
	php_errors?: QMPanelData;
	request?: QMPanelData;
	response?: QMPanelData;
	timing?: QMPanelData;
	transients: QMPanelData;
	active?: string;
}

interface iState {
	active: string;
}

declare const qm: iQMConfig;

export class Panels extends React.Component<iPanelsProps, iState> {
	render() {
		const active = this.props.active;

		return (
			<div id="qm-panels">
				{ active === 'admin' && (
					<ErrorBoundary>
						<Admin
							{ ...this.props.admin }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'block_editor' && (
					<ErrorBoundary>
						<BlockEditor
							{ ...this.props.block_editor }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'caps' && (
					<ErrorBoundary>
						<Caps
							{ ...this.props.caps }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'conditionals' && (
					<ErrorBoundary>
						<Conditionals
							{ ...this.props.conditionals }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'db_callers' && (
					<ErrorBoundary>
						<DBCallers
							{ ...this.props.db_callers }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'db_components' && (
					<ErrorBoundary>
						<DBComponents
							{ ...this.props.db_components }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'db_dupes' && (
					<ErrorBoundary>
						<DBDupes
							{ ...this.props.db_dupes }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'db_queries' && (
					<ErrorBoundary>
						<DBQueries
							{ ...this.props.db_queries }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'environment' && (
					<ErrorBoundary>
						<Environment
							{ ...this.props.environment }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'hooks' && (
					<ErrorBoundary>
						<Hooks
							{ ...this.props.hooks }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'http' && (
					<ErrorBoundary>
						<HTTP
							{ ...this.props.http }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'logger' && (
					<ErrorBoundary>
						<Logger
							{ ...this.props.logger }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'languages' && (
					<ErrorBoundary>
						<Languages
							{ ...this.props.languages }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'php_errors' && (
					<ErrorBoundary>
						<PHPErrors
							{ ...this.props.php_errors }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'request' && (
					<ErrorBoundary>
						<Request
							{ ...this.props.request }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'assets_scripts' && (
					<ErrorBoundary>
						<Scripts
							{ ...this.props.assets_scripts }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'assets_styles' && (
					<ErrorBoundary>
						<Styles
							{ ...this.props.assets_styles }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'response' && (
					<ErrorBoundary>
						<Theme
							{ ...this.props.response }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'transients' && (
					<ErrorBoundary>
						<Transients
							{ ...this.props.transients }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'timing' && (
					<ErrorBoundary>
						<Timing
							{ ...this.props.timing }
							id={ active }
						/>
					</ErrorBoundary>
				) }
				{ active === 'settings' && (
					<ErrorBoundary>
						<Settings { ...qm.settings } />
					</ErrorBoundary>
				) }
			</div>
		);
	}
}
