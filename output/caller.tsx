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

import { Toggle } from './components/toggle';

export type { CallSite } from './data-types';

interface Props {
	isFileList?: boolean;
	trace?: Backtrace | null;
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
				<Toggle
					expanded={ expanded }
					onToggle={ () => setExpanded( ! expanded ) }
					context={ caller?.display }
				/>
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
