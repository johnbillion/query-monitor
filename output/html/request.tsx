import {
	iPanelProps,
	NonTabular,
} from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface iItems {
	request: string;
	matched_rule: string;
	matched_query: string;
	query_string: string;
}

class Request extends React.Component<iPanelProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		const items: iItems = {
			request: __( 'Request', 'query-monitor' ),
			matched_rule: __( 'Matched Rule', 'query-monitor' ),
			matched_query: __( 'Matched Query', 'query-monitor' ),
			query_string: __( 'Query String', 'query-monitor' ),
		};

		return (
			<NonTabular id={ this.props.id }>
				{ Object.keys( items ).map( ( key: keyof typeof items ) => {
					const name = items[key];
					const value = data.request[key];

					return (
						<>
							<section>
								<h3>{ name }</h3>
								<p className="qm-ltr">
									<code>
										{ value }
									</code>
								</p>
							</section>
						</>
					);
				} ) }

				{ data.matching_rewrites && (
					<section>
						<h3>
							{ __( 'All Matching Rewrite Rules', 'query-monitor' ) }
						</h3>
						<table>
							<tbody>
								{ Object.keys( data.matching_rewrites ).map( ( rule: keyof typeof data.matching_rewrites ) => {
									const query = data.matching_rewrites[ rule ];

									return (
										<tr>
											<td className="qm-ltr">
												<code>
													{ rule }
												</code>
											</td>
											<td className="qm-ltr">
												<code>
													{ query }
												</code>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					</section>
				) }

				<section>
					<h3>{ __( 'Query Vars', 'query-monitor' ) }</h3>
					{ data.qvars && (
						<table>
							<tbody>
								{ Object.keys( data.qvars ).map( ( key: keyof typeof data.qvars ) => (
									<tr>
										<th className="qm-ltr" scope="row">
											{ key }
										</th>
										<td className="qm-ltr qm-wrap">
											{ data.qvars[ key ] }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</section>

				<section>
					<h3>{ __( 'Queried Object', 'query-monitor' ) }</h3>
					{ data.queried_object && (
						<p>
							{ data.queried_object.title } ({ data.queried_object.type })
						</p>
					) }
				</section>

				<section>
					<h3>{ __( 'Current User', 'query-monitor' ) }</h3>
					{ data.user && data.user.data && (
						<p>
							{ data.user.title }
						</p>
					) }
				</section>

				{ data.multisite && (
					<section>
						<h3>{ __( 'Multisite', 'query-monitor' ) }</h3>
						{ Object.keys( data.multisite ).map( ( key: keyof typeof data.multisite ) => (
							<p>
								{ data.multisite[ key ].title }
							</p>
						) ) }
					</section>
				) }
			</NonTabular>
		);
	}

}

export default Request;
