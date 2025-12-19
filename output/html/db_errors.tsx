import {
	PanelProps,
	TabularPanel,
	Utils,
	Warning,
	getComponentCol,
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

	const errors = data.rows.filter( ( row, i ) => data.errors.includes( i ) );

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
			caller: getCallerCol( errors ),
			component: getComponentCol( errors ),
		} }
		data={ errors }
		rowHasError={ () => true }
	/>
};
