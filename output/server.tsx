import * as React from "react";
import { Warning } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class WordPress extends React.Component {

	render() {
		const { server } = this.props;
		const info = {
			'name'    : __( 'Software', 'query-monitor' ),
			'version' : __( 'Version', 'query-monitor' ),
			'address' : __( 'Address', 'query-monitor' ),
			'host'    : __( 'Host', 'query-monitor' ),
			'OS'      : __( 'OS', 'query-monitor' ),
		};

		return (
			<section>
				<h3>
					{__('Server','query-monitor')}
				</h3>
				<table>
					<tbody>
						{Object.keys(info).map(key =>
							<tr key={key}>
								<th scope="row">
									{info[key]}
								</th>
								<td>
									{server[key] || (
										<span className="qm-warn">
											<Warning/>
											{__( 'Unknown', 'query-monitor' )}
										</span>
									)}
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</section>
		)
	}

}

export default WordPress;
