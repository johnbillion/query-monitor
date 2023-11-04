import { Warning } from 'qmi';
import {
	Environment as EnvironmentData,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface Props {
	server: EnvironmentData['server'];
}

export default ( { server }: Props ) => {
	const info = {
		name: __( 'Software', 'query-monitor' ),
		version: __( 'Version', 'query-monitor' ),
		address: __( 'Address', 'query-monitor' ),
		host: __( 'Host', 'query-monitor' ),
		/* translators: OS stands for Operating System */
		OS: __( 'OS', 'query-monitor' ),
		arch: __( 'Architecture', 'query-monitor' ),
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
									<Warning>
										{ __( 'Unknown', 'query-monitor' ) }
									</Warning>
								) }
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</section>
	);
};
