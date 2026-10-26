import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { buildCountedFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import * as Utils from '../utils';
import { useContext } from 'preact/hooks';
import { __, _nx } from '@wordpress/i18n';
import { Icon } from '../components/icon';
import { PanelMenuItem } from '../panels/panel-registry';

export const phpErrorsMenu = ( data: DataTypes['php_errors'] ): PanelMenuItem[] => {
	const errors = Object.values( data.errors ?? {} );

	if ( ! errors.length ) {
		return [];
	}

	let notice_count = 0;
	let warning_count = 0;

	for ( const error of errors ) {
		if ( error.suppressed ) {
			continue;
		}

		if ( error.level == 'warning' ) {
			warning_count += error.count;
		} else {
			notice_count += error.count;
		}
	}

	return [
		{
			id: 'php_errors',
			panel: 'php_errors',
			title: __( 'PHP Errors', 'query-monitor' ),
			notice_count: ( notice_count || null ),
			warning_count: ( warning_count || null ),
		},
	];
};

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
	], ( row ) => row.count );
	const showWarning = Utils.phpErrorHasError;

	return <TabularPanel
		title={ __( 'PHP Errors', 'query-monitor' ) }
		cols={{
			level: {
				heading: __( 'Level', 'query-monitor' ),
				className: 'qm-nowrap',
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
