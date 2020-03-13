import React, { Component } from 'react';
import Caller from '../caller.js';
import QMComponent from '../component.js';
import Tabular from '../tabular.js';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class DBCallers extends Component {

	render() {
		const data = this.props.data;

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
						{Object.keys(data.types).map(function(key){
							return (
								<th scope="col" class='qm-num'>
									{key}
								</th>
							)
						})}
						<th scope="col" class="qm-num">
							{__( 'Time', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.times.map(function(caller){
						return (
							<tr>
								<td>{caller.caller}</td>
								{Object.keys(data.types).map(function(key){
									return (
										<td scope="col" class='qm-num'>
											{caller.types[key] || ''}
										</td>
									)
								})}
								<td class='qm-num'>{caller.ltime}</td>
							</tr>
						)
					})}
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						{Object.keys(data.types).map(function(key){
							return (
								<td scope="col" class='qm-num'>
									{data.types[key]}
								</td>
							)
						})}
						<td class='qm-num'>{data.times.reduce((a,b)=>a+b.ltime,0)}</td>
					</tr>
				</tfoot>
			</Tabular>
		)
	}

}

export default DBCallers;
