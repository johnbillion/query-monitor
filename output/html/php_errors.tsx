import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { buildCountedFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import { __ } from '@wordpress/i18n';

export const PHPErrors = ( { data }: PanelProps<DataTypes['php_errors']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.errors ) {
		return <EmptyPanel>
			<p>
				{ __( 'No errors logged.', 'query-monitor' ) }
			</p>
		</EmptyPanel>
	}

	const errors = Object.values( data.errors );
	const filterOptions = buildCountedFilters( errors, ( row ) => row.level, [
		{ key: 'warning', label: 'Warning' },
		{ key: 'notice', label: 'Notice' },
		{ key: 'strict', label: 'Strict' },
		{ key: 'deprecated', label: 'Deprecated' },
	] );

	return <TabularPanel
		title={ __( 'PHP Errors', 'query-monitor' ) }
		cols={{
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => {
					const label = row.suppressed
						? `${ row.level } (${ __( 'suppressed', 'query-monitor' ) })`
						: row.level;
					return row.level === 'warning'
						? <Warning>{ label }</Warning>
						: label;
				},
				filters: {
					options: [ filterOptions ],
					callback: ( row, filter ) => row.level === filter,
				},
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => ( row.message ),
			},
			caller: getCallerCol( errors, settings ),
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
