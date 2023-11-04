import {
	Environment as EnvironmentData,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface Props {
	wordpress: EnvironmentData['wp'];
}

export default ( { wordpress }: Props ) => (
	<section>
		<h3>
			WordPress
		</h3>
		<table>
			<tbody>
				<tr>
					<th scope="row">
						{ __( 'Version', 'query-monitor' ) }
					</th>
					<td>
						{ wordpress.version }
					</td>
				</tr>
				{ Object.entries( wordpress.constants ).map( ( [ key, value ] ) => (
					<tr key={ key }>
						<th scope="row">
							{ key }
						</th>
						<td>
							{ value }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	</section>
);
