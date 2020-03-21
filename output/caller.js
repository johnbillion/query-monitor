import React, { Component } from 'react';
import { Frame } from './utils';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

export class Caller extends Component {

	render() {
		const trace = this.props.trace;
		const caller = trace.shift();

		return (
			<td className="qm-has-toggle qm-nowrap qm-ltr">
				{trace.length > 0 &&
					<button className="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label={__( 'Toggle more information', 'query-monitor' )}>
						<span aria-hidden="true">+</span>
					</button>
				}
				<ol>
					<li>
						<Frame frame={caller} />
					</li>
					{trace.length > 0 &&
						<div className="qm-toggled">
							{trace.map(frame =>
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
