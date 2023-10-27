import { iQM_i18n } from 'qmi';
import * as React from 'react';

interface TimeCellProps {
	value: number;
}

declare const QM_i18n: iQM_i18n;

export const TimeCell = ( { value }: TimeCellProps ) => (
			<td className="qm-num">
				{ QM_i18n.number_format( value, 4 ) }
			</td>
		);
