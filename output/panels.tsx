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
import DoingItWrong from './html/doing_it_wrong';
import Environment from './html/environment';
import Hooks from './html/hooks';
import HTTP from './html/http';
import Languages from './html/languages';
import Logger from './html/logger';
import Multisite from './html/multisite';
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
	doing_it_wrong: QMPanelData;
	environment: QMPanelData;
	hooks: QMPanelData;
	http: QMPanelData;
	languages: QMPanelData;
	logger?: QMPanelData;
	multisite?: QMPanelData;
	php_errors?: QMPanelData;
	request?: QMPanelData;
	response?: QMPanelData;
	timing?: QMPanelData;
	transients: QMPanelData;
	active?: string;
}

declare const qm: iQMConfig;

export const Panels = ( props: iPanelsProps ) => (
	<div id="qm-panels">
		{ props.active === 'admin' && (
			<ErrorBoundary>
				<Admin { ...props.admin } />
			</ErrorBoundary>
		) }
		{ props.active === 'block_editor' && (
			<ErrorBoundary>
				<BlockEditor { ...props.block_editor } />
			</ErrorBoundary>
		) }
		{ props.active === 'caps' && (
			<ErrorBoundary>
				<Caps { ...props.caps } />
			</ErrorBoundary>
		) }
		{ props.active === 'conditionals' && (
			<ErrorBoundary>
				<Conditionals { ...props.conditionals } />
			</ErrorBoundary>
		) }
		{ props.active === 'db_callers' && (
			<ErrorBoundary>
				<DBCallers { ...props.db_queries } />
			</ErrorBoundary>
		) }
		{ props.active === 'db_components' && (
			<ErrorBoundary>
				<DBComponents { ...props.db_queries } />
			</ErrorBoundary>
		) }
		{ props.active === 'db_dupes' && (
			<ErrorBoundary>
				<DBDupes { ...props.db_dupes } />
			</ErrorBoundary>
		) }
		{ props.active === 'db_queries' && (
			<ErrorBoundary>
				<DBQueries { ...props.db_queries } />
			</ErrorBoundary>
		) }
		{ props.active === 'doing_it_wrong' && (
			<ErrorBoundary>
				<DoingItWrong { ...props.doing_it_wrong } />
			</ErrorBoundary>
		) }
		{ props.active === 'environment' && (
			<ErrorBoundary>
				<Environment { ...props.environment } />
			</ErrorBoundary>
		) }
		{ props.active === 'hooks' && (
			<ErrorBoundary>
				<Hooks { ...props.hooks } />
			</ErrorBoundary>
		) }
		{ props.active === 'http' && (
			<ErrorBoundary>
				<HTTP { ...props.http } />
			</ErrorBoundary>
		) }
		{ props.active === 'logger' && (
			<ErrorBoundary>
				<Logger { ...props.logger } />
			</ErrorBoundary>
		) }
		{ props.active === 'languages' && (
			<ErrorBoundary>
				<Languages { ...props.languages } />
			</ErrorBoundary>
		) }
		{ props.active === 'multisite' && (
			<ErrorBoundary>
				<Multisite { ...props.multisite } />
			</ErrorBoundary>
		) }
		{ props.active === 'php_errors' && (
			<ErrorBoundary>
				<PHPErrors { ...props.php_errors } />
			</ErrorBoundary>
		) }
		{ props.active === 'request' && (
			<ErrorBoundary>
				<Request { ...props.request } />
			</ErrorBoundary>
		) }
		{ props.active === 'assets_scripts' && (
			<ErrorBoundary>
				<Scripts { ...props.assets_scripts } />
			</ErrorBoundary>
		) }
		{ props.active === 'assets_styles' && (
			<ErrorBoundary>
				<Styles { ...props.assets_styles } />
			</ErrorBoundary>
		) }
		{ props.active === 'response' && (
			<ErrorBoundary>
				<Theme { ...props.response } />
			</ErrorBoundary>
		) }
		{ props.active === 'transients' && (
			<ErrorBoundary>
				<Transients { ...props.transients } />
			</ErrorBoundary>
		) }
		{ props.active === 'timing' && (
			<ErrorBoundary>
				<Timing { ...props.timing } />
			</ErrorBoundary>
		) }
		{ props.active === 'settings' && (
			<ErrorBoundary>
				<Settings { ...qm.settings } />
			</ErrorBoundary>
		) }
	</div>
);
