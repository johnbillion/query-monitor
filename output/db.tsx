import { ApproximateSize } from './components/approximate-size';
import { Warning } from './components/warning';
import { Environment as EnvironmentData } from './data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

const ONE_MB = 1024 * 1024;

interface Props {
	db: EnvironmentData['db'];
}

export default ( { db }: Props) => {
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
									<Warning>
										{ __( 'Unknown', 'query-monitor' ) }
									</Warning>
								) }
							</td>
						</tr>
					) ) }
					{ db.variables.map( variable => {
						const numValue = Number( variable.Value );
						const showSize = ! isNaN( numValue ) && numValue >= ONE_MB;

						return (
							<tr key={ variable.Variable_name }>
								<th scope="row">
									{ variable.Variable_name }
								</th>
								<td>
									{ variable.Value }
									{ showSize && (
										<>
											&nbsp;
											<span className="qm-info">
												(<ApproximateSize value={ numValue } />)
											</span>
										</>
									) }
								</td>
							</tr>
						);
					} ) }
				</tbody>
			</table>
		</section>
	);
};
