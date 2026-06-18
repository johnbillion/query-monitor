import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { buildCountedFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import { __ } from '@wordpress/i18n';
import { Icon } from '../components/icon';

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
	const showWarning = ( row: DataTypes['php_errors']['errors'][string] ) => ( row.level === 'warning' && ! row.suppressed );

	return <TabularPanel
		title={ __( 'PHP Errors', 'query-monitor' ) }
		cols={{
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => {
					const level = row.level.charAt( 0 ).toUpperCase() + row.level.slice( 1 );
					const label = row.suppressed
						? `${ level } (${ __( 'suppressed', 'query-monitor' ) })`
						: level;

					if ( showWarning( row ) ) {
						return <Warning>{ label }</Warning>;
					}

					return (
						<>
							<Icon name="blank"/>
							{ label }
						</>
					);
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
		rowHasError={ showWarning }
		data={ errors }
	/>;
};
