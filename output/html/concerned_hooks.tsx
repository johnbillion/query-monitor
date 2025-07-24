import {
	PanelProps,
	TabularPanel,
	EmptyPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface HookRow {
	name: string;
	type: 'action' | 'filter';
	priority: number;
	callback: {
		name?: string;
		file?: string | false;
		line?: number | false;
		component?: any;
	};
	component?: any;
	trace?: any;
}

export const ConcernedHooks = ( { enabled, data }: PanelProps<DataTypes['caps']> ) => {
	const concernedActions = data.concerned_actions;
	const concernedFilters = data.concerned_filters;

	// Combine both actions and filters into a single array
	const allHooks: HookRow[] = [];

	concernedActions && Object.keys( concernedActions ).forEach( ( hookName: string ) => {
		allHooks.push( {
			name: hookName,
			type: 'action',
			priority: 10, // Default priority since we don't have specific callback info
			callback: {
				name: __( 'Related to this panel', 'query-monitor' ),
			},
		} );
	} );

	concernedFilters && Object.keys( concernedFilters ).forEach( ( hookName: string ) => {
		allHooks.push( {
			name: hookName,
			type: 'filter',
			priority: 10, // Default priority since we don't have specific callback info
			callback: {
				name: __( 'Related to this panel', 'query-monitor' ),
			},
		} );
	} );

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
					render: ( row ) => 'TBD',
				},
				callback: {
					heading: __( 'Callback', 'query-monitor' ),
					render: ( row ) => 'TBD',
				},
				component: {
					heading: __( 'Component', 'query-monitor' ),
					render: ( row ) => 'TBD',
				},
			} }
			data={ allHooks }
		/>
	);
};
