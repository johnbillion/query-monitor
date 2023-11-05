import {
	ApproximateSize,
	Frame,
	PanelFooter,
	PanelProps,
	TabularPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export default ( { data }: PanelProps<DataTypes['Languages']> ) => {
	return (
		<TabularPanel
			title={ __( 'Languages', 'query-monitor' ) }
			cols={ {
				domain: {
					heading: __( 'Text Domain', 'query-monitor' ),
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
							<Frame
								frame={ row.caller }
								isFileName
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
							row.file
						) : (
							__( 'None', 'query-monitor' )
						)
					),
				},
				found: {
					heading: __( 'Size', 'query-monitor' ),
					render: ( row ) => (
						row.found ? (
							<ApproximateSize
								value={ row.found }
							/>
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
						<ApproximateSize
							value={ data.reduce( ( total, row ) => ( total + ( row.found ? row.found : 0 ) ), 0 ) }
						/>
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
				<p>{ data.language_attributes }</p>
			</section>
		</TabularPanel>
	);
};
