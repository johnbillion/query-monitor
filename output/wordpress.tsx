import * as React from 'react';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

interface iWordPressProps {
	wordpress: any;
}

class WordPress extends React.Component<iWordPressProps, Record<string, unknown>> {

	render() {
		const { wordpress } = this.props;

		return (
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
						{Object.keys(wordpress.constants).map(key =>
							<tr key={key}>
								<th scope="row">
									{ key }
								</th>
								<td>
									{ wordpress.constants[ key ] }
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</section>
		)
	}

}

export default WordPress;
