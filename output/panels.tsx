import { ErrorBoundary } from 'qmi/src/error-boundary';
import {
	PanelContext,
	PanelContextType,
	MainContext,
} from 'qmi';
import * as React from 'react';

import { Admin } from './html/admin';
import { Scripts } from './html/assets_scripts';
import { Styles } from './html/assets_styles';
import { BlockEditor } from './html/block_editor';
import { Caps } from './html/caps';
import { Conditionals } from './html/conditionals';
import { DBErrors } from './html/db_errors';
import { DBCallers } from './html/db_callers';
import { DBComponents } from './html/db_components';
import { DBDupes } from './html/db_dupes';
import { DBQueries } from './html/db_queries';
import { DoingItWrong } from './html/doing_it_wrong';
import { Environment } from './html/environment';
import { Hooks } from './html/hooks';
import { HTTP } from './html/http';
import { Languages } from './html/languages';
import { Logger } from './html/logger';
import { Multisite } from './html/multisite';
import { PHPErrors } from './html/php_errors';
import { Request } from './html/request';
import { Settings, iQMConfig } from './html/settings';
import { Theme } from './html/theme';
import { Timing } from './html/timing';
import { Transients } from './html/transients';
import { DBExpensive } from './html/db_expensive';

interface QMPanelData {
	data: any;
	enabled: boolean;
}

export type iPanelsProps = {
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

export const Panels = ( props: iPanelsProps ) => {
	const {
		filters,
		setFilters,
	} = React.useContext( MainContext );

	const panelContextValue: PanelContextType = {
		id: props.active,
		filters: filters[ props.active ] || {},
		setFilter: ( filterName, filterValue ) => {
			const newFilters = {
				...filters,
			};

			if ( ! ( props.active in newFilters ) ) {
				newFilters[ props.active ] = {};
			}

			if ( filterValue === '' ) {
				delete newFilters[ props.active ][ filterName ];
			} else {
				newFilters[ props.active ][ filterName ] = filterValue;
			}

			if ( Object.keys( newFilters[ props.active ] ).length === 0 ) {
				delete newFilters[ props.active ];
			}

			setFilters( newFilters );
		},
	};

	return (
		<div id="qm-panels">
			<ErrorBoundary key={ props.active }>
				<PanelContext.Provider value={ panelContextValue }>
					{ props.active === 'admin' && (
						<Admin { ...props.admin } />
					) }
					{ props.active === 'block_editor' && (
						<BlockEditor { ...props.block_editor } />
					) }
					{ props.active === 'caps' && (
						<Caps { ...props.caps } />
					) }
					{ props.active === 'conditionals' && (
						<Conditionals { ...props.conditionals } />
					) }
					{ props.active === 'db_errors' && (
						<DBErrors { ...props.db_queries } />
					) }
					{ props.active === 'db_expensive' && (
						<DBExpensive { ...props.db_queries } />
					) }
					{ props.active === 'db_callers' && (
						<DBCallers { ...props.db_queries } />
					) }
					{ props.active === 'db_components' && (
						<DBComponents { ...props.db_queries } />
					) }
					{ props.active === 'db_dupes' && (
						<DBDupes { ...props.db_queries } />
					) }
					{ props.active === 'db_queries' && (
						<DBQueries { ...props.db_queries } />
					) }
					{ props.active === 'doing_it_wrong' && (
						<DoingItWrong { ...props.doing_it_wrong } />
					) }
					{ props.active === 'environment' && (
						<Environment { ...props.environment } />
					) }
					{ props.active === 'hooks' && (
						<Hooks { ...props.hooks } />
					) }
					{ props.active === 'http' && (
						<HTTP { ...props.http } />
					) }
					{ props.active === 'logger' && (
						<Logger { ...props.logger } />
					) }
					{ props.active === 'languages' && (
						<Languages { ...props.languages } />
					) }
					{ props.active === 'multisite' && (
						<Multisite { ...props.multisite } />
					) }
					{ props.active === 'php_errors' && (
						<PHPErrors { ...props.php_errors } />
					) }
					{ props.active === 'request' && (
						<Request { ...props.request } />
					) }
					{ props.active === 'assets_scripts' && (
						<Scripts { ...props.assets_scripts } />
					) }
					{ props.active === 'assets_styles' && (
						<Styles { ...props.assets_styles } />
					) }
					{ props.active === 'response' && (
						<Theme { ...props.response } />
					) }
					{ props.active === 'transients' && (
						<Transients { ...props.transients } />
					) }
					{ props.active === 'timing' && (
						<Timing { ...props.timing } />
					) }
					{ props.active === 'settings' && (
						<Settings { ...qm.settings } />
					) }
				</PanelContext.Provider>
			</ErrorBoundary>
		</div>
	);
};
