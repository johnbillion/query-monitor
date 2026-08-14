import { ErrorBoundary } from './error-boundary';
import { PanelContext, PanelContextType } from '../contexts/panel-context';
import { MainContext } from '../contexts/main-context';
import { getPanel, isOverviewPanel, isSettingsPanel } from './panel-registry';
import { ErrorPanel } from './error-panel';
import { Warning } from '../components/warning';
import { DataTypes } from '../data-types';
import { PanelProps, PanelDataMap } from '../types';
import { useRef, useState, useEffect, useContext } from 'preact/hooks';

type QMPanelData<TDataKey extends keyof DataTypes> = PanelProps<DataTypes[ TDataKey ]>;

export type iPanelData = {
	overview?: QMPanelData<'overview'>;
	timeline?: PanelProps<null>;
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

/**
 * Combined settings for use in the app.
 * Merges QueryMonitorData.settings with QueryMonitorData.l10n.
 */
export type iSettings = {
	verified: boolean;
	color_scheme: 'fresh' | 'modern';
	is_multisite: boolean;
	ajaxurl: string;
	admin_url: string;
	auth_nonce: {
		on: string;
		off: string;
	};
	file_path_map: Record<string, string>;
	file_link_format: string | false;
	abspath: string;
	contentpath: string;
};

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
	const containerRef = useRef<HTMLDivElement>( null );
	const originalParentRef = useRef<{ parent: ParentNode; nextSibling: ChildNode | null } | null>( null );
	const [ phpPanelExists, setPhpPanelExists ] = useState<boolean | null>( null );
	const { switchToPanel } = useContext( MainContext );
	const switchToPanelRef = useRef( switchToPanel );

	useEffect( () => {
		switchToPanelRef.current = switchToPanel;
	}, [ switchToPanel ] );

	useEffect( () => {
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

		const handleToggle = ( e: Event ) => {
			const button = ( e.target as HTMLElement ).closest( '.qm-toggle' ) as HTMLButtonElement | null;

			if ( ! button ) {
				return;
			}

			const expanded = button.getAttribute( 'aria-expanded' ) === 'true';
			const cell = button.closest( '.qm-has-toggle' );

			if ( ! cell ) {
				return;
			}

			const toggled = cell.querySelectorAll< HTMLElement >( '.qm-toggled' );

			toggled.forEach( ( el ) => {
				el.style.display = expanded ? 'none' : 'block';
			} );

			button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );

			const span = button.querySelector( 'span' );

			if ( span ) {
				span.textContent = expanded ? ( button.dataset.on ?? '+' ) : ( button.dataset.off ?? '-' );
			}
		};

		const handleFilterTrigger = ( e: Event ) => {
			const button = ( e.target as HTMLElement ).closest( '.qm-filter-trigger' ) as HTMLButtonElement | null;

			if ( ! button ) {
				return;
			}

			const target = button.dataset.qmTarget;

			if ( target ) {
				e.preventDefault();
				switchToPanelRef.current( target );
			}
		};

		phpPanel.addEventListener( 'click', handleToggle );
		phpPanel.addEventListener( 'click', handleFilterTrigger );

		// Cleanup: move the element back to its original location
		return () => {
			phpPanel.removeEventListener( 'click', handleToggle );
			phpPanel.removeEventListener( 'click', handleFilterTrigger );

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
	} = useContext( MainContext );

	const active = props.active ?? '';

	const panelContextValue: PanelContextType = {
		id: active,
		filters: filters[ active ] || {},
		setFilter: ( filterName, filterValue ) => {
			const newFilters = {
				...filters,
			};

			if ( ! ( active in newFilters ) ) {
				newFilters[ active ] = {};
			}

			if ( filterValue === '' ) {
				delete newFilters[ active ][ filterName ];
			} else {
				newFilters[ active ][ filterName ] = filterValue;
			}

			if ( Object.keys( newFilters[ active ] ).length === 0 ) {
				delete newFilters[ active ];
			}

			setFilters( newFilters );
		},
	};

	const panel = getPanel( active );
	let output = null;

	if ( panel ) {
		if ( isOverviewPanel( panel ) ) {
			// Overview panels receive the entire data object and settings
			output = panel.render( props.data, props.settings );
		} else if ( isSettingsPanel( panel ) ) {
			// Settings panel receives the settings object
			output = panel.render( props.settings );
		} else {
			const panelData = ( props.data as PanelDataMap )[ panel.data ] ?? null;
			output = panelData ? panel.render( panelData.data as DataTypes[keyof DataTypes], panelData.enabled ) : null;
		}
	}

	return (
		// Scrollable region must be keyboard-accessible.
		// eslint-disable-next-line jsx-a11y/no-noninteractive-tabindex
		<div id="qm-panels" tabIndex={ 0 }>
			<ErrorBoundary key={ active }>
				<PanelContext.Provider value={ panelContextValue }>
					{ panel ? (
						output ?? (
							<ErrorPanel>
								<p>
									<Warning>
										Data not found for panel: <code>{ active }</code>
									</Warning>
								</p>
							</ErrorPanel>
						)
					) : (
						<PhpPanelFallback panelId={ active } />
					) }
				</PanelContext.Provider>
			</ErrorBoundary>
		</div>
	);
};
