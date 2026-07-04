import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { buildCountedFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import * as Utils from '../utils';
import { useContext } from 'preact/hooks';
import { __, _nx, sprintf } from '@wordpress/i18n';
import { Icon } from '../components/icon';
import { PanelMenuItem } from '../panels/panel-registry';

export const phpErrorsMenuClass = ( data: DataTypes['php_errors'] ): string[] => {
	const classes = new Set<string>();

	for ( const error of Object.values( data.errors ?? {} ) ) {
		if ( ! error.suppressed ) {
			classes.add( `qm-${ error.level }` );
		}
	}

	return Array.from( classes );
};

const phpErrorPluralLabels: Record<string, ( count: number ) => string> = {
	deprecated: ( count ) =>
		/* translators: %s: Number of deprecated PHP errors */
		_nx( '%s Deprecated', '%s Deprecated', count, 'PHP error level', 'query-monitor' ),
	strict: ( count ) =>
		/* translators: %s: Number of strict PHP errors */
		_nx( '%s Strict', '%s Stricts', count, 'PHP error level', 'query-monitor' ),
	notice: ( count ) =>
		/* translators: %s: Number of PHP notices */
		_nx( '%s Notice', '%s Notices', count, 'PHP error level', 'query-monitor' ),
	warning: ( count ) =>
		/* translators: %s: Number of PHP warnings */
		_nx( '%s Warning', '%s Warnings', count, 'PHP error level', 'query-monitor' ),
};

export const phpErrorsMenu = ( data: DataTypes['php_errors'] ): PanelMenuItem[] => {
	const errors = Object.values( data.errors ?? {} );

	if ( ! errors.length ) {
		return [];
	}

	const counts: Record<string, number> = {};

	for ( const error of errors ) {
		if ( ! error.suppressed ) {
			counts[ error.level ] = ( counts[ error.level ] ?? 0 ) + error.count;
		}
	}

	const parts: string[] = [];
	let total = 0;

	for ( const [ level, getLabel ] of Object.entries( phpErrorPluralLabels ) ) {
		const count = counts[ level ];

		if ( ! count ) {
			continue;
		}

		total += count;
		parts.push( sprintf( getLabel( count ), Utils.numberFormat( count ) ) );
	}

	const title = parts.length
		? sprintf(
			/* translators: %s: List of PHP error types */
			__( 'PHP Errors (%s)', 'query-monitor' ),
			/* translators: used between list items, there is a space after the comma */
			parts.reverse().join( __( ', ', 'query-monitor' ) ),
		)
		: __( 'PHP Errors', 'query-monitor' );

	const classes = phpErrorsMenuClass( data );

	return [
		{
			id: 'php_errors',
			panel: 'php_errors',
			title,
			warning_count: total,
			...( classes.length ? { classname: classes.join( ' ' ) } : {} ),
			nav: false,
		},
		{
			id: 'php_errors',
			panel: 'php_errors',
			title: __( 'PHP Errors', 'query-monitor' ),
			warning_count: Object.values( data.types ?? {} ).reduce( ( sum, value ) => sum + value, 0 ),
			adminBar: false,
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
	] );
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
