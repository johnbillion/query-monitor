import { type ComponentChildren } from 'preact';
import { useErrorBoundary, useState } from 'preact/hooks';
import { __ } from '@wordpress/i18n';

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
						aria-label={ __( 'Copy error to clipboard', 'query-monitor' ) }
						onClick={ copyToClipboard }
						style={ {
							position: 'absolute',
							top: 0,
							right: 0,
						} }
					>
						{ copied ? __( 'Copied!', 'query-monitor' ) : __( 'Copy', 'query-monitor' ) }
					</button>
					<p>
						<Warning>
							{ __( 'An error occurred in this panel:', 'query-monitor' ) }
						</Warning>
					</p>
					<pre>
						{ error.stack }
					</pre>
				</div>
			) : (
				<p>
					<Warning>
						{ __( 'An unknown error occurred in this panel.', 'query-monitor' ) }
					</Warning>
				</p>
			) }
		</ErrorPanel>
	);
};
