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

export interface Col<T> {
	className?: string;
	heading: string;
	render: ( row: T, i: number, col: Col<T> ) => ( React.ReactNode | string );
	filters?: ColFilters<T>;
}

interface ColFilters<T> {
	options: {
		key: string;
		label: string;
	}[];
	callback: ( row: T, value: string ) => boolean;
}

interface TableProps<T> {
	id: string;
	title: string;
	cols: {
		[ key: string ]: Col<T>;
	};
	data: T[];
	hasError?: ( row: T ) => boolean;
	footer?: React.ReactNode;
}

interface CellProps<T> {
	col: Col<T>;
	i: number;
	name: string;
	row: T;
}

export interface KnownData {
	trace?: Backtrace;
	ltime?: number;
}

interface RowWithTrace {
	trace?: Backtrace;
}

interface RowWithTime {
	ltime?: number;
}

export const sortFilters = ( a: { label: string }, b: { label: string } ) => {
	if ( a.label < b.label ) {
		return -1;
	}

	if ( a.label > b.label ) {
		return 1;
	}

	return 0;
};

export const getComponentCol = <T extends unknown>( rows: T[], component_times: AbstractData['component_times'] ) => {
	const column: Col<RowWithTrace & T> = {
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

	return {
		component: column,
	};
};

export const getTimeCol = <T extends RowWithTime>( rows: T[] ) => {
	const column: Col<T> = {
		className: 'qm-num',
		heading: __( 'Time', 'query-monitor' ),
		render: ( row ) => <Time value={ row.ltime } />,
	};

	return {
		time: column,
	};
}

export const getCallerCol = <T extends RowWithTrace>( rows: T[] ) => {
	const column: Col<T> = {
		heading: __( 'Caller', 'query-monitor' ),
		render: ( row ) => <Caller trace={ row.trace } />,
	};

	return {
		caller: column,
	};
}

export const Table = <T extends unknown>( { title, cols, data, hasError, id, footer }: TableProps<T> ) => {
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

	return (
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
						key={ i }
						className={ classNames( {
							// @todo remove this in favour of using a warning or error property on row objects
							'qm-warn': hasError && hasError( row ),
						} ) }
					>
						{ nonEmptyCols.map( ( [ key, col ] ) => (
							<td className={ `qm-cell-${key} ${col.className}` }>
								{ col.render( row, i, col ) }
							</td>
						) ) }
					</tr>
				) ) }
			</tbody>
			{ footer ?? (
				<PanelFooter
					cols={ Object.keys( cols ).length }
					count={ data.length }
					total={ total }
				/>
			) }
		</table>
	);
};
