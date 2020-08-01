import * as React from "react";
import { Tabular, iPanelProps } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class DBDupes extends React.Component<iPanelProps, {}> {

	render() {
		const { data } = this.props;

		if ( ! data.dupes || ! Object.keys(data.dupes).length ) {
			return null;
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Query', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'Count', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Callers', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Components', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Potential Troublemakers', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{Object.keys(data.dupes).map(function(key){
						const row = data.dupes[key];
						const callers = data.dupe_callers[key];
						const components = data.dupe_components[key];
						const sources = data.dupe_sources[key];
						return (
							<tr>
								<td className="qm-row-sql qm-ltr qm-wrap">{key}</td>
								<td className="qm-num">{row.length}</td>
								<td>
									{Object.keys(callers).map(function(caller){
										const count = sprintf(
											_n( '%s call', '%s calls', callers[caller], 'query-monitor' ),
											callers[caller]
										);
										return (
											<>
												<code>{caller}</code><br/>
												<span className="qm-info qm-supplemental">{count}</span><br/>
											</>
										)
									})}
								</td>
								<td>
									{Object.keys(components).map(function(component){
										const count = sprintf(
											_n( '%s call', '%s calls', components[component], 'query-monitor' ),
											components[component]
										);
										return (
											<>
												{component}<br/>
												<span className="qm-info qm-supplemental">{count}</span><br/>
											</>
										)
									})}
								</td>
								<td>
									{Object.keys(sources).map(function(source){
										const count = sprintf(
											_n( '%s call', '%s calls', sources[source], 'query-monitor' ),
											sources[source]
										);
										return (
											<>
												<code>{source}</code><br/>
												<span className="qm-info qm-supplemental">{count}</span><br/>
											</>
										)
									})}
								</td>
							</tr>
						)
					})}
				</tbody>
			</Tabular>
		)
	}

}

export default DBDupes;
