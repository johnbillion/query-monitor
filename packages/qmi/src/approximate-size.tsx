import {
	iQM_i18n,
} from 'qmi';
import * as React from 'react';

import {
	sprintf,
} from '@wordpress/i18n';

interface SizeProps {
	value: number;
}

declare const QM_i18n: iQM_i18n;

export const ApproximateSize = ( { value }: SizeProps ) => (
			<td className="qm-num">
				{ sprintf(
					'~%s kB',
					QM_i18n.number_format( value / 1024 )
				) }
			</td>
		);
