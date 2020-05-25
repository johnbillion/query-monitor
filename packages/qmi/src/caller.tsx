import * as React from "react";
import { Frame } from 'qmi';

import type { FrameItem } from './frame';

export interface CallerProps {
	trace: FrameItem[];
	toggleLabel: string;
}

export class Caller extends React.Component<CallerProps, {}> {

	render() {
		const trace = this.props.trace;
		const caller = trace.shift();

		return (
			<td className="qm-has-toggle qm-nowrap qm-ltr">
				{trace.length > 0 &&
					<button className="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label={ this.props.toggleLabel }>
						<span aria-hidden="true">+</span>
					</button>
				}
				<ol>
					<li>
						<Frame frame={caller} />
					</li>
					{trace.length > 0 &&
						<div className="qm-toggled">
							{trace.map( frame =>
								<li key={frame.display}>
									<Frame frame={frame} />
								</li>
							)}
						</div>
					}
				</ol>
			</td>
		);
	}

}
