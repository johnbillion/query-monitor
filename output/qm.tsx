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
import Theme from './html/theme';
import Transients from './html/transients';

interface QMPanelData {
	data: any;
	enabled: boolean;
}

export interface iQMProps {
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
	transients: QMPanelData;
}

export class QM extends React.Component<iQMProps, Record<string, unknown>> {
	render() {
		return (
			<>
				{ this.props.admin && (
					<Admin
						data={ this.props.admin.data }
						enabled={ this.props.admin.enabled }
						id="admin"
					/>
				) }
				<BlockEditor
					data={ this.props.block_editor.data }
					enabled={ this.props.block_editor.enabled }
					id="block_editor"
				/>
				<Caps
					data={ this.props.caps.data }
					enabled={ this.props.caps.enabled }
					id="caps"
				/>
				<Conditionals
					data={ this.props.conditionals.data }
					enabled={ this.props.conditionals.enabled }
					id="conditionals"
				/>
				<DBCallers
					data={ this.props.db_callers.data }
					enabled={ this.props.db_callers.enabled }
					id="db_callers"
				/>
				<DBComponents
					data={ this.props.db_components.data }
					enabled={ this.props.db_components.enabled }
					id="db_components"
				/>
				<DBDupes
					data={ this.props.db_dupes.data }
					enabled={ this.props.db_dupes.enabled }
					id="db_dupes"
				/>
				{ Object.keys( this.props.db_queries.data.dbs ).map( key => (
					<DBQueries
						data={ this.props.db_queries.data.dbs[key] }
						enabled={ this.props.db_queries.enabled }
						id="db_queries-wpdb"
					/>
				) ) }
				<Environment
					data={ this.props.environment.data }
					enabled={ this.props.environment.enabled }
					id="environment"
				/>
				<Hooks
					data={ this.props.hooks.data }
					enabled={ this.props.hooks.enabled }
					id="hooks"
				/>
				<HTTP
					data={ this.props.http.data }
					enabled={ this.props.http.enabled }
					id="http"
				/>
				{ this.props.logger && (
					<Logger
						data={ this.props.logger.data }
						enabled={ this.props.logger.enabled }
						id="logger"
					/>
				) }
				<Languages
					data={ this.props.languages.data }
					enabled={ this.props.languages.enabled }
					id="languages"
				/>
				{ this.props.php_errors && (
					<PHPErrors
						data={ this.props.php_errors.data }
						enabled={ this.props.php_errors.enabled }
						id="php_errors"
					/>
				) }
				<Request
					data={ this.props.request.data }
					enabled={ this.props.request.enabled }
					id="request"
				/>
				<Scripts
					data={ this.props.assets_scripts.data }
					enabled={ this.props.assets_scripts.enabled }
					id="assets_scripts"
				/>
				<Styles
					data={ this.props.assets_styles.data }
					enabled={ this.props.assets_styles.enabled }
					id="assets_styles"
				/>
				{ this.props.response && (
					<Theme
						data={ this.props.response.data }
						enabled={ this.props.response.enabled }
						id="response"
					/>
				) }
				<Transients
					data={ this.props.transients.data }
					enabled={ this.props.transients.enabled }
					id="transients"
				/>
			</>
		);
	}
}
