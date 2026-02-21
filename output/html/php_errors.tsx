import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { getCallerCol, getComponentCol } from '../table';
import { getFilterLabel } from '../utils';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { __ } from '@wordpress/i18n';

export const PHPErrors = ( { data }: PanelProps<DataTypes['php_errors']> ) => {
	if ( ! data.errors ) {
		return <EmptyPanel>
			<p>
				{ __( 'No errors logged.', 'query-monitor' ) }
			</p>
		</EmptyPanel>
	}

	const errors = Object.values( data.errors );
	const counts = errors.reduce( ( acc, error ) => {
		acc[ error.level ] = ( acc[ error.level ] || 0 ) + 1;
		return acc;
	}, {} as Record<string, number> );

	const filterOptions = [
		{
			label: getFilterLabel( 'Warning', counts.warning ),
			key: 'warning',
		},
		{
			label: getFilterLabel( 'Notice', counts.notice ),
			key: 'notice',
		},
		{
			label: getFilterLabel( 'Strict', counts.strict ),
			key: 'strict',
		},
		{
			label: getFilterLabel( 'Deprecated', counts.deprecated ),
			key: 'deprecated',
		},
	];

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
					options: filterOptions,
					callback: ( row, filter ) => row.level === filter,
				},
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => ( row.message ),
			},
			caller: getCallerCol( errors ),
			count: {
				className: 'qm-num',
				heading: __( 'Count', 'query-monitor' ),
				render: ( row ) => ( row.count ),
			},
			component: getComponentCol( errors ),
		}}
		rowHasError={ ( row ) => ( row.level === 'warning' ) }
		data={ errors }
	/>;
};
