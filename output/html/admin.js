import React, { Component } from 'react';
import { NonTabular } from '../utils';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Admin extends Component {

	render() {
		const { data } = this.props;
		const admin_globals = [
			'pagenow',
			'typenow',
			'taxnow',
			'hook_suffix',
		];

		return (
			<NonTabular id={this.props.id}>
				<section>
					<h3>get_current_screen()</h3>
					<table>
						<thead className="qm-screen-reader-text">
							<tr>
								<th scope="col">{ __( 'Property', 'query-monitor' ) }</th>
								<th scope="col">{ __( 'Value', 'query-monitor' ) }</th>
							</tr>
						</thead>
						<tbody>
							{Object.keys(data.current_screen).map(key =>
								<tr key={key}>
									<th scope="row">
										{ key }
									</th>
									<td>
										{ data.current_screen[ key ] }
									</td>
								</tr>
							)}
						</tbody>
					</table>
				</section>
				<section>
					<h3>{ __( 'Globals', 'query-monitor' ) }</h3>
					<table>
						<thead className="qm-screen-reader-text">
							<tr>
								<th scope="col">{ __( 'Global Variable', 'query-monitor' ) }</th>
								<th scope="col">{ __( 'Value', 'query-monitor' ) }</th>
							</tr>
						</thead>
						<tbody>
							{admin_globals.map(global =>
								<tr key={global}>
									<th scope="row">
										{ global }
									</th>
									<td>
										{ data[ global ] }
									</td>
								</tr>
							)}
						</tbody>
					</table>
				</section>
				{ data.list_table &&
					<section>
						<h3>{ __( 'List Table', 'query-monitor' ) }</h3>
						{ data.list_table.class_name &&
							<>
								<h4>{ __( 'Class:', 'query-monitor' ) }</h4>
								<p><code>{ data.list_table.class_name }</code></p>
							</>
						}
						<h4>{ __( 'Column Filters:', 'query-monitor' ) }</h4>
						<p><code>{ data.list_table.columns_filter }</code></p>
						<p><code>{ data.list_table.sortables_filter }</code></p>
						<h4>{ __( 'Column Action:', 'query-monitor' ) }</h4>
						<p><code>{ data.list_table.column_action }</code></p>
					</section>
				}
			</NonTabular>
		);
	}

}

export default Admin;
