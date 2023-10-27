import { iQM_i18n } from 'qmi';
import * as React from 'react';

interface iPanelFooterProps {
	cols: number;
	label: string;
	count: number;
	children?: React.ReactNode;
}

declare const QM_i18n: iQM_i18n;

export const PanelFooter = ( { children, cols, count, label }: iPanelFooterProps ) => (
			<tfoot>
				<tr>
					<td colSpan={ cols }>
						{ label }
						&nbsp;
						<span className="qm-items-number">{ QM_i18n.number_format( count ) }</span>
					</td>
					{ children }
				</tr>
			</tfoot>
		);
