import * as React from "react";

import Conditionals from '../output/html/conditionals';
import Caps from '../output/html/caps';
import Transients from '../output/html/transients';
import Languages from '../output/html/languages';
import DBQueries from '../output/html/db_queries';
import DBDupes from '../output/html/db_dupes';
import DBCallers from '../output/html/db_callers';
import DBComponents from '../output/html/db_components';
import BlockEditor from '../output/html/block_editor';
import Environment from '../output/html/environment';
import Scripts from '../output/html/assets_scripts';
import Styles from '../output/html/assets_styles';
import Hooks from '../output/html/hooks';
import Admin from '../output/html/admin';
import HTTP from '../output/html/http';
import Logger from '../output/html/logger';

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
	transients: QMPanelData;
}

export class QM extends React.Component<iQMProps, {}> {
	render() {
		return (
			<>
				{this.props.admin && (
					<Admin
						data={this.props.admin.data}
						enabled={this.props.admin.enabled}
						id="admin"
					/>
				)}
				<BlockEditor
					data={this.props.block_editor.data}
					enabled={this.props.block_editor.enabled}
					id="block_editor"
				/>
				<Caps
					data={this.props.caps.data}
					enabled={this.props.caps.enabled}
					id="caps"
				/>
				<Conditionals
					data={this.props.conditionals.data}
					enabled={this.props.conditionals.enabled}
					id="conditionals"
				/>
				<DBCallers
					data={this.props.db_callers.data}
					enabled={this.props.db_callers.enabled}
					id="db_callers"
				/>
				<DBComponents
					data={this.props.db_components.data}
					enabled={this.props.db_components.enabled}
					id="db_components"
				/>
				<DBDupes
					data={this.props.db_dupes.data}
					enabled={this.props.db_dupes.enabled}
					id="db_dupes"
				/>
				{Object.keys(this.props.db_queries.data.dbs).map(key=>{
					<DBQueries
						data={this.props.db_queries.data.dbs[key]}
						enabled={this.props.db_queries.enabled}
						id={`db_queries-{key}`}
					/>
				})}
				<Environment
					data={this.props.environment.data}
					enabled={this.props.environment.enabled}
					id="environment"
				/>
				<Hooks
					data={this.props.hooks.data}
					enabled={this.props.hooks.enabled}
					id="hooks"
				/>
				<HTTP
					data={this.props.http.data}
					enabled={this.props.http.enabled}
					id="http"
				/>
				{this.props.logger && (
					<Logger
						data={this.props.logger.data}
						enabled={this.props.logger.enabled}
						id="logger"
					/>
				)}
				<Languages
					data={this.props.languages.data}
					enabled={this.props.languages.enabled}
					id="languages"
				/>
				<Scripts
					data={this.props.assets_scripts.data}
					enabled={this.props.assets_scripts.enabled}
					id="assets_scripts"
				/>
				<Styles
					data={this.props.assets_styles.data}
					enabled={this.props.assets_styles.enabled}
					id="assets_styles"
				/>
				<Transients
					data={this.props.transients.data}
					enabled={this.props.transients.enabled}
					id="transients"
				/>
			</>
		)
	}
}
