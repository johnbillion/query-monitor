import { iQM_i18n } from 'qmi';
import * as React from 'react';

interface iPanelFooterProps {
	cols: number;
	count: number;
	children?: React.ReactNode;
}

import {
	__,
} from '@wordpress/i18n';

declare const QM_i18n: iQM_i18n;

export const PanelFooter = ( { children, cols, count }: iPanelFooterProps ) => (
	<tfoot>
		<tr>
			<td colSpan={ cols }>
				{ __( 'Total:', 'query-monitor' ) }
				&nbsp;
				<span className="qm-items-number">{ QM_i18n.number_format( count ) }</span>
			</td>
			{ children }
		</tr>
	</tfoot>
);
