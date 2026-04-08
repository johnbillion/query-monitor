import { FileName } from './components/file-name';
import { SourceLocation } from './components/source-location';
import {
	Backtrace,
} from './data-types';
import { resolveFrames } from './frame-lookup';
import * as Utils from './utils';
import { MainContext } from './contexts/main-context';
import { useContext, useState } from 'preact/hooks';

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
	const {
		settings,
	} = useContext( MainContext );
	const [ expanded, setExpanded ] = useState( defaultExpanded );

	const frames = trace?.frames ? resolveFrames( trace.frames ) : [];

	// Frame 0 is always removed from the stack. It either becomes the
	// caller display, or is redundant with the call site.
	const caller = frames.shift() ?? null;
	const hasStack = frames.length > 0;

	if ( ! callsite && ! caller ) {
		return __( 'Unknown', 'query-monitor' );
	}

	return (
		<>
			{ hasStack && ! defaultExpanded && (
				<Toggle
					expanded={ expanded }
					onToggle={ () => setExpanded( ! expanded ) }
					context={ ( callsite?.file ? Utils.stripAbspath( callsite.file, settings ) : undefined ) ?? ( caller ? Utils.frameDisplay( caller ) : undefined ) }
				/>
			) }
			<ol>
				{ callsite && (
					<li>
						<FileName
							text={ callsite.file ? Utils.stripAbspath( callsite.file, settings ) : '' }
							file={ callsite.file }
							line={ callsite.line }
							expanded={ expanded || ! hasStack }
						/>
					</li>
				) }
				{ ! callsite && caller && (
					<li>
						<SourceLocation
							text={ Utils.frameDisplay( caller ) }
							file={ caller.file }
							line={ caller.line }
							expanded={ expanded || ! hasStack }
						/>
					</li>
				) }
				{ hasStack && expanded && (
					frames.map( frame => (
						<li key={ Utils.frameDisplay( frame ) }>
							<SourceLocation
								text={ Utils.frameDisplay( frame ) }
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
