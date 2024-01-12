import {
	PanelProps,
	TabularPanel,
	Time,
	Utils,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	_n,
	sprintf,
} from '@wordpress/i18n';

export const DBDupes = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	if ( ! data.dupes.length ) {
		return null;
	}

	return <TabularPanel
		title={ __( 'Duplicate Queries', 'query-monitor' ) }
		cols={ {
			sql: {
				heading: __( 'Query', 'query-monitor' ),
				render: ( row ) => (
					<code>
						{ Utils.formatSQL( row.query ) }
					</code>
				),
			},
			count: {
				heading: __( 'Count', 'query-monitor' ),
				render: ( row ) => ( row.count ),
			},
			time: {
				className: 'qm-num',
				heading: __( 'Time', 'query-monitor' ),
				render: ( row ) => <Time value={ row.ltime } />,
			},
			callers: {
				heading: __( 'Callers', 'query-monitor' ),
				render: ( row ) => (
					Object.entries( row.callers ).map( ( [ caller, calls ] ) => (
						<>
							<code>{ caller }</code>
							<br/>
							<span className="qm-info qm-supplemental">
								{ sprintf(
									_n( '%s call', '%s calls', calls, 'query-monitor' ),
									calls
								) }
							</span>
							<br/>
						</>
					) )
				),
			},
			components: {
				heading: __( 'Components', 'query-monitor' ),
				render: ( row ) => (
					Object.entries( row.components ).map( ( [ component, calls ] ) => (
						<>
							{ component }
							<br/>
							<span className="qm-info qm-supplemental">
								{ sprintf(
									_n( '%s call', '%s calls', calls, 'query-monitor' ),
									calls
								) }
							</span>
							<br/>
						</>
					) )
				),
			},
			sources: {
				heading: __( 'Potential Troublemakers', 'query-monitor' ),
				render: ( row ) => (
					Object.entries( row.sources ).map( ( [ source, calls ] ) => (
						<>
							<code>{ source }</code>
							<br/>
							<span className="qm-info qm-supplemental">
								{ sprintf(
									_n( '%s call', '%s calls', calls, 'query-monitor' ),
									calls
								) }
							</span>
							<br/>
						</>
					) )
				),
			},
		} }
		data={ data.dupes }
	/>
};
