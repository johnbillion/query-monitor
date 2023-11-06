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

export const HTTP = ( { data }: PanelProps<DataTypes['HTTP']> ) => {
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
					( row.args.blocking === false ? __( 'Non-blocking', 'query-monitor' ) : `${row.response.response.code} ${row.response.response.message}` )
				),
				filters: {
					options: Object.keys( data.types ).map( ( type ) => ( {
						key: type,
						label: type,
					} ) ),
					callback: ( row, value ) => {
						switch ( value ) {
							case 'non-blocking':
								return row.args.blocking === false;
							case 'error':
								return Utils.isWPError( row.response );
							default:
								return Utils.isWPError( row.response ) ? false : row.response.response.code.toString() === value;
						}
					},
				},
			},
			caller: getCallerCol( data.http ),
			component: getComponentCol( data.http, data.component_times ),
			timeout: {
				heading: __( 'Timeout', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => row.args.timeout,
			},
			time: getTimeCol( data.http ),
		} }
		data={ data.http }
		hasError={ ( row ) => Utils.isWPError( row.response ) }
	/>
};
