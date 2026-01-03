import { Frame } from './frame';
import { FileName } from './components/file-name';
import {
	Backtrace,
	CallSite,
} from './data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export type { CallSite } from './data-types';

interface Props {
	isFileList?: boolean;
	trace?: Backtrace;
	callsite?: CallSite;
	defaultExpanded?: boolean;
}

export const Caller = ( { isFileList, trace, callsite, defaultExpanded = false }: Props ) => {
	const [ expanded, setExpanded ] = React.useState( defaultExpanded );

	// This creates a copy of the frames array.
	const frames = trace?.frames ? [ ...trace.frames ] : [];

	// If we have a callsite but no frames, show just the callsite
	if ( frames.length === 0 && ! callsite ) {
		return (
			<>
				{ __( 'Unknown', 'query-monitor' ) }
			</>
		);
	}

	const caller = frames.shift();
	const hasStack = frames.length > 0;

	return (
		<>
			{ hasStack && ! defaultExpanded && (
				<button
					aria-expanded={ expanded ? 'false' : 'true' }
					aria-label={ __( 'Toggle full call stack', 'query-monitor' ) }
					className="qm-toggle"
					onClick={ () => setExpanded( ! expanded ) }
				>
					<span aria-hidden="true">
						{ expanded ? '-' : '+' }
					</span>
				</button>
			) }
			<ol>
				{ callsite && (
					<li>
						<FileName
							text={ callsite.filename }
							file={ callsite.file }
							line={ callsite.line }
							isFileName={ isFileList }
							expanded={ expanded || ! hasStack }
						/>
					</li>
				) }
				{ caller && (
					<li>
						<Frame
							expanded={ expanded || ! hasStack }
							frame={ caller }
							isFileName={ isFileList }
						/>
					</li>
				) }
				{ hasStack && expanded && (
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
