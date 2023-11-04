import { iQM_i18n } from 'qmi';
import * as React from 'react';

interface iPanelFooterProps {
	cols: number;
	count: number;
	total?: number;
	children?: React.ReactNode;
}

import {
	__,
	sprintf,
} from '@wordpress/i18n';

declare const QM_i18n: iQM_i18n;

export const PanelFooter = ( { children, cols, count, total = count }: iPanelFooterProps ) => (
	<tfoot>
		<tr>
			<td colSpan={ cols }>
				{ __( 'Total:', 'query-monitor' ) }
				&nbsp;
				<span className="qm-items-number">
					{ ( total === count ) ? (
						QM_i18n.number_format( count )
					) : (
						sprintf(
							'%1$s / %2$s',
							QM_i18n.number_format( count ),
							QM_i18n.number_format( total )
						)
					) }
				</span>
			</td>
			{ children }
		</tr>
	</tfoot>
);
