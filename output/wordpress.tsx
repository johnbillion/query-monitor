import {
	Environment as EnvironmentData,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface iWordPressProps {
	wordpress: EnvironmentData['wp'];
}

export default ( { wordpress }: iWordPressProps ) => (
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
				{ Object.keys( wordpress.constants ).map( key => (
					<tr key={ key }>
						<th scope="row">
							{ key }
						</th>
						<td>
							{ wordpress.constants[ key ] }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	</section>
);
