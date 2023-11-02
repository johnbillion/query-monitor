import {
	PanelFooter,
} from 'qmi';

import * as React from 'react';

interface Col<T> {
	className?: string;
	heading: string;
	render: ( row: T, i: number, col: Col<T> ) => React.ReactNode;
}

interface Props<T> {
	title?: string;
	cols: {
		[ key: string ]: Col<T>;
	};
	data: T[];
	footer?: React.ReactNode;
}

export const PanelTable = <T extends unknown>( { title, cols, data, footer }: Props<T> ) => (
	<div
		aria-labelledby="qm-panel-title"
		className="qm qm-panel-show"
		role="tabpanel"
		tabIndex={ -1 }
	>
		<table>
			<caption>
				<h2 id="qm-panel-title">
					{ title ?? '@TODO' }
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
	</div>
);
