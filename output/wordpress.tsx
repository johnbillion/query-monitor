import { Environment as EnvironmentData } from './data-types';
import { __ } from '@wordpress/i18n';

interface Props {
	wordpress: EnvironmentData['wp'];
}

const WordPress = ( { wordpress }: Props ) => (
	<section>
		<h3>
			WordPress
		</h3>
		<table>
			<tbody>
				<tr>
					<th scope="row">
						{ __( 'Version', 'query-monitor' ) }
					</th>
					<td>
						{ wordpress.version }
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __( 'Environment Type', 'query-monitor' ) }
						&nbsp;
						<span className="qm-info">
							(<a className="qm-external-link" href="https://make.wordpress.org/core/2020/07/24/new-wp_get_environment_type-function-in-wordpress-5-5/">
								{ __( 'Help', 'query-monitor' ) }
							</a>)
						</span>
					</th>
					<td>
						{ wordpress.environment_type }
					</td>
				</tr>
				<tr>
					<th scope="row">
						{ __( 'Development Mode', 'query-monitor' ) }
						&nbsp;
						<span className="qm-info">
							(<a className="qm-external-link" href="https://make.wordpress.org/core/2023/07/14/configuring-development-mode-in-6-3/">
								{ __( 'Help', 'query-monitor' ) }
							</a>)
						</span>
					</th>
					<td>
						{ wordpress.development_mode || __( 'empty string', 'query-monitor' ) }
					</td>
				</tr>
				{ Object.entries( wordpress.constants ).map( ( [ key, value ] ) => (
					<tr key={ key }>
						<th scope="row">
							{ key }
						</th>
						<td>
							{ value }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	</section>
);

export default WordPress;
