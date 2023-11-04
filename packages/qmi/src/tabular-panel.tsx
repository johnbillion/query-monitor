import {
	Table,
	KnownColumns,
	Col,
} from './table';

import * as React from 'react';

interface TabularProps<T> {
	title: string;
	cols: {
		[ key: string ]: Col<T>;
	};
	data: T[];
	hasError?: ( row: T ) => boolean;
	footer?: React.ReactNode;
}

export const TabularPanel = <T extends KnownColumns>( { cols, data, footer, title }: TabularProps<T> ) => (
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
