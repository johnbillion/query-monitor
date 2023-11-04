import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	Utils,
	Warning,
	getComponentCol,
	getTimeCol,
	getCallerCol,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export default ( { data }: PanelProps<DataTypes['HTTP']> ) => {
	if ( ! data.http ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No HTTP API calls.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'HTTP API', 'query-monitor' ) }
		cols={ {
			method: {
				heading: __( 'Method', 'query-monitor' ),
				render: ( row ) => row.args.method,
			},
			url: {
				heading: __( 'URL', 'query-monitor' ),
				render: ( row ) => Utils.formatURL( row.url ),
			},
			status: {
				heading: __( 'Status', 'query-monitor' ),
				render: ( row ) => Utils.isWPError( row.response ) ? (
					<Warning>
						{ sprintf(
							__( 'Error: %s', 'query-monitor' ),
							Utils.getErrorMessage( row.response )
						) }
					</Warning>
				) : (
					`${row.response.response.code} ${row.response.response.message}`
				),
			},
			...getCallerCol( data.http ),
			...getComponentCol( data.http, data.component_times ),
			timeout: {
				heading: __( 'Timeout', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => row.args.timeout,
			},
			...getTimeCol( data.http ),
		} }
		data={ data.http }
		hasError={ ( row ) => Utils.isWPError( row.response ) }
	/>
};
