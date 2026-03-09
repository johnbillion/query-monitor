import clsx from 'clsx';
import {
	Caller,
} from './caller';
import { Component } from './component';
import { Time } from './components/time';
import { Warning } from './components/warning';
import { PanelContext } from './contexts/panel-context';
import {
	Backtrace,
	Component as ComponentType,
} from './data-types';
import {
	PanelFooter,
} from './panels/panel-footer';
import { StackCaller } from './stack-caller';
import {
	__,
} from '@wordpress/i18n';

import { type ComponentChildren } from 'preact';
import { useContext, useState } from 'preact/hooks';

export type Col<TDataRow> = {
	className?: string | ( ( row: TDataRow, i: number ) => string );
	heading: string;
	render: ( row: TDataRow, i: number ) => ( ComponentChildren | string );
	filters?: ColFilters<TDataRow>;
	sorting?: ColSorting<TDataRow>;
	cellHasError?: ( row: TDataRow, i: number ) => boolean;
	wrap?: boolean;
	rowSpan?: ( row: TDataRow, i: number, data: TDataRow[] ) => number;
}

export interface Cols<TDataRow> {
	[ key: string ]: Col<TDataRow> | null | false;
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

export type TabularProps<TDataRow, TCols extends Cols<TDataRow> = Cols<TDataRow>> = {
	cols: TCols;
	data: TDataRow[];
	rowHasError?: ( row: TDataRow ) => boolean;
	footer?: ( args: { cols: number, count: number, total: number, data: TDataRow[] } ) => ComponentChildren;
	warning?: () => ComponentChildren;
	orderby?: keyof TCols & string;
	order?: 'asc'|'desc';
	groupKey?: ( row: TDataRow ) => string;
}

interface TableProps<TDataRow, TCols extends Cols<TDataRow> = Cols<TDataRow>> extends TabularProps<TDataRow, TCols> {
	id: string;
	title: string;
	children?: ComponentChildren;
}

interface DataRowWithTrace {
	trace?: Backtrace | null;
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

export const componentFilterCallback = ( component: ComponentType | null | undefined, value: string ): boolean => {
	if ( ! component ) {
		return false;
	}

	if ( value === 'non-core' ) {
		return component.context !== 'core';
	}

	return `${ component.type }-${ component.context }` === value;
};

export const deriveComponentFilters = <TDataRow,>(
	rows: TDataRow[],
	getComponent: ( row: TDataRow ) => ComponentType | null | undefined
): FilterOption[] => {
	const filters = deriveFilters( rows, ( row ) => {
		const component = getComponent( row );
		if ( ! component ) {
			return null;
		}
		return {
			key: `${ component.type }-${ component.context }`,
			label: component.name,
		};
	} );

	if ( filters.length > 1 ) {
		filters.unshift( {
			key: 'non-core',
			label: __( 'Non-WordPress Core', 'query-monitor' ),
		} );
	}

	return filters;
};

export const getComponentCol = <TDataRow extends DataRowWithTrace>( rows: TDataRow[] ) => {
	const filters = deriveComponentFilters( rows, ( row ) => row.trace?.component );

	const column: Col<DataRowWithTrace & TDataRow> = {
		heading: __( 'Component', 'query-monitor' ),
		render: ( row ) => <Component component={ row.trace?.component } />,
		filters: {
			options: filters,
			callback: ( row, value: string ) => componentFilterCallback( row.trace?.component, value ),
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
		render: ( row ) => <Time value={ row.ltime ?? 0 } />,
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
		if ( row.trace?.callsite ) {
			return { key: row.trace.callsite.filename, label: row.trace.callsite.filename };
		}
		if ( ! row.trace?.frames?.length ) {
			return null;
		}
		const frame = row.trace.frames[0];
		return { key: frame.id, label: frame.display };
	} );

	const column: Col<TDataRow> = {
		heading: __( 'Caller', 'query-monitor' ),
		render: ( row ) => <Caller trace={ row.trace } defaultExpanded={ rows.length === 1 } />,
		className: 'qm-has-toggle',
		filters: filters.length ? {
			options: filters,
			callback: ( row, value: string ) => {
				if ( row.trace?.callsite ) {
					return row.trace.callsite.filename === value;
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

interface DataRowWithStack {
	stack?: string[];
}

export const getStackCol = <TDataRow extends DataRowWithStack>( rows: TDataRow[] ) => {
	const filters = deriveFilters( rows, ( row ) => {
		if ( ! row.stack?.length ) {
			return null;
		}
		return { key: row.stack[0], label: row.stack[0] };
	} );

	const column: Col<TDataRow> = {
		heading: __( 'Caller', 'query-monitor' ),
		render: ( row ) => <StackCaller stack={ row.stack } defaultExpanded={ rows.length === 1 } />,
		className: 'qm-has-toggle',
		filters: filters.length ? {
			options: filters,
			callback: ( row, value: string ) => {
				if ( ! row.stack?.length ) {
					return false;
				}
				return row.stack[0] === value;
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

export const Table = <TDataRow extends {}, TCols extends Cols<TDataRow> = Cols<TDataRow>>( { title, cols, data, rowHasError, id, footer, warning, orderby, order = 'desc', groupKey, children }: TableProps<TDataRow, TCols> ) => {
	const {
		filters,
		setFilter,
	} = useContext( PanelContext );
	const total = countData( data );
	const nonEmptyCols = Object.entries( cols ).filter( ( entry ): entry is [string, Col<TDataRow>] => ( entry[1] ? true : false ) );

	for ( const [ filterName, filterValue ] of Object.entries( filters ) ) {
		if ( ! ( filterName in cols ) ) {
			continue;
		}

		const col = cols[ filterName ];
		if ( ! col || ! col.filters ) {
			continue;
		}

		const colFilters = col.filters;

		if ( ! colFilters.options.filter( ( option ) => ( option.key === filterValue ) ).length ) {
			continue;
		}

		if ( groupKey ) {
			// When groupKey is provided, keep all rows in a group if any row matches
			const matchingGroups = new Set<string>();
			for ( const row of data ) {
				if ( colFilters.callback( row, filterValue ) ) {
					matchingGroups.add( groupKey( row ) );
				}
			}
			data = data.filter( ( row ) => matchingGroups.has( groupKey( row ) ) );
		} else {
			data = data.filter( ( row ) => colFilters.callback( row, filterValue ) );
		}
	}

	const count = countData( data );
	const [ sorting, _setSorting ] = useState( {
		orderby,
		order,
	} );

	if ( sorting.orderby ) {
		const sortCol = cols[ sorting.orderby ];
		const sortField = sortCol && sortCol.sorting?.field;

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
								className={ clsx( `qm-col-${key}`, typeof col.className === 'string' ? col.className : undefined, {
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
											value={ filterValue }
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
				{ warning && (
					<tr className="qm-warn">
						<td colSpan={ nonEmptyCols.length }>
							<Warning>
								{ warning() }
							</Warning>
						</td>
					</tr>
				) }
				{ data.map( ( row, i ) => (
					<tr
						key={ i }
						className={ clsx( {
							'qm-warn': rowHasError && rowHasError( row ),
						} ) }
					>
						{ nonEmptyCols.map( ( [ key, col ] ) => {
							const span = col.rowSpan ? col.rowSpan( row, i, data ) : 1;

							// Skip this cell if a previous row is spanning into it
							if ( span === 0 ) {
								return null;
							}

							return (
								<td
									key={ key }
									className={ clsx( `qm-cell-${key}`, typeof col.className === 'function' ? col.className( row, i ) : col.className, {
										'qm-warn': col.cellHasError && col.cellHasError( row, i ),
										'qm-wrap': col.wrap,
									} ) }
									rowSpan={ span > 1 ? span : undefined }
								>
									{ col.render( row, i ) }
								</td>
							);
						} ) }
					</tr>
				) ) }
			</tbody>
			{ footerFunc( {
				cols: nonEmptyCols.length,
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
