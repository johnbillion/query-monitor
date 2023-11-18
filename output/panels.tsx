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
			{ props.active === 'admin' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Admin { ...props.admin } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'block_editor' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<BlockEditor { ...props.block_editor } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'caps' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Caps { ...props.caps } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'conditionals' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Conditionals { ...props.conditionals } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'db_errors' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<DBErrors { ...props.db_queries } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'db_callers' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<DBCallers { ...props.db_queries } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'db_components' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<DBComponents { ...props.db_queries } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'db_dupes' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<DBDupes { ...props.db_dupes } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'db_queries' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<DBQueries { ...props.db_queries } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'doing_it_wrong' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<DoingItWrong { ...props.doing_it_wrong } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'environment' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Environment { ...props.environment } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'hooks' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Hooks { ...props.hooks } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'http' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<HTTP { ...props.http } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'logger' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Logger { ...props.logger } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'languages' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Languages { ...props.languages } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'multisite' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Multisite { ...props.multisite } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'php_errors' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<PHPErrors { ...props.php_errors } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'request' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Request { ...props.request } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'assets_scripts' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Scripts { ...props.assets_scripts } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'assets_styles' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Styles { ...props.assets_styles } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'response' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Theme { ...props.response } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'transients' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Transients { ...props.transients } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'timing' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Timing { ...props.timing } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
			{ props.active === 'settings' && (
				<ErrorBoundary>
					<PanelContext.Provider value={ panelContextValue }>
						<Settings { ...qm.settings } />
					</PanelContext.Provider>
				</ErrorBoundary>
			) }
		</div>
	);
};
