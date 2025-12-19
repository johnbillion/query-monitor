import clsx from 'clsx';
import {
	Caller,
	Component,
	Time,
	PanelContext,
} from 'qmi';
import {
	CallSite,
} from './caller';
import {
	Backtrace,
} from 'qmi/data-types';
import {
	PanelFooter,
} from './panel-footer';
import {
	__,
} from '@wordpress/i18n';

import * as React from 'react';

export type Col<TDataRow> = {
	className?: string;
	heading: string;
	render: ( row: TDataRow, i: number ) => ( React.ReactNode | string );
	filters?: ColFilters<TDataRow>;
	sorting?: ColSorting<TDataRow>;
	cellHasError?: ( row: TDataRow, i: number ) => boolean;
	wrap?: boolean;
}

interface Cols<TDataRow> {
	[ key: string ]: Col<TDataRow>;
}

export interface FilterOption {
	key: string;
	label: string;
}

interface ColFilters<TDataRow> {
	options: FilterOption[];
	callback: ( row: TDataRow, value: string ) => boolean;
}

interface ColSorting<TDataRow> {
	field: keyof TDataRow;
	default?: 'asc'|'desc';
}

export type TabularProps<TDataRow> = {
	cols: Cols<TDataRow>;
	data: TDataRow[];
	rowHasError?: ( row: TDataRow ) => boolean;
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
	callsite?: CallSite;
}

interface DataRowWithTime {
	ltime?: number;
}

const sortFilters = ( a: { label: string }, b: { label: string } ) => {
	return a.label.localeCompare(b.label);
};

const deriveFilters = <TDataRow,>(
	rows: TDataRow[],
	getKeyLabel: ( row: TDataRow ) => FilterOption | null
): FilterOption[] => {
	const seen = new Set<string>();
	const filters: FilterOption[] = [];

	for ( const row of rows ) {
		const result = getKeyLabel( row );
		if ( result && ! seen.has( result.key ) ) {
			seen.add( result.key );
			filters.push( result );
		}
	}

	filters.sort( sortFilters );
	return filters;
};

export const getComponentCol = <TDataRow extends DataRowWithTrace>( rows: TDataRow[] ) => {
	const filters = deriveFilters( rows, ( row ) => {
		if ( ! row.trace?.component ) {
			return null;
		}
		return {
			key: `${ row.trace.component.type }-${ row.trace.component.context }`,
			label: row.trace.component.name,
		};
	} );

	if ( filters.length > 1 ) {
		filters.unshift( {
			key: 'non-core',
			label: __( 'Non-WordPress Core', 'query-monitor' ),
		} );
	}

	const column: Col<DataRowWithTrace & TDataRow> = {
		heading: __( 'Component', 'query-monitor' ),
		render: ( row ) => <Component component={ row.trace.component } />,
		filters: {
			options: filters,
			callback: ( row, value: string ) => {
				if ( value === 'non-core' ) {
					return ( row.trace.component.context !== 'core' );
				}

				return ( `${row.trace.component.type}-${row.trace.component.context}` === value );
			},
		},
	};

	return column;
};

interface isSlowCallback {
	( row: DataRowWithTime, i: number ): boolean;
}

export const getTimeCol = <TDataRow extends DataRowWithTime>( _rows: TDataRow[], slow: isSlowCallback = () => false ) => {
	const column: Col<TDataRow> = {
		className: 'qm-num',
		heading: __( 'Time', 'query-monitor' ),
		render: ( row ) => <Time value={ row.ltime } />,
		cellHasError: ( row, i ) => slow && slow( row, i ),
		sorting: {
			field: 'ltime',
			default: 'desc',
		},
	};

	return column;
}

export const getCallerCol = <TDataRow extends DataRowWithTrace>( rows: TDataRow[] ) => {
	const filters = deriveFilters( rows, ( row ) => {
		// If callsite is present, use filename as the filter key/label
		if ( row.callsite ) {
			return { key: row.callsite.filename, label: row.callsite.filename };
		}
		if ( ! row.trace?.frames?.length ) {
			return null;
		}
		const frame = row.trace.frames[0];
		return { key: frame.id, label: frame.display };
	} );

	const column: Col<TDataRow> = {
		heading: __( 'Caller', 'query-monitor' ),
		render: ( row ) => <Caller trace={ row.trace } callsite={ row.callsite } defaultExpanded={ rows.length === 1 } />,
		className: 'qm-has-toggle',
		filters: filters.length ? {
			options: filters,
			callback: ( row, value: string ) => {
				// If callsite is present, filter by filename
				if ( row.callsite ) {
					return row.callsite.filename === value;
				}
				if ( ! row.trace?.frames?.length ) {
					return false;
				}
				return row.trace.frames[0].id === value;
			},
		} : undefined,
	};

	return column;
}

const countData = <TDataRow extends {}>( data: TDataRow[] ) => {
	return data.reduce( ( total, row ) => {
		if ( 'count' in row ) {
			return total += ( row.count as number );
		}

		return total += 1;
	}, 0 );
};

export const Table = <TDataRow extends {}>( { title, cols, data, rowHasError, id, footer, orderby = null, order = 'desc', children }: TableProps<TDataRow> ) => {
	const {
		filters,
		setFilter,
	} = React.useContext( PanelContext );
	const total = countData( data );
	const nonEmptyCols = Object.entries( cols ).filter( ( [ _key, value ] ) => ( value ? true : false ) );

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

	const count = countData( data );
	const [ sorting, _setSorting ] = React.useState( {
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
								className={ clsx( `qm-col-${key}`, col.className, {
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
						className={ clsx( {
							// @todo remove this in favour of using a warning or error property on row objects
							'qm-warn': rowHasError && rowHasError( row ),
						} ) }
					>
						{ nonEmptyCols.map( ( [ key, col ] ) => (
							<td
								key={ key }
								className={ clsx( `qm-cell-${key}`, col.className, {
									'qm-warn': col.cellHasError && col.cellHasError( row, i ),
									'qm-wrap': col.wrap,
								} ) }
							>
								{ col.render( row, i ) }
							</td>
						) ) }
					</tr>
				) ) }
			</tbody>
			{ footerFunc( {
				cols: Object.keys( cols ).length,
				count: count,
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
