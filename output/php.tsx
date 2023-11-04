import * as classNames from 'classnames';
import { Toggler, Warning, iQM_i18n } from 'qmi';
import {
	Environment as EnvironmentData,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface Props {
	php: EnvironmentData['php'];
}

declare const QM_i18n: iQM_i18n;

export default ( { php }: Props ) => (
	<section>
		<h3>
			PHP
		</h3>
		<table>
			<tbody>
				<tr className={ classNames( {
					'qm-warn': php.old,
				} ) }>
					<th scope="row">
						{ __( 'Version', 'query-monitor' ) }
					</th>
					<td>
						{ php.old ? (
							<Warning>
								{ php.version }
								&nbsp;(<a className="qm-external-link" href="https://wordpress.org/support/update-php/">
									{ __( 'Help', 'query-monitor' ) }
								</a>)
							</Warning>
						) : (
							php.version
						) }
					</td>
				</tr>
				<tr>
					<th scope="row">
						SAPI
					</th>
					<td>
						{ php.sapi }
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __( 'User', 'query-monitor' ) }
					</th>
					<td>
						{ php.user || __( 'Unknown', 'query-monitor' ) }
					</td>
				</tr>
				{ Object.keys( php.variables ).map( key => (
					<tr key={ key }>
						<th scope="row">
							{ key }
						</th>
						<td>
							{ php.variables[ key ] }
						</td>
					</tr>
				) ) }
				<tr>
					<th scope="row">
						{ __( 'Error Reporting', 'query-monitor' ) }
					</th>
					<td className="qm-has-toggle qm-ltr">
						{ php.error_reporting }

						<Toggler>
							<ul className="qm-supplemental">
								{ Object.keys( php.error_levels ).map( ( key ) => (
									<li key={ key }>
										{ php.error_levels[ key ] ? (
											<>
												{ key }
												&nbsp;&#x2713;
											</>
										):(
											<span className="qm-false">
												{ key }
											</span>
										) }
									</li>
								) ) }
							</ul>
						</Toggler>
					</td>
				</tr>
				{ php.extensions && (
					<tr>
						<th scope="row">
							{ __( 'Extensions', 'query-monitor' ) }
						</th>
						<td className="qm-has-toggle qm-ltr">
							{ QM_i18n.number_format( Object.keys( php.extensions ).length ) }

							<Toggler>
								<ul className="qm-supplemental">
									{ Object.keys( php.extensions ).map( key => (
										<li key={ key }>
											{ key } ({ php.extensions[ key ] || __( 'Unknown', 'query-monitor' ) })
										</li>
									) ) }
								</ul>
							</Toggler>
						</td>
					</tr>
				) }
			</tbody>
		</table>
	</section>
);
