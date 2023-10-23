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

export class ApproximateSize extends React.Component<SizeProps, Record<string, unknown>> {

	render() {
		return (
			<td className="qm-num">
				{ sprintf(
					'~%s kB',
					QM_i18n.number_format( this.props.value / 1024 )
				) }
			</td>
		);
	}

}
