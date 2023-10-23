import { Frame } from 'qmi';
import {
	Backtrace,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

interface CallerProps {
	isFileList?: boolean;
	trace: Backtrace;
	toggleLabel: string;
}

interface iState {
	expanded: boolean;
}

export class Caller extends React.Component<CallerProps, iState> {
	constructor( props: CallerProps ) {
		super( props );

		this.state = {
			expanded: false,
		};
	}

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
					<button
						aria-expanded={ this.state.expanded ? 'false' : 'true' }
						aria-label={ this.props.toggleLabel }
						className="qm-toggle"
						onClick={ () => this.setState( { expanded: ! this.state.expanded } ) }
					>
						<span aria-hidden="true">
							{ this.state.expanded ? '-' : '+' }
						</span>
					</button>
				) }
				<ol>
					<li>
						<Frame
							expanded={ this.state.expanded }
							frame={ caller }
							isFileName={ this.props.isFileList }
						/>
					</li>
					{ frames.length > 0 && this.state.expanded && (
						frames.map( frame => (
							<li key={ frame.display }>
								<Frame
									expanded
									frame={ frame }
									isFileName={ this.props.isFileList }
								/>
							</li>
						) )
					) }
				</ol>
			</td>
		);
	}

}
