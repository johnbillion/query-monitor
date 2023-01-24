import {
	NonTabular,
	iPanelProps,
} from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface iAdminProps extends iPanelProps {
	data: {
		current_screen: {
			[k: string]: string|boolean;
		};
		hook_suffix: string;
		pagenow: string;
		taxnow: string;
		typenow: string;
		list_table?: {
			columns_filter: string;
			sortables_filter: string;
			column_action: string;
			class_name?: string;
		};
	};
}

class Admin extends React.Component<iAdminProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		return (
			<NonTabular id={ this.props.id }>
				<section>
					<h3>
						get_current_screen()
					</h3>
					<table>
						<thead className="qm-screen-reader-text">
							<tr>
								<th scope="col">
									{ __( 'Property', 'query-monitor' ) }
								</th>
								<th scope="col">
									{ __( 'Value', 'query-monitor' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ Object.keys( data.current_screen ).map( key => (
								<tr key={ key }>
									<th scope="row">
										{ key }
									</th>
									<td>
										{ ( typeof data.current_screen[ key ] ) === 'string' ? (
											data.current_screen[ key ]
										) : (
											data.current_screen[ key ] ? 'true' : 'false'
										) }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</section>
				<section>
					<h3>
						{ __( 'Globals', 'query-monitor' ) }
					</h3>
					<table>
						<thead className="qm-screen-reader-text">
							<tr>
								<th scope="col">
									{ __( 'Global Variable', 'query-monitor' ) }
								</th>
								<th scope="col">
									{ __( 'Value', 'query-monitor' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<th scope="row">
									pagenow
								</th>
								<td>
									{ data.pagenow }
								</td>
							</tr>
							<tr>
								<th scope="row">
									taxnow
								</th>
								<td>
									{ data.taxnow }
								</td>
							</tr>
							<tr>
								<th scope="row">
									typenow
								</th>
								<td>
									{ data.typenow }
								</td>
							</tr>
							<tr>
								<th scope="row">
									hook_suffix
								</th>
								<td>
									{ data.hook_suffix }
								</td>
							</tr>
						</tbody>
					</table>
				</section>
				{ data.list_table && (
					<section>
						<h3>
							{ __( 'List Table', 'query-monitor' ) }
						</h3>
						{ data.list_table.class_name && (
							<>
								<h4>
									{ __( 'Class:', 'query-monitor' ) }
								</h4>
								<p>
									<code>
										{ data.list_table.class_name }
									</code>
								</p>
							</>
						) }
						<h4>
							{ __( 'Column Filters:', 'query-monitor' ) }
						</h4>
						<p>
							<code>
								{ data.list_table.columns_filter }
							</code>
						</p>
						<p>
							<code>
								{ data.list_table.sortables_filter }
							</code>
						</p>
						<h4>
							{ __( 'Column Action:', 'query-monitor' ) }
						</h4>
						<p>
							<code>
								{ data.list_table.column_action }
							</code>
						</p>
					</section>
				) }
			</NonTabular>
		);
	}

}

export default Admin;
