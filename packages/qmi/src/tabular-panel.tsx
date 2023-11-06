import { PanelContext } from './panel-context';
import {
	Table,
	TabularProps,
} from './table';

import * as React from 'react';

export interface Props<T> extends TabularProps<T> {
	title: string;
	children?: React.ReactNode;
}

export const TabularPanel = <T extends {}>( { cols, data, footer, hasError, title, children }: Props<T> ) => {
	const {
		id,
	} = React.useContext( PanelContext );

	return (
		<div
			aria-labelledby="qm-panel-title"
			className="qm qm-panel-show"
			id={ `qm-${id}` }
			role="tabpanel"
			tabIndex={ -1 }
		>
			<Table
				cols={ cols }
				data={ data }
				id="qm-panel-table"
				footer={ footer }
				hasError={ hasError }
				title={ title }
			>
				{ children }
			</Table>
		</div>
	);
};
