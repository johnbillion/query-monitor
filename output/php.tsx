import clsx from 'clsx';
import { Warning } from 'qmi';
import {
	Environment as EnvironmentData,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface Props {
	php: EnvironmentData['php'];
}

export default ( { php }: Props ) => (
	<section>
		<h3>
			PHP
		</h3>
		<table>
			<tbody>
				<tr className={ clsx( {
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

						<ul>
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
					</td>
				</tr>
				{ php.extensions && (
					<tr>
						<th scope="row">
							{ __( 'Extensions', 'query-monitor' ) }
						</th>
						<td className="qm-has-toggle qm-ltr">
							<ul>
								{ Object.keys( php.extensions ).map( key => (
									<li key={ key }>
										{ key } { php.extensions[ key ] || '' }
									</li>
								) ) }
							</ul>
						</td>
					</tr>
				) }
			</tbody>
		</table>
	</section>
);
