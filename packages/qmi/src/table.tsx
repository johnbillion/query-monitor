import * as classNames from 'classnames';
import {
	Caller,
	Component,
	Time,
	PanelContext,
} from 'qmi';
import {
	AbstractData,
	Backtrace,
} from 'qmi/data-types';
import {
	PanelFooter,
} from './panel-footer';
import {
	__,
} from '@wordpress/i18n';

import * as React from 'react';

export interface Col<TDataRow> {
	className?: string;
	heading: string;
	render: ( row: TDataRow, i: number, col: Col<TDataRow> ) => ( React.ReactNode | string );
	filters?: ColFilters<TDataRow>;
	sorting?: ColSorting<TDataRow>;
}

interface Cols<TDataRow> {
	[ key: string ]: Col<TDataRow>;
}

interface ColFilters<TDataRow> {
	options: {
		key: string;
		label: string;
	}[];
	callback: ( row: TDataRow, value: string ) => boolean;
}

interface ColSorting<TDataRow> {
	field: keyof TDataRow;
	default?: 'asc'|'desc';
}

export interface TabularProps<TDataRow> {
	cols: Cols<TDataRow>;
	data: TDataRow[];
	hasError?: ( row: TDataRow ) => boolean;
	footer?: ( args: { cols: number, count: number, total: number, data: TDataRow[] } ) => React.ReactNode;
	orderby?: string; // @todo restrict this to a key of the cols
	order?: 'asc'|'desc';
}

interface TableProps<TDataRow> extends TabularProps<TDataRow> {
	id: string;
	title: string;
	children?: React.ReactNode;
}

interface DataRowWithTrace {
	trace?: Backtrace;
}

interface DataRowWithTime {
	ltime?: number;
}

const sortFilters = ( a: { label: string }, b: { label: string } ) => {
	if ( a.label < b.label ) {
		return -1;
	}

	if ( a.label > b.label ) {
		return 1;
	}

	return 0;
};

export const getComponentCol = <TDataRow extends {}>( rows: TDataRow[], component_times: AbstractData['component_times'] ) => {
	const column: Col<DataRowWithTrace & TDataRow> = {
		heading: __( 'Component', 'query-monitor' ),
		render: ( row ) => <Component component={ row.trace.component } />,
		filters: {
			options: ( () => {
				const filters = Object.keys( component_times ).map( ( component ) => ( {
					key: component,
					label: component,
				} ) );

				filters.sort( sortFilters );

				if ( filters.length > 1 ) {
					filters.unshift( {
						key: 'non-core',
						label: __( 'Non-WordPress Core', 'query-monitor' ),
					} );
				}

				return filters;
			} )(),
			callback: ( row, value: string ) => {
				if ( value === 'non-core' ) {
					return ( row.trace.component.context !== 'core' );
				}

				return ( row.trace.component.name === value );
			},
		},
	};

	return column;
};

export const getTimeCol = <TDataRow extends DataRowWithTime>( rows: TDataRow[] ) => {
	const column: Col<TDataRow> = {
		className: 'qm-num',
		heading: __( 'Time', 'query-monitor' ),
		render: ( row ) => <Time value={ row.ltime } />,
		sorting: {
			field: 'ltime',
			default: 'desc',
		},
	};

	return column;
}

export const getCallerCol = <TDataRow extends DataRowWithTrace>( rows: TDataRow[] ) => {
	const column: Col<TDataRow> = {
		heading: __( 'Caller', 'query-monitor' ),
		render: ( row ) => <Caller trace={ row.trace } />,
	};

	return column;
}

export const Table = <TDataRow extends {}>( { title, cols, data, hasError, id, footer, orderby = null, order = 'desc', children }: TableProps<TDataRow> ) => {
	const {
		filters,
		setFilter,
	} = React.useContext( PanelContext );
	const total = data.length;
	const nonEmptyCols = Object.entries( cols ).filter( ( [ key, value ] ) => ( value ? true : false ) );

	for ( const [ filterName, filterValue ] of Object.entries( filters ) ) {
		if ( ! ( filterName in cols ) ) {
			continue;
		}

		if ( ! cols[ filterName ].filters ) {
			continue;
		}

		if ( ! cols[ filterName ].filters.options.filter( ( option ) => ( option.key === filterValue ) ).length ) {
			continue;
		}

		data = data.filter( ( row ) => cols[ filterName ].filters.callback( row, filterValue ) );
	}

	const [ sorting, setSorting ] = React.useState( {
		orderby,
		order,
	} );

	if ( sorting.orderby ) {
		const sortField = cols[ sorting.orderby ].sorting?.field;

		if ( sortField ) {
			data.sort( ( a, b ) => {
				if ( a[ sortField ] < b[ sortField ] ) {
					return sorting.order === 'asc' ? -1 : 1;
				}

				if ( a[ sortField ] > b[ sortField ] ) {
					return sorting.order === 'asc' ? 1 : -1;
				}

				return 0;
			} );
		}
	}

	const footerFunc = footer || PanelFooter;

	const table = (
		<table>
			<caption className="qm-screen-reader-text">
				<h2 id={ id }>
					{ title }
				</h2>
			</caption>
			<thead>
				<tr>
					{ nonEmptyCols.map( ( [ key, col ] ) => {
						const colFilters = col.filters ? col.filters.options : [];
						const filterValue = ( key in filters ) ? filters[ key ] : '';

						return (
							<th
								key={ key }
								className={ classNames( `qm-col-${key}`, col.className, {
									'qm-filterable-column': colFilters.length,
									'qm-filtered': filterValue !== '',
								} ) }
								role="columnheader"
								scope="col"
							>
								{ colFilters.length ? (
									<div className="qm-filter-container">
										<label htmlFor={ `qm-filter-${ key }` }>
											{ col.heading }
										</label>
										<select
											id={ `qm-filter-${ key }` }
											className="qm-filter"
											defaultValue={ filterValue }
											onChange={ ( e ) => ( setFilter( key, e.currentTarget.value ) ) }
										>
											<option value="">All</option>
											{ colFilters.map( ( filter ) => (
												<option
													key={ filter.key }
													value={ filter.key }
												>{ filter.label }</option>
											) ) }
										</select>
									</div>
								) : (
									col.heading
								) }
							</th>
						);
					} ) }
				</tr>
			</thead>
			<tbody>
				{ data.map( ( row, i ) => (
					<tr
						key={ i } // @todo nope
						className={ classNames( {
							// @todo remove this in favour of using a warning or error property on row objects
							'qm-warn': hasError && hasError( row ),
						} ) }
					>
						{ nonEmptyCols.map( ( [ key, col ] ) => (
							<td className={ classNames( `qm-cell-${key}`, col.className ) }>
								{ col.render( row, i, col ) }
							</td>
						) ) }
					</tr>
				) ) }
			</tbody>
			{ footerFunc( {
				cols: Object.keys( cols ).length,
				count: data.length,
				total: total,
				data: data,
			} ) }
		</table>
	);

	return (
		<>
			{ children && (
				<div className="qm-table-children">
					<div className="qm-boxed">
						{ children }
					</div>
				</div>
			) }
			{ table }
		</>
	);
};
