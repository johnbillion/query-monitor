import {
	iPanelProps,
	NonTabular,
	Utils,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { sprintf, __ } from '@wordpress/i18n';

interface iRequestPanelProps extends iPanelProps {
	data: DataTypes['Request'];
}

class Request extends React.Component<iRequestPanelProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		const items = {
			request: __( 'Request', 'query-monitor' ),
			matched_rule: __( 'Matched Rule', 'query-monitor' ),
			matched_query: __( 'Matched Query', 'query-monitor' ),
			query_string: __( 'Query String', 'query-monitor' ),
		};
		const urls = {
			request: false,
			matched_rule: false,
			matched_query: true,
			query_string: true,
		};

		return (
			<NonTabular id={ this.props.id }>
				{ Object.keys( items ).map( ( key: keyof typeof items ) => {
					const name = items[key];
					const value = data.request[ key ];
					const url = urls[ key ];

					return (
						<React.Fragment key={ key }>
							<section>
								<h3>{ name }</h3>
								{ value ? (
									<p className="qm-ltr">
										<code>
											{ url ? Utils.formatURL( value ) : value }
										</code>
									</p>
								) : (
									<p>
										{ __( 'None', 'query-monitor' ) }
									</p>
								) }
							</section>
						</React.Fragment>
					);
				} ) }

				{ data.matching_rewrites && Object.keys( data.matching_rewrites ).length > 0 && (
					<section>
						<h3>
							{ __( 'All Matching Rewrite Rules', 'query-monitor' ) }
						</h3>
						<table>
							<tbody>
								{ Object.keys( data.matching_rewrites ).map( ( rule: keyof typeof data.matching_rewrites ) => {
									const query = data.matching_rewrites[ rule ].replace( 'index.php?', '' );

									return (
										<tr key={ rule }>
											<td className="qm-ltr">
												<code>
													{ rule }
												</code>
											</td>
											<td className="qm-ltr">
												<code>
													{ Utils.formatURL( query ) }
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
					{ data.qvars ? (
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
					) : (
						<p>
							{ __( 'None', 'query-monitor' ) }
						</p>
					) }
				</section>

				<section>
					<h3>{ __( 'Queried Object', 'query-monitor' ) }</h3>
					{ data.queried_object ? (
						<p>
							{ data.queried_object.title } ({ data.queried_object.type })
						</p>
					) : (
						<p>
							{ __( 'None', 'query-monitor' ) }
						</p>
					) }
				</section>

				<section>
					<h3>{ __( 'Current User', 'query-monitor' ) }</h3>
					{ data.user.data ? (
						<p>
							{ sprintf(
								/* translators: %d: User ID */
								__( 'Current User: #%d', 'query-monitor' ),
								data.user.data.ID
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
						<p>
							{ sprintf(
								/* translators: %d: Multisite site ID */
								__( 'Current Site: #%d', 'query-monitor' ),
								data.multisite.current_site.blog_id
							) }
						</p>
					</section>
				) }
			</NonTabular>
		);
	}

}

export default Request;
