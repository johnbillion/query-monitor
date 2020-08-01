import * as React from "react";
import { __, _x, _n, sprintf } from '@wordpress/i18n';
import { PanelFooter } from 'qmi';

interface iAssetsProps {
	data: any;
}

interface iPositionLabels {
	missing : string;
	broken : string;
	header : string;
	footer : string;
}

class Assets extends React.Component<iAssetsProps, {}> {

	render() {
		const { data } = this.props;
		const position_labels: iPositionLabels = {
			'missing' : __( 'Missing', 'query-monitor' ),
			'broken'  : __( 'Missing Dependencies', 'query-monitor' ),
			'header'  : __( 'Header', 'query-monitor' ),
			'footer'  : __( 'Footer', 'query-monitor' ),
		};

		return (
			<>
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Position', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Handle', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Host', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Source', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Dependencies', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Dependents', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Version', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{Object.keys(position_labels).map(( key: keyof typeof position_labels ) =>
						<React.Fragment key={key}>
							{data.assets[ key ] && Object.keys(data.assets[ key ]).map(handle => {
								const asset = data.assets[ key ][ handle ];
								return (
									<tr key={handle}>
										<td>
											{ position_labels[ key ] }
										</td>
										<td>
											{ handle }
										</td>
										<td>
											{ asset.host }
										</td>
										<td>
											{ asset.display }
										</td>
										<td>
											{ asset.dependencies.join(', ') }
										</td>
										<td>
											{ asset.dependents.join(', ') }
										</td>
										<td>
											{ asset.ver }
										</td>
									</tr>
								)
							})}
						</React.Fragment>
					)}
				</tbody>
				<PanelFooter cols={7} label={__( 'Total:', 'Total assets', 'query-monitor' )} count={data.counts.total}>
				</PanelFooter>
			</>
		);
	}

}

export default Assets;
