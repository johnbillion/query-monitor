import React, { Component } from 'react';
import { Tabular } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class DBCallers extends Component {

	render() {
		const { data } = this.props;

		if ( ! data.times || ! Object.keys(data.times).length ) {
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
							<td className='qm-num'>{caller.ltime}</td>
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
						<td className='qm-num'>{data.times.reduce((a,b)=>a+b.ltime,0)}</td>
					</tr>
				</tfoot>
			</Tabular>
		)
	}

}

export default DBCallers;
