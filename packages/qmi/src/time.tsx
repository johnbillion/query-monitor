import * as React from "react";
import { iQM_i18n } from 'qmi';

export interface TimeProps {
	value: number;
}

declare var QM_i18n: iQM_i18n;

export class Time extends React.Component<TimeProps, {}> {

	render() {
		return (
			<td className="qm-num">
				{QM_i18n.number_format( this.props.value, 4 )}
			</td>
		);
	}

}
