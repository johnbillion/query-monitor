import { FileName } from './components/file-name';
import { SourceLocation } from './components/source-location';
import {
	Backtrace,
} from './data-types';
import { useState } from 'preact/hooks';

import {
	__,
} from '@wordpress/i18n';

import { Toggle } from './components/toggle';

interface Props {
	trace?: Backtrace | null;
	defaultExpanded?: boolean;
}

export const Caller = ( { trace, defaultExpanded = false }: Props ) => {
	const callsite = trace?.callsite;
	const [ expanded, setExpanded ] = useState( defaultExpanded );

	// This creates a copy of the frames array.
	const frames = trace?.frames ? [ ...trace.frames ] : [];

	// Frame 0 is always removed from the stack. It either becomes the
	// caller display, or is redundant with the call site.
	const caller = frames.shift() ?? null;
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
					context={ callsite?.filename ?? caller?.display }
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
				{ ! callsite && caller && (
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
