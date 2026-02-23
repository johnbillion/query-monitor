import { FileName } from './components/file-name';
import { SourceLocation } from './components/source-location';
import {
	Backtrace,
	CallSite,
} from './data-types';
import { useState } from 'preact/hooks';

import {
	__,
} from '@wordpress/i18n';

import { Toggle } from './components/toggle';

interface Props {
	trace?: Backtrace | null;
	callsite?: CallSite;
	defaultExpanded?: boolean;
}

export const Caller = ( { isFileList, trace, callsite, defaultExpanded = false }: Props ) => {
	const [ expanded, setExpanded ] = useState( defaultExpanded );

	// This creates a copy of the frames array.
	const frames = trace?.frames ? [ ...trace.frames ] : [];

	// When a call site is present it serves as the caller, so don't shift from the stack.
	const caller = callsite ? null : frames.shift();
	const hasStack = frames.length > 0;

	if ( ! callsite && ! caller ) {
		return (
			<>
				{ __( 'Unknown', 'query-monitor' ) }
			</>
		);
	}

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
							expanded={ expanded || ! hasStack }
						/>
					</li>
				) }
				{ caller && (
					<li>
						<SourceLocation
							text={ caller.display }
							file={ caller.file }
							line={ caller.line }
							expanded={ expanded || ! hasStack }
						/>
					</li>
				) }
				{ hasStack && expanded && (
					frames.map( frame => (
						<li key={ frame.display }>
							<SourceLocation
								text={ frame.display }
								file={ frame.file }
								line={ frame.line }
								expanded
							/>
						</li>
					) )
				) }
			</ol>
		</>
	);
};
