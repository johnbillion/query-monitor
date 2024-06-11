import { ErrorBoundary } from 'qmi/src/error-boundary';
import {
	PanelContext,
	PanelContextType,
	MainContext,
	getPanel,
	ErrorPanel,
	Warning,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

// what is this?
interface QMPanelData<TDataKey extends keyof DataTypes> {
	data: DataTypes[ TDataKey ];
	enabled: boolean;
}

// what is this?
export type iPanelData = {
	admin?: QMPanelData<'admin'>;
	assets_scripts: QMPanelData<'assets_scripts'>;
	assets_styles: QMPanelData<'assets_styles'>;
	block_editor: QMPanelData<'block_editor'>;
	caps: QMPanelData<'caps'>;
	conditionals: QMPanelData<'conditionals'>;
	db_callers: QMPanelData<'db_queries'>;
	db_components: QMPanelData<'db_queries'>;
	db_errors: QMPanelData<'db_queries'>;
	db_dupes: QMPanelData<'db_queries'>;
	db_queries: QMPanelData<'db_queries'>;
	doing_it_wrong: QMPanelData<'doing_it_wrong'>;
	environment: QMPanelData<'environment'>;
	hooks: QMPanelData<'hooks'>;
	http: QMPanelData<'http'>;
	languages: QMPanelData<'languages'>;
	logger?: QMPanelData<'logger'>;
	multisite?: QMPanelData<'multisite'>;
	php_errors?: QMPanelData<'php_errors'>;
	request?: QMPanelData<'request'>;
	theme?: QMPanelData<'theme'>;
	timing?: QMPanelData<'timing'>;
	transients: QMPanelData<'transients'>;
};

// what is this?
type Props = {
	data: iPanelData;
	active?: string;
}

export const Panels = ( props: Props ) => {
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

	const panel = getPanel( props.active );
	let output = null;

	if ( panel ) {
		// what is panelData?
		const panelData = props.data[ panel.data ] ?? null;
		// what is output?
		output = panelData ? panel.render( panelData.data, panelData.enabled ) : null;
	}

	return (
		<div id="qm-panels">
			<ErrorBoundary key={ props.active }>
				<PanelContext.Provider value={ panelContextValue }>
					{ panel ? (
						output ?? (
							<ErrorPanel>
								<p>
									<Warning>
										Data not found for panel: <code>{ props.active }</code>
									</Warning>
								</p>
							</ErrorPanel>
						)
					) : (
						<ErrorPanel>
							<p>
								<Warning>
									Panel not found: <code>{ props.active }</code>
								</Warning>
							</p>
						</ErrorPanel>
					) }
				</PanelContext.Provider>
			</ErrorBoundary>
		</div>
	);
};
