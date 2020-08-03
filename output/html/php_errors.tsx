import * as React from "react";
import { Tabular, iPanelProps } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class PHPErrors extends React.Component<iPanelProps, {}> {

	render() {
		const { data } = this.props;

		if ( ! data.errors ) {
			return null;
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Level', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Message', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'Count', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Location', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Component', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{Object.keys(data.errors).map(type=> {
						const errors = data.errors[type];

						return Object.keys(errors).map(id=>{
							const error = errors[id];

							return (
								<tr>
									<td>{type}</td>
									<td>{error.message}</td>
									<td>Count</td>
									<td>Location</td>
									<td>Component</td>
								</tr>
							)
						})
					})}
				</tbody>
			</Tabular>
		)
	}

}

export default PHPErrors;
