import { PanelContext } from './panel-context';
import {
	Table,
	TabularProps,
} from './table';

import * as React from 'react';

interface Props<TDataRow> extends TabularProps<TDataRow> {
	title: string;
	children?: React.ReactNode;
}

export const TabularPanel = <TDataRow extends {}>( { cols, data, footer, orderby = null, order = 'desc', rowHasError, title, children }: Props<TDataRow> ) => {
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
				rowHasError={ rowHasError }
				title={ title }
				orderby={ orderby }
				order={ order }
			>
				{ children }
			</Table>
		</div>
	);
};
