import React, { Component } from 'react';
import NonTabular from '../non-tabular.js';
import Warning from '../warning.js';
import Toggler from '../toggler.js';
import classnames from 'classnames';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Environment extends Component {

	render() {
		const { data } = this.props;
		const warning = data.php.old;
		const classes = classnames( {
			'qm-warn': warning,
		} );

		return (
			<NonTabular id={this.props.id}>
				<section>
					<h3>
						PHP
					</h3>
					<table>
						<tbody>
							<tr className={ classes }>
								<th scope="row">
									{ __( 'Version', 'query-monitor' ) }
								</th>
								<td>
									{ warning ? (
										<>
											<Warning/>
											{ data.php.version }
											&nbsp;(<a href="https://wordpress.org/support/update-php/" class="qm-external-link">
												{__( 'Help', 'query-monitor' )}
											</a>)
										</>
									) : (
										<>
											{ data.php.version }
										</>
									)}
								</td>
							</tr>
							<tr>
								<th scope="row">
									SAPI
								</th>
								<td>
									{ data.php.sapi }
								</td>
							</tr>
							<tr>
								<th scope="row">
									{ __( 'User', 'query-monitor' ) }
								</th>
								<td>
									{ data.php.user || __( 'Unknown', 'query-monitor' ) }
								</td>
							</tr>
							{Object.keys(data.php.variables).map(key =>
								<tr key={key}>
									<th scope="row">
										{ key }
									</th>
									<td>
										{ data.php.variables[ key ].after }
									</td>
								</tr>
							)}
							<tr>
								<th scope="row">
									{__( 'Error Reporting', 'query-monitor' )}
								</th>
								<td className="qm-has-toggle qm-ltr">
									{ data.php.error_reporting }

									<Toggler>
										<ul className="qm-supplemental">
											{Object.keys(data.php.error_levels).map(key =>
												<li key={key}>
													{ data.php.error_levels[ key ] ? (
														<>
															{key}&nbsp;&#x2713;
														</>
													):(
														<span className="qm-false">
															{key}
														</span>
													)}
												</li>
											)}
										</ul>
									</Toggler>
								</td>
							</tr>
							{ data.php.extensions && (
								<tr>
									<th scope="row">
										{__( 'Extensions', 'query-monitor' )}
									</th>
									<td className="qm-has-toggle qm-ltr">
										{QM_i18n.number_format( Object.keys(data.php.extensions).length )}

										<Toggler>
											<ul className="qm-supplemental">
												{Object.keys(data.php.extensions).map(key =>
													<li key={key}>
														{key} ({ data.php.extensions[ key ] || __( 'Unknown', 'query-monitor') })
													</li>
												)}
											</ul>
										</Toggler>
									</td>
								</tr>
							)}
						</tbody>
					</table>
				</section>
			</NonTabular>
		);
	}

}

export default Environment;
