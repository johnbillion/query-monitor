import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	getCallerCol,
	getComponentCol
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export const Multisite = ( { data }: PanelProps<DataTypes['Multisite']> ) => {
	if ( ! data.switches.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'Multisite', 'query-monitor' ) }
		cols={ {
			function: {
				heading: __( 'Function', 'query-monitor' ),
				render: ( row ) => (
					<code>
						{ row.to ? (
							sprintf(
								'switch_to_blog(%d)',
								row.new
							)
						) : (
							'restore_current_blog()'
						) }
					</code>
				),
			},
			site: {
				heading: __( 'Site Switch', 'query-monitor' ),
				render: ( row ) => (
					<code>
						@todo
					</code>
				),
			},
			caller: getCallerCol( data.switches ),
			component: getComponentCol( data.switches, data.component_times ),
		}}
		data={ data.switches }
	/>
};
