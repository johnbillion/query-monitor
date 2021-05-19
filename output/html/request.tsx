import {
	iPanelProps,
	NonTabular,
} from 'qmi';
import * as React from 'react';
import {
	WP_Network,
	WP_Post_Type,
	WP_Post,
	WP_Site,
	WP_Term,
	WP_User,
} from 'wp-types';

import { sprintf, __ } from '@wordpress/i18n';

interface iItems {
	request: string;
	matched_rule: string;
	matched_query: string;
	query_string: string;
}

interface iRequestPanelProps extends iPanelProps {
	data: {
		request: iItems;
		request_method: string;
		user?: WP_User;
		matching_rewrites?: {
			[k: string]: string;
		};
		qvars?: {
			[k: string]: string;
		};
		queried_object?: {
			title: string;
			type?: 'WP_Term' | 'WP_Post_Type' | 'WP_Post' | 'WP_User';
			data?: WP_Term | WP_Post_Type | WP_Post | WP_User;
		};
		multisite?: {
			current_site: {
				title: string;
				data: WP_Site;
			};
			current_network?: {
				title: string;
				data: WP_Network;
			};
		};
	}
}

class Request extends React.Component<iRequestPanelProps, Record<string, unknown>> {

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
						<React.Fragment key={ key }>
							<section>
								<h3>{ name }</h3>
								<p className="qm-ltr">
									<code>
										{ value }
									</code>
								</p>
							</section>
						</React.Fragment>
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
										<tr key={ rule }>
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
									<tr key={ key }>
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
					{ data.user ? (
						<p>
							{ sprintf(
								/* translators: %d: User ID */
								__( 'Current User: #%d', 'query-monitor' ),
								data.user.ID
							) }
						</p>
					) : (
						<p>
							{ __( 'None', 'query-monitor' ) }
						</p>
					) }
				</section>

				{ data.multisite && (
					<section>
						<h3>{ __( 'Multisite', 'query-monitor' ) }</h3>
						{ Object.keys( data.multisite ).map( ( key: keyof typeof data.multisite ) => (
							<p key={ key }>
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
