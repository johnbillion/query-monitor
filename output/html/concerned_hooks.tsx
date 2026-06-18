import { TabularPanel } from '../panels/tabular-panel';
import { EmptyPanel } from '../panels/empty-panel';
import { SourceLocation } from '../components/source-location';
import { AbstractData, ConcernedHook } from '../data-types';
import { PanelProps } from '../types';
import * as Utils from '../utils';
import { MainContext } from '../contexts/main-context';
import { useContext } from 'preact/hooks';
import { __ } from '@wordpress/i18n';

type HookRow = ConcernedHook['actions'][number] & {
	name: string;
	type: 'action' | 'filter';
};

export const ConcernedHooks = ( { data }: PanelProps<AbstractData> ) => {
	const { settings } = useContext( MainContext );
	const concernedActions = data.concerned_actions;
	const concernedFilters = data.concerned_filters;

	// Combine both actions and filters into a single array, flattening the actions
	const allHooks: HookRow[] = [];

	const processHooks = ( hooks: typeof concernedActions ) => {
		if ( ! hooks ) {
			return;
		}

		Object.values( hooks ).forEach( ( hook ) => {
			hook.actions.forEach( ( action ) => {
				allHooks.push( {
					...action,
					name: hook.name,
					type: hook.type,
				} );
			} );
		} );
	};

	processHooks( concernedActions );
	processHooks( concernedFilters );

	if ( allHooks.length === 0 ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No hooks in use.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	const title = __( 'Related Hooks with Filters or Actions Attached', 'query-monitor' );

	const rowSpanByName = ( row: HookRow, i: number, rows: HookRow[] ): number => {
		if ( i > 0 && rows[ i - 1 ].name === row.name ) {
			return 0;
		}
		let count = 1;
		while ( i + count < rows.length && rows[ i + count ].name === row.name ) {
			count++;
		}
		return count;
	};

	return (
		<TabularPanel
			title={ title }
			cols={ {
				hook: {
					heading: __( 'Hook', 'query-monitor' ),
					render: ( row ) => (
						<span className="qm-sticky">
							<code>{ row.name }</code>
						</span>
					),
					rowSpan: rowSpanByName,
				},
				priority: {
					heading: __( 'Priority', 'query-monitor' ),
					className: 'qm-num',
					render: ( row ) => row.priority,
				},
				callback: {
					heading: __( 'Callback', 'query-monitor' ),
					className: 'qm-nowrap',
					render: ( row ) => {
						const text = Utils.getCallbackName( row.callback, settings );
						if ( ! text ) {
							return '';
						}
						return (
							<SourceLocation
								text={ text }
								file={ row.callback.file || '' }
								line={ row.callback.line || 0 }
								expanded
							/>
						);
					},
				},
				component: {
					heading: __( 'Component', 'query-monitor' ),
					render: ( row ) => row.callback.component?.name || '',
				},
			} }
			data={ allHooks }
			groupKey={ ( row ) => row.name }
		/>
	);
};
