import { ErrorBoundary } from 'qmi/src/error-boundary';
import { PanelContext } from 'qmi/src/panel-context';
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
				<PanelContext.Provider value={ { id: props.active } }>
					<Admin { ...props.admin } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'block_editor' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<BlockEditor { ...props.block_editor } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'caps' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Caps { ...props.caps } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'conditionals' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Conditionals { ...props.conditionals } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'db_callers' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<DBCallers { ...props.db_queries } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'db_components' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<DBComponents { ...props.db_queries } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'db_dupes' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<DBDupes { ...props.db_dupes } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'db_queries' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<DBQueries { ...props.db_queries } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'doing_it_wrong' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<DoingItWrong { ...props.doing_it_wrong } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'environment' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Environment { ...props.environment } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'hooks' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Hooks { ...props.hooks } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'http' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<HTTP { ...props.http } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'logger' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Logger { ...props.logger } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'languages' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Languages { ...props.languages } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'multisite' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Multisite { ...props.multisite } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'php_errors' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<PHPErrors { ...props.php_errors } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'request' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Request { ...props.request } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'assets_scripts' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Scripts { ...props.assets_scripts } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'assets_styles' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Styles { ...props.assets_styles } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'response' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Theme { ...props.response } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'transients' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Transients { ...props.transients } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'timing' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Timing { ...props.timing } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
		{ props.active === 'settings' && (
			<ErrorBoundary>
				<PanelContext.Provider value={ { id: props.active } }>
					<Settings { ...qm.settings } />
				</PanelContext.Provider>
			</ErrorBoundary>
		) }
	</div>
);
