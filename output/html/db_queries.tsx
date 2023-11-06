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

export const DBQueries = ( { data }: PanelProps<DataTypes['DB_Queries']> ) => {
	if ( ! data.rows?.length ) {
		return <EmptyPanel>
			<p>
				{ __( 'No queries! Nice work.', 'query-monitor' ) }
			</p>
		</EmptyPanel>
	}

	return <TabularPanel
		title={ __( 'Database Queries', 'query-monitor' ) }
		cols={ {
			i: {
				className: 'qm-num',
				heading: '#',
				render: ( row, i ) => ( i + 1 ),
			},
			sql: {
				heading: __( 'Query', 'query-monitor' ),
				render: ( row ) => (
					<>
						<code>
							{ Utils.formatSQL( row.sql ) }
						</code>
						{ Utils.isWPError( row.result ) && (
							<>
								<br />
								<br />
								<Warning>
									{ Utils.getErrorMessage( row.result ) }
								</Warning>
							</>
						) }
					</>
				),
				filters: {
					options: ( () => {
						const filters = Object.keys( data.types ).map( ( type ) => ( {
							key: type,
							label: type,
						} ) );

						if ( filters.length > 1 ) {
							filters.unshift( {
								key: 'non-select',
								label: __( 'Non-SELECT', 'query-monitor' ),
							} );
						}

						return filters;
					} )(),
					callback: ( row, value ) => {
						if ( value === 'non-select' ) {
							return ( row.type !== 'SELECT' );
						}

						return ( row.type === value );
					},
				},
			},
			caller: getCallerCol( data.rows ),
			component: getComponentCol( data.rows, data.component_times ),
			result: {
				className: 'qm-num',
				heading: __( 'Rows', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ ! Utils.isWPError( row.result ) && (
							row.result
						) }
					</>
				),
			},
			time: getTimeCol( data.rows ),
		} }
		data={ data.rows }
		hasError={ ( row ) => Utils.isWPError( row.result ) }
	/>
};
