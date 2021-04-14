import * as React from 'react';
import { Tabular, iPanelProps, Time, TotalTime } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

interface iDBCallerTypeTimes {
	[key: string]: number;
};

interface iDBCallersProps extends iPanelProps {
	data: {
		times?: {
			caller: string;
			ltime: number;
			types: iDBCallerTypeTimes;
		}[];
		types: iDBCallerTypeTimes;
	};
}

class DBCallers extends React.Component<iDBCallersProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.times || ! data.times.length ) {
			return null;
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Caller', 'query-monitor' )}
						</th>
						{Object.keys(data.types).map(key =>
							<th key={key} scope="col" className="qm-num">
								{key}
							</th>
						)}
						<th scope="col" className="qm-num">
							{__( 'Time', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.times.map(caller=>
						<tr key={caller.caller}>
							<td>{caller.caller}</td>
							{Object.keys(data.types).map(key=>
								<td key={key} scope="col" className="qm-num">
									{caller.types[key] || ''}
								</td>
							)}
							<Time value={caller.ltime}/>
						</tr>
					)}
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						{Object.keys(data.types).map(key=>
							<td key={key} scope="col" className="qm-num">
								{data.types[key]}
							</td>
						)}
						<TotalTime rows={data.times}/>
					</tr>
				</tfoot>
			</Tabular>
		)
	}

}

export default DBCallers;
