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

export const Caller = ( { isFileList, trace, toggleLabel }: CallerProps ) => {
		const [ expanded, setExpanded ] = React.useState( false );

		const frames = trace.frames;

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
						aria-expanded={ expanded ? 'false' : 'true' }
						aria-label={ toggleLabel }
						className="qm-toggle"
						onClick={ () => setExpanded( ! expanded ) }
					>
						<span aria-hidden="true">
							{ expanded ? '-' : '+' }
						</span>
					</button>
				) }
				<ol>
					<li>
						<Frame
							expanded={ expanded }
							frame={ caller }
							isFileName={ isFileList }
						/>
					</li>
					{ frames.length > 0 && expanded && (
						frames.map( frame => (
							<li key={ frame.display }>
								<Frame
									expanded
									frame={ frame }
									isFileName={ isFileList }
								/>
							</li>
						) )
					) }
				</ol>
			</td>
		);
	}
