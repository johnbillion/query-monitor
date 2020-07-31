import * as React from "react";
import { Caller, Notice, QMComponent, PanelFooter, Tabular, iPanelProps, FrameItem } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

interface iCapsProps extends iPanelProps {
	data: {
		caps: {
			name: string;
			user: number;
			result: boolean;
			filtered_trace: FrameItem[];
			component: any;
		}[];
	};
}

class Caps extends React.Component<iCapsProps, {}> {

	render() {
		if ( ! this.props.enabled ) {
			return (
				<Notice id={this.props.id}>
					<p>
					{sprintf(
						/* translators: %s: Configuration file name. */
						__( 'For performance reasons, this panel is not enabled by default. To enable it, add the following code to your %s file:', 'query-monitor' ),
						'<code>wp-config.php</code>'
					)}
					</p>
					<p><code>define( 'QM_ENABLE_CAPS_PANEL', true );</code></p>
				</Notice>
			);
		}

		const { data } = this.props;

		if ( ! data.caps || ! data.caps.length ) {
			return (
				<Notice id={this.props.id}>
					<p>
					{__( 'No capability checks were recorded.', 'query-monitor' )}
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Capability Check', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'User', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Result', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Caller', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Component', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.caps.map(cap =>
						<tr>
							<td className="qm-ltr qm-nowrap"><code>{cap.name}</code></td>
							<td className="qm-num">{cap.user}</td>
							<td className="qm-nowrap">{cap.result ? <span className="qm-true">true&nbsp;&#x2713;</span> : 'false'}</td>
							<Caller trace={cap.filtered_trace} toggleLabel={ __( 'View call stack', 'query-monitor' ) } />
							<QMComponent component={cap.component} />
						</tr>
					)}
				</tbody>
				<PanelFooter cols={5} label={__( 'Total:', 'User capability checks', 'query-monitor' )} count={data.caps.length} />
			</Tabular>
		)
	}

}

export default Caps;
