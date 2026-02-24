import { type ComponentChildren } from 'preact';
import { useErrorBoundary, useState } from 'preact/hooks';

import { ErrorPanel } from './error-panel';
import { Warning } from '../components/warning';

interface Props {
	children: ComponentChildren;
}

export const ErrorBoundary = ( { children }: Props ) => {
	const [error] = useErrorBoundary();
	const [copied, setCopied] = useState( false );

	const copyToClipboard = () => {
		if ( error instanceof Error ) {
			navigator.clipboard.writeText( error.stack || '' ).then( () => {
				setCopied( true );
				setTimeout( () => setCopied( false ), 2000 );
			} );
		}
	};

	if ( ! error ) {
		return <>{ children }</>;
	}

	return (
		<ErrorPanel>
			{ ( error instanceof Error ) ? (
				<div style={ { position: 'relative' } }>
					<button
						onClick={ copyToClipboard }
						style={ {
							position: 'absolute',
							top: 0,
							right: 0,
						} }
					>
						{ copied ? 'Copied!' : 'Copy' }
					</button>
					<p>
						<Warning>
							An error occurred while rendering this panel:
						</Warning>
					</p>
					<pre>
						{ error.stack }
					</pre>
				</div>
			) : (
				<p>
					<Warning>
						An unknown error occurred while rendering this panel.
					</Warning>
				</p>
			) }
		</ErrorPanel>
	);
};
