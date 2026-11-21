import { NonTabularPanel } from '../panels/non-tabular-panel';
import { FileName } from '../components/file-name';
import * as Utils from '../utils';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { Fragment } from 'preact';

import {
	__,
	_nx,
	sprintf,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

export const themeMenu = ( data: DataTypes['response'] ): PanelMenuItem[] => {
	let name: string;

	if ( data.block_template ) {
		name = data.block_template.wp_id
			? data.block_template.id
			: sprintf( '%s/%s.html', data.theme_folders[ data.block_template.type ], data.block_template.slug );
	} else if ( data.template_file ) {
		name = data.is_child_theme ? data.theme_template_file : data.template_file;
	} else {
		name = __( 'Unknown', 'query-monitor' );
	}

	return [
		{
			id: 'response',
			panel: 'response',
			/* translators: %s: Template file name */
			title: sprintf( __( 'Template: %s', 'query-monitor' ), name ),
			nav: false,
		},
		{
			id: 'response',
			panel: 'response',
			title: __( 'Template', 'query-monitor' ),
			adminBar: false,
		},
	];
};

interface iParts {
	[key: string]: string;
}

export const Theme = ( { data }: PanelProps<DataTypes['response']> ) => {
	let parts: iParts | null = null;

	if ( data.template_parts ) {
		if ( data.is_child_theme ) {
			parts = data.theme_template_parts;
		} else {
			parts = data.template_parts;
		}
	}

	return (
		<NonTabularPanel title={ __( 'Template', 'query-monitor' ) }>
			<section>
				<h3>
					{ __( 'Theme', 'query-monitor' ) }
				</h3>
				<p>
					{ data.stylesheet }
				</p>
				<p>
					<FileName
						text="style.css"
						file={ `${ data.theme_dirs[ data.stylesheet ] }/style.css` }
					/>
				</p>
				{ data.stylesheet_theme_json && (
					<p>
						<FileName
							text="theme.json"
							file={ data.stylesheet_theme_json }
						/>
					</p>
				) }
				{ data.is_child_theme && (
					<>
						<h3>
							{ __( 'Parent Theme', 'query-monitor' ) }
						</h3>
						<p>
							{ data.template }
						</p>
						<p>
							<FileName
								text="style.css"
								file={ `${ data.theme_dirs[ data.template ] }/style.css` }
							/>
						</p>
						{ data.template_theme_json && (
							<p>
								<FileName
									text="theme.json"
									file={ data.template_theme_json }
								/>
							</p>
						) }
					</>
				) }
			</section>

			<section>
				{ data.block_template ? (
					<>
						<h3>
							{ __( 'Block Template', 'query-monitor' ) }
						</h3>
						<p className="qm-ltr">
							{ data.block_template.wp_id ? (
								<a href={ Utils.getSiteEditorUrl( data.block_template.id, 'wp_template' ) }>
									{ data.block_template.id }
								</a>
							) : (
								<FileName
									text={ `${ data.theme_folders[ data.block_template.type ] }/${ data.block_template.slug }.html` }
									file={ `${ data.theme_dirs[ data.block_template.theme ] }/${ data.theme_folders[ data.block_template.type ] }/${ data.block_template.slug }.html` }
								/>
							) }
						</p>
					</>
				) : (
					<>
						<h3>
							{ __( 'Template File', 'query-monitor' ) }
						</h3>
						{ data.template_path ? (
							<p className="qm-ltr">
								<FileName
									text={ ( data.is_child_theme ? data.theme_template_file : data.template_file ) ?? '' }
									file={ data.template_path }
								/>
							</p>
						) : (
							<p>
								<em>
									{ __( 'Unknown', 'query-monitor' ) }
								</em>
							</p>
						) }
					</>
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
				{ parts ? (
					<ul className="qm-ltr">
						{ Object.keys( parts ).map( ( filename ) => (
							<li key={ filename }>
								{ typeof filename === 'number' ? (
									<a href={ Utils.getSiteEditorUrl( parts[ filename ] ) }>
										{ parts[ filename ] }
									</a>
								) : (
									<FileName
										text={ parts[ filename ] }
										file={ filename }
									/>
								) }
								{ data.count_template_parts[ filename ] > 1 && (
									<span className="qm-info qm-supplemental">
										<br/>
										&nbsp;-&nbsp;
										{ sprintf(
											/* translators: %s: The number of times that a template part file was included in the page */
											_nx( 'Included %s time', 'Included %s times', data.count_template_parts[ filename ], 'template parts', 'query-monitor' ),
											Utils.numberFormat( data.count_template_parts[ filename ] )
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

				{ data.unsuccessful_template_parts && data.unsuccessful_template_parts.length > 0 && (
					<>
						<h4>
							{ __( 'Not Loaded', 'query-monitor' ) }
						</h4>
						<ul>
							{ data.unsuccessful_template_parts.map( ( requested, index ) => (
								<Fragment key={ index }>
									{ requested.name && (
										<li>
											<FileName
												text={ `${ requested.slug }-${ requested.name }.php` }
												file={ requested.caller?.file }
												line={ requested.caller?.line }
											/>
										</li>
									) }
									<li>
										<FileName
											text={ `${ requested.slug }.php` }
											file={ requested.caller?.file }
											line={ requested.caller?.line }
										/>
									</li>
								</Fragment>
							) ) }
						</ul>
					</>
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
