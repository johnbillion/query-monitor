import { iQM_i18n } from 'qmi';
import * as React from 'react';

interface TimeProps {
	value: number;
}

declare const QM_i18n: iQM_i18n;

export class Time extends React.Component<TimeProps, Record<string, unknown>> {

	render() {
		return (
			<td className="qm-num">
				{ QM_i18n.number_format( this.props.value, 4 ) }
			</td>
		);
	}

}
