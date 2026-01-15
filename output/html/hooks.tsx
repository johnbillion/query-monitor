import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { Component } from '../component';
import { Warning } from '../components/warning';
import { DataTypes } from '../data-types';
import { componentFilterCallback, deriveComponentFilters } from '../table';
import { PanelProps } from '../types';
import * as React from 'react';

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
	callback: string | null;
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
					callback: action.callback.name ?? null,
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
					render: ( row ) => row.priority ?? '',
				},
				callback: {
					heading: __( 'Action', 'query-monitor' ),
					className: 'qm-nowrap',
					render: ( row ) => row.callback ? <code>{ row.callback }</code> : '',
				},
				component: {
					heading: __( 'Component', 'query-monitor' ),
					render: ( row ) => row.component
						? <Component component={ row.component } />
						: null,
					filters: {
						options: componentFilters,
						callback: ( row, value: string ) => componentFilterCallback( row.component, value ),
					},
				},
			} }
			data={ rows }
			groupKey={ ( row ) => row.hookName }
		/>
	);
};
