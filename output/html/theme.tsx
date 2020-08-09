import * as React from "react";
import { NonTabular, iPanelProps } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Theme extends React.Component<iPanelProps, {}> {

	render() {
		const { data } = this.props;

		return (
			<NonTabular id={this.props.id}>
				<section>
					<h3>{ __( 'Theme', 'query-monitor' ) }</h3>
					<p>
						{ data.stylesheet }
					</p>
					{ data.is_child_theme && (
						<>
							<h3>{ __( 'Parent Theme', 'query-monitor' ) }</h3>
							<p>
								{ data.template }
							</p>
						</>
					)}
				</section>

				<section>
					<h3>{ __( 'Template File', 'query-monitor' ) }</h3>
					{ data.template_path ? (
						<p className="qm-ltr">
							{ data.is_child_theme ? data.theme_template_file : data.template_file }
						</p>
					) : (
						<p>
							<em>{ __( 'Unknown', 'query-monitor' ) }</em>
						</p>
					) }

					{ data.template_hierarchy && (
						<>
							<h3>{ __( 'Template Hierarchy', 'query-monitor' ) }</h3>
							<ol className="qm-ltr">
								{ data.template_hierarchy.map((template: string)=>
									<li>{ template }</li>
								) }
							</ol>
						</>
					)}
				</section>
			</NonTabular>
		)
	}

}

export default Theme;
