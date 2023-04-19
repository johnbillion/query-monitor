import { Warning } from 'qmi';
import {
	Environment as EnvironmentData,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

interface iDBProps {
	db: EnvironmentData['db'];
}

class DB extends React.Component<iDBProps, Record<string, unknown>> {

	render() {
		const {
			db,
		} = this.props;
		const infoLabels = {
			'server-version': __( 'Server Version', 'query-monitor' ),
			'extension': __( 'Extension', 'query-monitor' ),
			'client-version': __( 'Client Version', 'query-monitor' ),
			'user': __( 'User', 'query-monitor' ),
			'host': __( 'Host', 'query-monitor' ),
			'database': __( 'Database', 'query-monitor' ),
		};

		return (
			<section>
				<h3>
					{ __( 'Database', 'query-monitor' ) }
				</h3>
				<table>
					<tbody>
						{ Object.keys( infoLabels ).map( ( key: keyof typeof infoLabels ) => (
							<tr key={ key }>
								<th scope="row">
									{ infoLabels[key] }
								</th>
								<td>
									{ db.info[key] || (
										<span className="qm-warn">
											<Warning/>
											{ __( 'Unknown', 'query-monitor' ) }
										</span>
									) }
								</td>
							</tr>
						) ) }
						{ db.variables.map( variable => (
							<tr key={ variable.Variable_name }>
								<th scope="row">
									{ variable.Variable_name }
								</th>
								<td>
									{ variable.Value }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			</section>
		);
	}

}

export default DB;
