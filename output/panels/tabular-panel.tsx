import { PanelContext } from '../contexts/panel-context';
import {
	Table,
	TabularProps,
} from '../table';

import { type ComponentChildren } from 'preact';
import { useContext } from 'preact/hooks';

interface Props<TDataRow> extends TabularProps<TDataRow> {
	title: string;
	children?: ComponentChildren;
}

export const TabularPanel = <TDataRow extends {}>( { cols, data, footer, warning, orderby, order = 'desc', rowHasError, title, groupKey, header, children }: Props<TDataRow> ) => {
	const {
		id,
	} = useContext( PanelContext );

	return (
		<div
			aria-labelledby="qm-panel-table"
			className="qm qm-panel-show"
			id={ `qm-${id}` }
			role="tabpanel"
			// Allows programmatic focus when switching tabs.
			tabIndex={ -1 }
		>
			<Table
				cols={ cols }
				data={ data }
				id="qm-panel-table"
				footer={ footer }
				warning={ warning }
				rowHasError={ rowHasError }
				title={ title }
				orderby={ orderby }
				order={ order }
				groupKey={ groupKey }
				header={ header }
			>
				{ children }
			</Table>
		</div>
	);
};
