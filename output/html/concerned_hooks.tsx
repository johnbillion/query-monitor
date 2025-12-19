import {
	PanelProps,
	TabularPanel,
	EmptyPanel,
	FileName,
} from 'qmi';
import {
	AbstractData,
	ConcernedHook,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

type HookRow = ConcernedHook['actions'][number] & {
	name: string;
	type: 'action' | 'filter';
};

export const ConcernedHooks = ( { data }: PanelProps<AbstractData> ) => {
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

	return (
		<TabularPanel
			title={ title }
			cols={ {
				hook: {
					heading: __( 'Hook', 'query-monitor' ),
					render: ( row ) => <code>{ row.name }</code>,
				},
				type: {
					heading: __( 'Type', 'query-monitor' ),
					render: ( row ) => row.type,
				},
				priority: {
					heading: __( 'Priority', 'query-monitor' ),
					render: ( row ) => row.priority,
				},
				callback: {
					heading: __( 'Callback', 'query-monitor' ),
					render: ( row ) => {
						const text = row.callback.name || row.callback.display_file || '';
						if ( ! text ) {
							return '';
						}
						return (
							<FileName
								text={ text }
								file={ row.callback.file || '' }
								line={ row.callback.line || 0 }
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
		/>
	);
};
