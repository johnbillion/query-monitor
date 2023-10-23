import { Frame, Notice } from 'qmi';
import {
	Backtrace,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

interface CallerProps {
	trace: Backtrace;
	toggleLabel: string;
}

export class Caller extends React.Component<CallerProps, Record<string, unknown>> {

	render() {
		const frames = [ ...this.props.trace.frames ];

		if ( frames.length === 0 ) {
			return (
				<td>
					{ __( 'Unknown', 'query-monitor' ) }
				</td>
			);
		}

		const caller = frames.shift();

		return (
			<td className="qm-has-toggle qm-nowrap qm-ltr">
				{ frames.length > 0 && (
					<button aria-expanded="false" aria-label={ this.props.toggleLabel } className="qm-toggle" data-off="-" data-on="+">
						<span aria-hidden="true">+</span>
					</button>
				) }
				<ol>
					<li>
						<Frame frame={ caller } />
					</li>
					{ frames.length > 0 && (
						<div className="qm-toggled">
							{ frames.map( frame => (
								<li key={ frame.display }>
									<Frame frame={ frame } />
								</li>
							) ) }
						</div>
					) }
				</ol>
			</td>
		);
	}

}
