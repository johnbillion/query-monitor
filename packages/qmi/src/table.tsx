import * as classNames from 'classnames';
import {
	Caller,
	Component,
	Time,
	Utils,
} from 'qmi';
import {
	Backtrace,
	Component as QM_Component,
} from 'qmi/data-types';
import {
	PanelFooter,
} from './panel-footer';

import * as React from 'react';

export interface Col<T> {
	className?: string;
	heading: string;
	render?: ( row: T, i: number, col: Col<T> ) => ( React.ReactNode | string );
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

export interface KnownColumns {
	component?: QM_Component;
	trace?: Backtrace;
	ltime?: number;
	sql?: string;
}

const Cell = <T extends unknown>( { col, i, name, row }: CellProps<T> ) => {
	if ( col.render ) {
		return (
			<>
				{ col.render( row, i, col ) }
			</>
		);
	}

	const item = row as KnownColumns;

	switch ( name ) {
		case 'caller':
			return (
				<Caller trace={ item.trace } />
			);
			break;
		case 'component':
			return (
				<Component component={ item.component } />
			);
			break;
		case 'ltime':
			return (
				<Time value={ item.ltime } />
			);
			break;
		case 'sql':
			return (
				<code>
					{ Utils.formatSQL( item.sql ) }
				</code>
			);
			break;
		}

	return (
		<></>
	);
};

export const Table = <T extends unknown>( { title, cols, data, hasError, id, footer }: TableProps<T> ) => (
	<table>
		<caption>
			<h2 id={ id }>
				{ title }
			</h2>
		</caption>
		<thead>
			<tr>
				{ Object.entries( cols ).map( ( [ key, col ] ) => (
					<th
						key={ key }
						className={ col.className }
						role="columnheader"
						scope="col"
					>
						{ col.heading }
					</th>
				) ) }
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
			/>
		) }
	</table>
);
