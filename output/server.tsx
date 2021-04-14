import { Warning } from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface ServerItem {
	name: string;
	version: string;
	address: string;
	host: string;
	OS: string;
}

interface iServerProps {
	server: ServerItem;
}

class WordPress extends React.Component<iServerProps, Record<string, unknown>> {

	render() {
		const { server } = this.props;
		const info: ServerItem = {
			name: __( 'Software', 'query-monitor' ),
			version: __( 'Version', 'query-monitor' ),
			address: __( 'Address', 'query-monitor' ),
			host: __( 'Host', 'query-monitor' ),
			OS: __( 'OS', 'query-monitor' ),
		};

		return (
			<section>
				<h3>
					{ __( 'Server', 'query-monitor' ) }
				</h3>
				<table>
					<tbody>
						{ Object.keys( info ).map( ( key: keyof typeof info ) => (
							<tr key={ key }>
								<th scope="row">
									{ info[key] }
								</th>
								<td>
									{ server[key] || (
										<span className="qm-warn">
											<Warning/>
											{ __( 'Unknown', 'query-monitor' ) }
										</span>
									) }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			</section>
		);
	}

}

export default WordPress;
