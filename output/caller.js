import React, { Component } from 'react';
import Frame from './frame.js';

const { __, _x, _n, sprintf } = wp.i18n;

class Caller extends Component {

	render() {
		const trace = this.props.trace;
		const caller = trace.shift();

		return (
			<td class="qm-has-toggle qm-nowrap qm-ltr">
				{trace.length > 0 &&
					<button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label={__( 'Toggle more information', 'query-monitor' )}>
						<span aria-hidden="true">+</span>
					</button>
				}
				<ol>
					<li><Frame frame={caller} /></li>
					{trace.length > 0 &&
						<>
							<div class="qm-toggled">
								{trace.map(frame =>
									<li><Frame frame={frame} /></li>
								)}
							</div>
						</>
					}
				</ol>
			</td>
		);
	}

}

export default Caller;
