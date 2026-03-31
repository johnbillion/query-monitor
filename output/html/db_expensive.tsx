import { TabularPanel } from '../panels/tabular-panel';
import * as Utils from '../utils';
import { Warning } from '../components/warning';
import { getCallerCol, getComponentCol, getTimeCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import {
	__,
} from '@wordpress/i18n';

export const DBExpensive = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	if ( ! data.expensive?.length || ! data.rows ) {
		return null;
	}

	const expensive = data.expensive;
	const rows = data.rows.filter( ( row, i ) => expensive.includes( i ) );

	return <TabularPanel
		title={ __( 'Slow Database Queries', 'query-monitor' ) }
		cols={ {
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
			},
			caller: getCallerCol( rows ),
			component: getComponentCol( rows ),
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
			time: getTimeCol( rows, () => true ),
		} }
		data={ rows }
		rowHasError={ ( row ) => Utils.isWPError( row.result ) }
	/>
};
