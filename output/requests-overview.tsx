import { __ } from '@wordpress/i18n';

import { numberFormat } from './utils';
import { iQMRequest } from './request-nav';

interface Props {
	requests: iQMRequest[];
}

export const RequestsOverview = ( { requests }: Props ) => {
	const byPath = new Map<string, number>();

	for ( const request of requests ) {
		// Group by the display path (urlPathForDisplay), which is the request's label.
		const path = request.label;
		byPath.set( path, ( byPath.get( path ) ?? 0 ) + 1 );
	}

	const paths = Array.from( byPath.entries() ).sort( ( a, b ) => b[ 1 ] - a[ 1 ] );

	return (
		// Scrollable region must be keyboard-accessible.
		// eslint-disable-next-line jsx-a11y/no-noninteractive-tabindex
		<div id="qm-requests" tabIndex={ 0 }>
			<div
				aria-labelledby="qm-requests-overview-title"
				className="qm qm-panel-show qm-non-tabular"
				id="qm-requests-overview"
				role="tabpanel"
				tabIndex={ -1 }
			>
				<div className="qm-boxed">
					<section>
						<h3>
							{ __( 'Requests by URL Path', 'query-monitor' ) }
						</h3>
						<table>
							<thead>
								<tr>
									<th scope="col">
										{ __( 'URL Path', 'query-monitor' ) }
									</th>
									<th scope="col" className="qm-num">
										{ __( 'Count', 'query-monitor' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ paths.map( ( [ path, count ] ) => (
									<tr key={ path }>
										<td className="qm-ltr">
											<code>{ path }</code>
										</td>
										<td className="qm-num">
											{ numberFormat( count ) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</section>
				</div>
			</div>
		</div>
	);
};
