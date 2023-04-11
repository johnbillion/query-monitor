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
					<Admin
						data={ this.props.admin.data }
						enabled={ this.props.admin.enabled }
						id="admin"
					/>
				) }
				{ active === 'block_editor' && (
					<BlockEditor
						data={ this.props.block_editor.data }
						enabled={ this.props.block_editor.enabled }
						id="block_editor"
					/>
				) }
				{ active === 'caps' && (
					<Caps
						data={ this.props.caps.data }
						enabled={ this.props.caps.enabled }
						id="caps"
					/>
				) }
				{ active === 'conditionals' && (
					<Conditionals
						data={ this.props.conditionals.data }
						enabled={ this.props.conditionals.enabled }
						id="conditionals"
					/>
				) }
				{ active === 'db_callers' && (
					<DBCallers
						data={ this.props.db_callers.data }
						enabled={ this.props.db_callers.enabled }
						id="db_callers"
					/>
				) }
				{ active === 'db_components' && (
					<DBComponents
						data={ this.props.db_components.data }
						enabled={ this.props.db_components.enabled }
						id="db_components"
					/>
				) }
				{ active === 'db_dupes' && (
					<DBDupes
						data={ this.props.db_dupes.data }
						enabled={ this.props.db_dupes.enabled }
						id="db_dupes"
					/>
				) }
				{ active === 'db_queries' && (
					<DBQueries
						data={ this.props.db_queries.data }
						enabled={ this.props.db_queries.enabled }
						id="db_queries"
					/>
				) }
				{ active === 'environment' && (
					<Environment
						data={ this.props.environment.data }
						enabled={ this.props.environment.enabled }
						id="environment"
					/>
				) }
				{ active === 'hooks' && (
					<Hooks
						data={ this.props.hooks.data }
						enabled={ this.props.hooks.enabled }
						id="hooks"
					/>
				) }
				{ active === 'http' && (
					<HTTP
						data={ this.props.http.data }
						enabled={ this.props.http.enabled }
						id="http"
					/>
				) }
				{ active === 'logger' && (
					<Logger
						data={ this.props.logger.data }
						enabled={ this.props.logger.enabled }
						id="logger"
					/>
				) }
				{ active === 'languages' && (
					<Languages
						data={ this.props.languages.data }
						enabled={ this.props.languages.enabled }
						id="languages"
					/>
				) }
				{ active === 'php_errors' && (
					<PHPErrors
						data={ this.props.php_errors.data }
						enabled={ this.props.php_errors.enabled }
						id="php_errors"
					/>
				) }
				{ active === 'request' && (
					<Request
						data={ this.props.request.data }
						enabled={ this.props.request.enabled }
						id="request"
					/>
				) }
				{ active === 'assets_scripts' && (
					<Scripts
						data={ this.props.assets_scripts.data }
						enabled={ this.props.assets_scripts.enabled }
						id="assets_scripts"
					/>
				) }
				{ active === 'assets_styles' && (
					<Styles
						data={ this.props.assets_styles.data }
						enabled={ this.props.assets_styles.enabled }
						id="assets_styles"
					/>
				) }
				{ active === 'response' && (
					<Theme
						data={ this.props.response.data }
						enabled={ this.props.response.enabled }
						id="response"
					/>
				) }
				{ active === 'transients' && (
					<Transients
						data={ this.props.transients.data }
						enabled={ this.props.transients.enabled }
						id="transients"
					/>
				) }
				{ active === 'timing' && (
					<Timing
						data={ this.props.timing.data }
						enabled={ this.props.timing.enabled }
						id="timing"
					/>
				) }
				{ active === 'settings' && (
					<Settings { ...qm.settings } />
				) }
			</div>
		);
	}
}
