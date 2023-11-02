import {
	PanelFooter,
} from './panel-footer';

import * as React from 'react';

interface Col<T> {
	className?: string;
	heading: string;
	render: ( row: T, i: number, col: Col<T> ) => React.ReactNode;
}

interface TableProps<T> {
	id: string;
	title: string;
	cols: {
		[ key: string ]: Col<T>;
	};
	data: T[];
	footer?: React.ReactNode;
}

export const Table = <T extends unknown>( { title, cols, data, id, footer }: TableProps<T> ) => (
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
				<tr key={ i }>
					{ Object.entries( cols ).map( ( [ name, col ] ) => (
						<td className={ `qm-col-${name}` }>
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
			/>
		) }
	</table>
);
