import {
	Caller,
	FrameItem,
	iPanelProps,
	NonTabular,
} from 'qmi';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

interface iLanguagesProps extends iPanelProps {
	data: {
		languages: {
			[key: string]: {
				domain: string;
				file: string;
				handle: string;
				type: string;
				found: string;
				caller: FrameItem;
			}[];
		};
		locale: string;
		user_locale: string;
		determined_locale: string;
		mlp_language: string;
		pll_language: string;
		language_attributes: string;
	};
}

class Languages extends React.Component<iLanguagesProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		return (
			<NonTabular id={ this.props.id }>
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

				{ data.languages && (
					<table className="qm-full-width">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Text Domain', 'query-monitor' ) }
								</th>
								<th scope="col">
									{ __( 'Type', 'query-monitor' ) }
								</th>
								<th scope="col">
									{ __( 'Caller', 'query-monitor' ) }
								</th>
								<th scope="col">
									{ __( 'Translation File', 'query-monitor' ) }
								</th>
								<th scope="col">
									{ __( 'Size', 'query-monitor' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ Object.keys( data.languages ).map( key => (
								<React.Fragment key={ key }>
									{ Object.keys( data.languages[key] ).map( lang_path => {
										const lang = data.languages[key][lang_path];

										return (
											<tr key={ lang.domain + lang.handle + lang.file }>
												{ lang.handle ? (
													<td className="qm-ltr">
														{ lang.domain } ({ lang.handle })
													</td>
												) : (
													<td className="qm-ltr">
														{ lang.domain }
													</td>
												) }
												<td>
													{ lang.type }
												</td>
												<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ [ lang.caller ] } />
												{ lang.file ? (
													<td className="qm-ltr">
														{ lang.file }
													</td>
												) : (
													<td className="qm-nowrap">
														<em>
															{ __( 'None', 'query-monitor' ) }
														</em>
													</td>
												) }
												{ lang.found ? (
													<td className="qm-nowrap">
														{ lang.found }
													</td>
												) : (
													<td className="qm-nowrap">
														{ __( 'Not Found', 'query-monitor' ) }
													</td>
												) }
											</tr>
										);
									} ) }
								</React.Fragment>
							) ) }
						</tbody>
					</table>
				) }
			</NonTabular>
		);
	}

}

export default Languages;
