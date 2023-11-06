import {
	PanelProps,
	NonTabularPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	_nx,
	sprintf,
} from '@wordpress/i18n';

interface iParts {
	[key: string]: string;
}

export const Theme = ( { data }: PanelProps<DataTypes['Theme']> ) => {
	let parts: iParts = null;

	if ( data.template_parts ) {
		if ( data.is_child_theme ) {
			parts = data.theme_template_parts;
		} else {
			parts = data.template_parts;
		}
	}

	return (
		<NonTabularPanel>
			<section>
				<h3>
					{ __( 'Theme', 'query-monitor' ) }
				</h3>
				<p>
					{ data.stylesheet }
				</p>
				{ data.is_child_theme && (
					<>
						<h3>
							{ __( 'Parent Theme', 'query-monitor' ) }
						</h3>
						<p>
							{ data.template }
						</p>
					</>
				) }
			</section>

			<section>
				<h3>
					{ __( 'Template File', 'query-monitor' ) }
				</h3>
				{ data.template_path ? (
					<p className="qm-ltr">
						{ data.is_child_theme ? data.theme_template_file : data.template_file }
					</p>
				) : (
					<p>
						<em>
							{ __( 'Unknown', 'query-monitor' ) }
						</em>
					</p>
				) }

				{ data.template_hierarchy && (
					<>
						<h3>
							{ __( 'Template Hierarchy', 'query-monitor' ) }
						</h3>
						<ol className="qm-ltr">
							{ data.template_hierarchy.map( ( template: string ) => (
								<li key={ template }>
									{ template }
								</li>
							) ) }
						</ol>
					</>
				) }
			</section>

			<section>
				<h3>
					{ __( 'Template Parts', 'query-monitor' ) }
				</h3>
				{ data.template_parts ? (
					<ul className="qm-ltr">
						{ Object.keys( parts ).map( ( filename ) => (
							<li key={ filename }>
								{ parts[ filename ] }
								{ data.count_template_parts[ filename ] > 1 && (
									<span className="qm-info qm-supplemental">
										<br/>
										{ sprintf(
											/* translators: %s: The number of times that a template part file was included in the page */
											_nx( 'Included %s time', 'Included %s times', data.count_template_parts[ filename ], 'template parts', 'query-monitor' ),
											data.count_template_parts[ filename ]
										) }
									</span>
								) }
							</li>
						) ) }
					</ul>
				) : (
					<p>
						<em>
							{ __( 'None', 'query-monitor' ) }
						</em>
					</p>
				) }

				<h4>
					{ __( 'Not Loaded', 'query-monitor' ) }
				</h4>

				{ data.unsuccessful_template_parts ? (
					<ul>
						{ data.unsuccessful_template_parts.map( ( requested ) => (
							<>
								{ requested.name && (
									<li>
										{ `${ requested.slug }-${ requested.name }.php` }
									</li>
								) }
								<li>
									{ `${ requested.slug }.php` }
								</li>
							</>
						) ) }
					</ul>
				) : (
					<p>
						<em>
							{ __( 'None', 'query-monitor' ) }
						</em>
					</p>
				) }
			</section>

			{ data.timber_files && (
				<section>
					<h3>
						{ __( 'Twig Template Files', 'query-monitor' ) }
					</h3>
					<ul className="qm-ltr">
						{ data.timber_files.map( ( filename: string ) => (
							<li key={ filename }>
								{ filename }
							</li>
						) ) }
					</ul>
				</section>
			) }

			{ data.body_class && (
				<section>
					<h3>
						{ __( 'Body Classes', 'query-monitor' ) }
					</h3>
					<ul className="qm-ltr">
						{ data.body_class.map( ( bodyclass: string ) => (
							<li key={ bodyclass }>
								{ bodyclass }
							</li>
						) ) }
					</ul>
				</section>
			) }

		</NonTabularPanel>
	);
};
