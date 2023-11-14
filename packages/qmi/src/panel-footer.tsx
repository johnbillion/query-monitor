import { Utils } from 'qmi';
import * as React from 'react';

interface Props {
	cols: number;
	count: number;
	total: number;
	children?: React.ReactNode;
}

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export const PanelFooter = ( { children, cols, count, total = count }: Props ) => (
	<tfoot>
		<tr>
			<td colSpan={ cols }>
				{ __( 'Total:', 'query-monitor' ) }
				&nbsp;
				<span className="qm-items-number">
					{ ( total === count ) ? (
						Utils.numberFormat( count )
					) : (
						sprintf(
							'%1$s / %2$s',
							Utils.numberFormat( count ),
							Utils.numberFormat( total )
						)
					) }
				</span>
			</td>
			{ children }
		</tr>
	</tfoot>
);
