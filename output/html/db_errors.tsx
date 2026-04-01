import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import * as Utils from '../utils';
import { Warning } from '../components/warning';
import { getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import {
	__,
} from '@wordpress/i18n';

export const DBErrors = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.errors?.length ) {
		return null;
	}

	const errors = ( data.rows ?? [] ).filter( ( row, i ) => data.errors.includes( i ) );

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
			caller: getCallerCol( errors, settings ),
			component: getComponentCol( errors ),
		} }
		data={ errors }
		rowHasError={ () => true }
	/>
};
