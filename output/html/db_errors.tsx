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
} from '@wordpress/i18n';

export const DBErrors = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	if ( ! data.errors?.length ) {
		return null;
	}

	return <TabularPanel
		title={ __( 'Database Errors', 'query-monitor' ) }
		cols={ {
			sql: {
				heading: __( 'Query', 'query-monitor' ),
				render: ( row ) => (
					<>
						<code>
							{ Utils.formatSQL( row.sql ) }
						</code>
						<br />
						<br />
						<Warning>
							{ Utils.getErrorMessage( row.result ) }
						</Warning>
					</>
				),
			},
			caller: getCallerCol( data.rows ),
			component: getComponentCol( data.rows, data.component_times ),
		} }
		data={ data.rows.filter( ( row, i ) => data.errors.includes( i ) ) }
		rowHasError={ ( row ) => true }
	/>
};
