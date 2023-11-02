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
}

export const Caller = ( { isFileList, trace }: CallerProps ) => {
	const [ expanded, setExpanded ] = React.useState( false );

	// This creates a copy of the frames array.
	const frames = [ ...trace.frames ];

	if ( frames.length === 0 ) {
		return (
			<>
				{ __( 'Unknown', 'query-monitor' ) }
			</>
		);
	}

	const caller = frames.shift();

	return (
		<>
			{ frames.length > 0 && (
				<button
					aria-expanded={ expanded ? 'false' : 'true' }
					aria-label={ __( 'View call stack', 'query-monitor' ) }
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
		</>
	);
};
