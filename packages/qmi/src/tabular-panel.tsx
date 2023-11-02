import {
	Table,
} from './table';

import * as React from 'react';

interface Col<T> {
	className?: string;
	heading: string;
	render: ( row: T, i: number, col: Col<T> ) => React.ReactNode;
}

interface TabularProps<T> {
	title: string;
	cols: {
		[ key: string ]: Col<T>;
	};
	data: T[];
	footer?: React.ReactNode;
}

export const TabularPanel = <T extends unknown>( { cols, data, footer, title }: TabularProps<T> ) => (
	<div
		aria-labelledby="qm-panel-title"
		className="qm qm-panel-show"
		role="tabpanel"
		tabIndex={ -1 }
	>
		<Table
			cols={ cols }
			data={ data }
			id="qm-panel-table"
			footer={ footer }
			title={ title }
		/>
	</div>
);
