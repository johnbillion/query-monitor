import React, { Component } from 'react';
import Tabular from '../tabular.jsx';
import Caller from '../caller.jsx';
import Notice from '../notice.jsx';

const { __, _x, _n, sprintf } = wp.i18n;

class Transients extends Component {

	render() {
		const data = this.props.data;

		if ( ! data.trans || ! data.trans.length ) {
			return (
				<Notice id={this.props.id}>
					<p>
					{__( 'No transients set.', 'query-monitor' )}
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Updated Transient', 'query-monitor' )}
						</th>
						{data.has_type &&
							<th scope="col">
								{_x( 'Type', 'transient type', 'query-monitor' )}
							</th>
						}
						<th scope="col">
							{__( 'Expiration', 'query-monitor' )}
						</th>
						<th scope="col">
							{_x( 'Size', 'size of transient value', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Caller', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Component', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.trans.map(transient =>
						<tr>
							<td class="qm-ltr qm-nowrap"><code>{transient.name}</code></td>
							{data.has_type &&
								<td class="qm-ltr qm-nowrap">{transient.type}</td>
							}

							{ transient.expiration ? (
								<td class="qm-nowrap">{transient.expiration} <span class="qm-info">(~{transient.exp_diff})</span></td>
							) : (
								<td class="qm-nowrap"><em>{__( 'none', 'query-monitor' )}</em></td>
							) }

							<td class="qm-nowrap">~{transient.size_formatted}</td>
							<Caller trace={transient.filtered_trace} />
							<td class="qm-nowrap">{transient.component.name}</td>
						</tr>
					)}
				</tbody>
			</Tabular>
		)
	}

}

export default Transients;
