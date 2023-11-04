import * as classNames from 'classnames';
import {
	Caller,
	Component,
	Time,
	PanelContext,
} from 'qmi';
import {
	Backtrace,
} from 'qmi/data-types';
import {
	PanelFooter,
} from './panel-footer';

import * as React from 'react';

export interface Col<T> {
	className?: string;
	heading: string;
	render?: ( row: T, i: number, col: Col<T> ) => ( React.ReactNode | string );
	filters?: () => {
		key: string;
		label: string;
	}[];
	filterCallback?: ( row: T, value: string ) => boolean;
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
		case 'component':
			return (
				<Component component={ item.trace.component } />
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

export const sortFilters = ( a: { label: string }, b: { label: string } ) => {
	if ( a.label < b.label ) {
		return -1;
	}

	if ( a.label > b.label ) {
		return 1;
	}

	return 0;
};

export const Table = <T extends unknown>( { title, cols, data, hasError, id, footer }: TableProps<T> ) => {
	const {
		filters,
		setFilter,
	} = React.useContext( PanelContext );

	const currentFilters = Object.entries( filters );
	let filteredData = [ ...data ];

	if ( currentFilters.length ) {
		filteredData = filteredData.filter( ( row ) => {
			for ( const [ filterName, filterValue ] of currentFilters ) {
				if ( ! ( filterName in cols ) ) {
					continue;
				}

				if ( ! cols[ filterName ].filterCallback ) {
					continue;
				}

				if ( ! cols[ filterName ].filterCallback( row, filterValue ) ) {
					return false;
				}
			}

			return true;
		} );
	}

	return (
		<table>
			<caption>
				<h2 id={ id }>
					{ title }
				</h2>
			</caption>
			<thead>
				<tr>
					{ Object.entries( cols ).map( ( [ key, col ] ) => {
						const colFilters = col.filters ? col.filters() : [];
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
											onChange={ ( e ) => ( setFilter( key, e.currentTarget.value ) ) }
										>
											<option value="">All</option>
											{ colFilters.map( ( filter ) => (
												<option
													key={ filter.key }
													selected={ filterValue === filter.key }
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
				{ filteredData.map( ( row, i ) => (
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
					count={ filteredData.length }
					total={ data.length }
				/>
			) }
		</table>
	);
};
