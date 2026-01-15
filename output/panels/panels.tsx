import { ErrorBoundary } from './error-boundary';
import { PanelContext, PanelContextType } from '../contexts/panel-context';
import { MainContext } from '../contexts/main-context';
import { getPanel, isOverviewPanel, isSettingsPanel } from './panel-registry';
import { ErrorPanel } from './error-panel';
import { Warning } from '../components/warning';
import { DataTypes } from '../data-types';
import * as React from 'react';

// what is this?
interface QMPanelData<TDataKey extends keyof DataTypes> {
	data: DataTypes[ TDataKey ];
	enabled: boolean;
}

// @todo this comes from QueryMonitorData / iQM
export type iPanelData = {
	overview?: QMPanelData<'overview'>;
	admin?: QMPanelData<'admin'>;
	assets_scripts: QMPanelData<'assets_scripts'>;
	assets_styles: QMPanelData<'assets_styles'>;
	block_editor: QMPanelData<'block_editor'>;
	cache?: QMPanelData<'cache'>;
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
	raw_request?: QMPanelData<'raw_request'>;
	request?: QMPanelData<'request'>;
	response?: QMPanelData<'response'>;
	timing?: QMPanelData<'timing'>;
	transients: QMPanelData<'transients'>;
};

// @todo this comes from QueryMonitorData / iQM
export type iSettings = {
	verified: boolean;
	extended_query_prompt_reason: 'conflict' | 'disabled' | 'failed' | null;
	ajaxurl: string;
	admin_url: string;
	auth_nonce: {
		on: string;
		off: string;
	};
};

// what is this?
type Props = {
	data: iPanelData;
	settings: iSettings;
	active?: string;
}

/**
 * Component that pulls in a PHP-rendered panel from the DOM when no React panel is registered.
 * Moves the element back to its original location on cleanup to preserve it for future use.
 */
const PhpPanelFallback = ( { panelId }: { panelId: string } ) => {
	const containerRef = React.useRef<HTMLDivElement>( null );
	const originalParentRef = React.useRef<{ parent: ParentNode; nextSibling: ChildNode | null } | null>( null );
	const [ phpPanelExists, setPhpPanelExists ] = React.useState<boolean | null>( null );

	React.useEffect( () => {
		const phpPanel = document.getElementById( `qm-${ panelId }-container` );

		if ( ! phpPanel ) {
			setPhpPanelExists( false );
			return;
		}

		setPhpPanelExists( true );

		if ( containerRef.current ) {
			// Store original location before moving
			if ( phpPanel.parentNode ) {
				originalParentRef.current = {
					parent: phpPanel.parentNode,
					nextSibling: phpPanel.nextSibling,
				};
			}

			// Move the PHP panel into our container
			containerRef.current.appendChild( phpPanel );
		}

		// Cleanup: move the element back to its original location
		return () => {
			if ( phpPanel && originalParentRef.current ) {
				const { parent, nextSibling } = originalParentRef.current;
				if ( nextSibling ) {
					parent.insertBefore( phpPanel, nextSibling );
				} else {
					parent.appendChild( phpPanel );
				}
			}
		};
	}, [ panelId ] );

	if ( phpPanelExists === false ) {
		return (
			<ErrorPanel>
				<p>
					<Warning>
						Panel not found: <code>{ panelId }</code>
					</Warning>
				</p>
			</ErrorPanel>
		);
	}

	return (
		<div
			ref={ containerRef }
			className="qm-php-panel-container"
		/>
	);
};

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
		if ( isOverviewPanel( panel ) ) {
			// Overview panel receives the entire data object and settings
			output = panel.render( props.data, props.settings );
		} else if ( isSettingsPanel( panel ) ) {
			// Settings panel receives the settings object
			output = panel.render( props.settings );
		} else {
			// what is panelData?
			const panelData = props.data[ panel.data ] ?? null;
			// what is output?
			if ( props.active === 'settings' ) {
				// Settings panel doesn't need backend data
				output = panel.render( null, true );
			} else {
				output = panelData ? panel.render( panelData.data, panelData.enabled ) : null;
			}
		}
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
						<PhpPanelFallback panelId={ props.active } />
					) }
				</PanelContext.Provider>
			</ErrorBoundary>
		</div>
	);
};
