import * as React from "react";
import { Tabular, iPanelProps } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';

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
							const classes = classnames( {
								'qm-warn': ( 'warning' === type ),
							} );

							return (
								<tr className={classes}>
									<td scope="row" className="qm-nowrap">{type}</td>
									<td className="qm-ltr">{error.message}</td>
									<td className="qm-num">{error.calls}</td>
									<td className="qm-row-caller qm-row-stack qm-nowrap qm-ltr">{error.filename}:{error.line}</td>
									<td className="qm-nowrap">Component</td>
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
