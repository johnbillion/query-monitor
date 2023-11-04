import {
	Caller,
	iPanelProps,
	EmptyPanel,
	Component,
	TabularPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export default ( { data }: iPanelProps<DataTypes['Multisite']> ) => {
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
			caller: {
				heading: __( 'Caller', 'query-monitor' ),
			},
			component: {
				heading: __( 'Component', 'query-monitor' ),
			},
		}}
		data={ data.switches }
	/>
};
