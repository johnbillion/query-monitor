import { FileName } from '../components/file-name';
import { SourceLocation } from '../components/source-location';
import * as Utils from '../utils';
import { PanelFooter } from '../panels/panel-footer';
import { TabularPanel } from '../panels/tabular-panel';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { MainContext } from '../contexts/main-context';
import { useContext } from 'preact/hooks';

import {
	__,
	sprintf,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

export const languagesMenu = (): PanelMenuItem[] => [ {
	id: 'languages',
	panel: 'languages',
	title: __( 'Languages', 'query-monitor' ),
} ];

export const Languages = ( { data }: PanelProps<DataTypes['languages']> ) => {
	const {
		settings,
	} = useContext( MainContext );

	return (
		<TabularPanel
			title={ __( 'Languages', 'query-monitor' ) }
			cols={ {
				domain: {
					heading: __( 'Text Domain', 'query-monitor' ),
					className: 'qm-nowrap',
					render: ( row ) => (
						row.handle ? (
							`${ row.domain } (${ row.handle })`
						) : (
							row.domain
						)
					),
				},
				type: {
					heading: __( 'Type', 'query-monitor' ),
					render: ( row ) => row.type,
				},
				frame: {
					heading: __( 'Caller', 'query-monitor' ),
					render: ( row ) => (
						row.caller ? (
							<SourceLocation
								text={ Utils.frameDisplay( row.caller ) }
								file={ row.caller.file }
								line={ row.caller.line }
							/>
						) : (
							__( 'Unknown', 'query-monitor' )
						)
					),
				},
				file: {
					heading: __( 'Translation File', 'query-monitor' ),
					render: ( row ) => (
						row.file ? (
							row.found ? (
								<FileName
									text={ Utils.stripAbspath( row.file, settings ) }
									file={ row.file }
								/>
							) : (
								Utils.stripAbspath( row.file, settings )
							)
						) : (
							__( 'None', 'query-monitor' )
						)
					),
				},
				found: {
					heading: __( 'Size', 'query-monitor' ),
					className: 'qm-nowrap',
					render: ( row ) => (
						row.found ? (
							sprintf(
								/* translators: %s: Size in kilobytes */
								__( '%s kB', 'query-monitor' ),
								Utils.numberFormat( row.found / 1024, 1 )
							)
						) : (
							__( 'Not Found', 'query-monitor' )
						)
					),
				},
			} }
			data={ data.languages }
			footer={ ( { cols, count, total, data } ) => (
				<PanelFooter
					cols={ cols - 1 }
					count={ count }
					total={ total }
				>
					<td>
						{ sprintf(
							/* translators: %s: Size in kilobytes */
							__( '%s kB', 'query-monitor' ),
							Utils.numberFormat( data.reduce( ( total, row ) => ( total + ( row.found ? row.found : 0 ) ), 0 ) / 1024, 1 )
						) }
					</td>
				</PanelFooter>
			) }
		>
			<section>
				<h3><code>get_locale()</code></h3>
				<p>{ data.locale }</p>
			</section>

			<section>
				<h3><code>get_user_locale()</code></h3>
				<p>{ data.user_locale }</p>
			</section>

			<section>
				<h3><code>determine_locale()</code></h3>
				<p>{ data.determined_locale }</p>
			</section>

			{ data.mlp_language && (
				<section>
					<h3>
						{ sprintf(
							/* translators: %s: Name of a multilingual plugin */
							__( '%s Language', 'query-monitor' ),
							'MultilingualPress'
						) }
					</h3>
					<p>{ data.mlp_language }</p>
				</section>
			) }

			{ data.pll_language && (
				<section>
					<h3>
						{ sprintf(
							/* translators: %s: Name of a multilingual plugin */
							__( '%s Language', 'query-monitor' ),
							'Polylang'
						) }
					</h3>
					<p>{ data.pll_language }</p>
				</section>
			) }

			<section>
				<h3><code>get_language_attributes()</code></h3>
				<p><code>{ data.language_attributes }</code></p>
			</section>
		</TabularPanel>
	);
};
