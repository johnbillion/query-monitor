import {
	PanelProps,
	TabularPanel,
	Warning,
	EmptyPanel,
	getCallerCol,
	getComponentCol
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export const PHPErrors = ( { data }: PanelProps<DataTypes['PHP_Errors']> ) => {
	if ( ! data.errors ) {
		return <EmptyPanel>
			<p>
				{ __( 'No errors logged.', 'query-monitor' ) }
			</p>
		</EmptyPanel>
	}

	return <TabularPanel
		title={ __( 'PHP Errors', 'query-monitor' ) }
		cols={{
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ row.level === 'warning' && ( <Warning /> ) }
						{ row.level }
						{ row.suppressed && (
							<>
								&nbsp;({ __( 'suppressed', 'query-monitor' ) })
							</>
						) }
					</>
				),
				filters: {
					options: Object.keys( data.types ).map( ( level ) => ( {
						key: level,
						label: level,
					} ) ),
					callback: ( row, filter ) => row.level === filter,
				},
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => ( row.message ),
			},
			caller: getCallerCol( Object.values( data.errors ) ),
			count: {
				className: 'qm-num',
				heading: __( 'Count', 'query-monitor' ),
				render: ( row ) => ( row.count ),
			},
			component: getComponentCol( Object.values( data.errors ), data.component_times ),
		}}
		rowHasError={ ( row ) => ( row.level === 'warning' ) }
		data={ Object.values( data.errors ) }
	/>;
};
