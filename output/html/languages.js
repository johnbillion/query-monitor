import React, { Component } from 'react';
import Caller from '../caller.js';
import Notice from '../notice.js';
import Tabular from '../tabular.js';
import PanelFooter from '../panel-footer.js';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Languages extends Component {

	render() {
		const { data } = this.props;

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Text Domain', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Type', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Caller', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Translation File', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Size', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{Object.keys(data.languages).map(key =>
						<>
							{data.languages[key].map(lang =>
								<tr>
									{ lang.handle ? (
										<td class="qm-ltr">{lang.domain} ({lang.handle})</td>
									) : (
										<td class="qm-ltr">{lang.domain}</td>
									)}
									<td>{lang.type}</td>
									<Caller trace={[lang.caller]} />
									{ lang.file ? (
										<td class="qm-ltr">{lang.file}</td>
									) : (
										<td class="qm-nowrap"><em>{__( 'None', 'query-monitor' )}</em></td>
									)}
									{ lang.found ? (
										<td class="qm-nowrap">{lang.found}</td>
									) : (
										<td class="qm-nowrap">{__( 'Not Found', 'query-monitor' )}</td>
									)}
								</tr>
							)}
						</>
					)}
				</tbody>
			</Tabular>
		)
	}

}

export default Languages;
