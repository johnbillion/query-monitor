import * as React from 'react';

import { ErrorPanel } from './error-panel';
import { Warning } from '../components/warning';

interface Props {
	children: React.ReactNode;
}

interface State {
	hasError: Error | false;
	copied: boolean;
}

export class ErrorBoundary extends React.Component<Props, State> {
	constructor( props: Props ) {
		super( props );
		this.state = { hasError: false, copied: false };
	}

	copyToClipboard = () => {
		if ( this.state.hasError instanceof Error ) {
			navigator.clipboard.writeText( this.state.hasError.stack || '' ).then( () => {
				this.setState( { copied: true } );
				setTimeout( () => this.setState( { copied: false } ), 2000 );
			} );
		}
	};

	static getDerivedStateFromError( error: unknown ) {
		return { hasError: error };
	}

	render() {
		if ( this.state.hasError ) {
			return (
				<ErrorPanel>
					{ ( this.state.hasError instanceof Error ) ? (
						<div style={ { position: 'relative' } }>
							<button
								onClick={ this.copyToClipboard }
								style={ {
									position: 'absolute',
									top: 0,
									right: 0,
								} }
							>
								{ this.state.copied ? 'Copied!' : 'Copy' }
							</button>
							<p>
								<Warning>
									An error occurred while rendering this panel:
								</Warning>
							</p>
							<pre>
								{ this.state.hasError.stack }
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
		}

		return this.props.children;
	}
}
