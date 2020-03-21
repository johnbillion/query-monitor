import React, { Component } from 'react';
import { Tabular } from '../utils.js';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class DBComponents extends Component {

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
							{__( 'Component', 'query-monitor' )}
						</th>
						{Object.keys(data.types).map(key=>
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
					{data.times.map(comp=>
						<tr key={comp.component}>
							<td>{comp.component}</td>
							{Object.keys(data.types).map(key=>
								<td key={key} scope="col" className="qm-num">
									{comp.types[key] || ''}
								</td>
							)}
							<td className='qm-num'>{comp.ltime}</td>
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

export default DBComponents;
