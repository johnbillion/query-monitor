import * as React from "react";
import { __, _x, _n, sprintf } from '@wordpress/i18n';
import { Warning } from 'qmi';

interface iDBProps {
	name: string;
	db: any;
}

class DB extends React.Component<iDBProps, {}> {

	render() {
		const {
			name,
			db,
		} = this.props;
		const info = {
			'server-version' : __( 'Server Version', 'query-monitor' ),
			'extension'      : __( 'Extension', 'query-monitor' ),
			'client-version' : __( 'Client Version', 'query-monitor' ),
			'user'           : __( 'User', 'query-monitor' ),
			'host'           : __( 'Host', 'query-monitor' ),
			'database'       : __( 'Database', 'query-monitor' ),
		};

		return (
			<section>
				<h3>
					{sprintf( __( 'Database: %s', 'query-monitor' ), name )}
				</h3>
				<table>
					<tbody>
						{Object.keys(info).map(key =>
							<tr key={key}>
								<th scope="row">
									{info[key]}
								</th>
								<td>
									{db.info[key] || (
										<span className="qm-warn">
											<Warning/>
											{__( 'Unknown', 'query-monitor' )}
										</span>
									)}
								</td>
							</tr>
						)}
						{db.variables.map( variable =>
							<tr key={variable.Variable_name}>
								<th scope="row">
									{variable.Variable_name}
								</th>
								<td>
									{variable.Value}
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</section>
		)
	}

}

export default DB;
