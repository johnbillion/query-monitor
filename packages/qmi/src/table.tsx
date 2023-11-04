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
	render?: ( row: T, i: number, col: Col<T> ) => ( React.ReactNode | string );
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

export const sortFilters = ( a: { label: string }, b: { label: string } ) => {
	if ( a.label < b.label ) {
		return -1;
	}

	if ( a.label > b.label ) {
		return 1;
	}

	return 0;
};

export const getComponentCol = <T extends RowWithTrace, U extends unknown>( component_times: AbstractData['component_times'], rows: U[] ) => {
	const column: Col<T & U> = {
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

const Cell = <T extends unknown>( { col, i, name, row }: CellProps<T> ) => {
	if ( col.render ) {
		return (
			<>
				{ col.render( row, i, col ) }
			</>
		);
	}

	const item = row as KnownData;

	switch ( name ) {
		case 'caller':
			return (
				<Caller trace={ item.trace } />
			);
			break;
		case 'ltime':
			return (
				<Time value={ item.ltime } />
			);
			break;
		}

	return (
		<></>
	);
};

export const Table = <T extends unknown>( { title, cols, data, hasError, id, footer }: TableProps<T> ) => {
	const {
		filters,
		setFilter,
	} = React.useContext( PanelContext );
	const total = data.length;

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
					{ Object.entries( cols ).map( ( [ key, col ] ) => {
						const colFilters = col.filters ? col.filters.options : [];
						const filterValue = ( key in filters ) ? filters[ key ] : '';

						return (
							<th
								key={ key }
								className={ classNames( col.className, {
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
						{ Object.entries( cols ).map( ( [ name, col ] ) => (
							<td className={ `qm-col-${name}` }>
								<Cell
									col={ col }
									i={ i }
									name={ name }
									row={ row }
								/>
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
