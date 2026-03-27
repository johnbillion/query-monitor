import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { Component } from '../component';
import { SourceLocation } from '../components/source-location';
import { Warning } from '../components/warning';
import { DataTypes } from '../data-types';
import { componentFilterCallback, deriveComponentFilters } from '../table';
import { PanelProps } from '../types';
import {
	__,
	sprintf,
} from '@wordpress/i18n';

type HookAction = DataTypes['hooks']['hooks'][number]['actions'][number];
type HookCallback = HookAction['callback'];
type HookComponent = NonNullable<HookCallback['component']>;

interface FlattenedRow {
	hookName: string;
	priority: number | null;
	callback: HookCallback | null;
	component: HookComponent | null;
}

const flattenHooks = ( hooks: DataTypes['hooks']['hooks'] ): FlattenedRow[] => {
	const rows: FlattenedRow[] = [];

	for ( const hook of hooks ) {
		if ( ! hook.actions.length ) {
			rows.push( {
				hookName: hook.name,
				priority: null,
				callback: null,
				component: null,
			} );
		} else {
			for ( const action of hook.actions ) {
				rows.push( {
					hookName: hook.name,
					priority: action.priority,
					callback: action.callback,
					component: action.callback.component ?? null,
				} );
			}
		}
	}

	return rows;
};

export const Hooks = ( { data }: PanelProps<DataTypes['hooks']> ) => {
	if ( ! data.hooks?.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No hooks were recorded.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	const rows = flattenHooks( data.hooks );
	const componentFilters = deriveComponentFilters( rows, ( row ) => row.component );

	return (
		<TabularPanel
			title={ __( 'Hooks', 'query-monitor' ) }
			cols={ {
				hook: {
					heading: __( 'Hook', 'query-monitor' ),
					className: 'qm-ltr qm-nowrap',
					render: ( row ) => (
						<>
							<span className="qm-sticky">
								<code>{ row.hookName }</code>
							</span>
							{ row.hookName === 'all' && (
								<>
									<br/>
									<Warning>
										{ sprintf(
											/* translators: %s: Action name */
											__( 'Warning: The %s action is extremely resource intensive. Try to avoid using it.', 'query-monitor' ),
											'all'
										) }
									</Warning>
								</>
							) }
						</>
					),
					rowSpan: ( row, i, data ) => {
						// If previous row has same hookName, we're not first in this consecutive group
						if ( i > 0 && data[ i - 1 ].hookName === row.hookName ) {
							return 0;
						}
						// Count consecutive rows with same hookName
						let count = 1;
						while ( i + count < data.length && data[ i + count ].hookName === row.hookName ) {
							count++;
						}
						return count;
					},
				},
				priority: {
					heading: __( 'Priority', 'query-monitor' ),
					className: 'qm-num',
					render: ( row ) => {
						if ( row.priority === null ) {
							return '';
						}

						let label: string | null = null;

						if ( row.priority === data.php_int_max ) {
							label = 'PHP_INT_MAX';
						} else if ( row.priority === data.php_int_min ) {
							label = 'PHP_INT_MIN';
						} else if ( row.priority === -data.php_int_max ) {
							label = '-PHP_INT_MAX';
						}

						return (
							<>
								{ row.priority }
								{ label && (
									<>
										<br/>
										<span className="qm-info">({ label })</span>
									</>
								) }
							</>
						);
					},
				},
				callback: {
					heading: __( 'Action', 'query-monitor' ),
					className: 'qm-nowrap',
					render: ( row ) => {
						if ( ! row.callback ) {
							return '';
						}

						let text: string;

						if ( 'closure' === row.callback.callback_type ) {
							text = sprintf(
								/* translators: A closure is an anonymous PHP function. 1: Line number, 2: File name */
								__( 'Closure on line %1$d of %2$s', 'query-monitor' ),
								row.callback.start_line ?? 0,
								row.callback.display_file ?? ''
							);
						} else if ( 'unknown_closure' === row.callback.callback_type ) {
							/* translators: A closure is an anonymous PHP function */
							text = __( 'Unknown closure', 'query-monitor' );
						} else {
							text = row.callback.name || row.callback.display_file || '';
						}

						if ( ! text ) {
							return '';
						}
						return (
							<SourceLocation
								text={ text }
								file={ row.callback.file || null }
								line={ row.callback.line || 0 }
								expanded
							/>
						);
					},
				},
				component: {
					heading: __( 'Component', 'query-monitor' ),
					render: ( row ) => row.component
						? <Component component={ row.component } />
						: null,
					filters: componentFilters.length ? {
						options: componentFilters,
						callback: ( row, value: string ) => componentFilterCallback( row.component, value ),
					} : undefined,
				},
			} }
			data={ rows }
			groupKey={ ( row ) => row.hookName }
		/>
	);
};
