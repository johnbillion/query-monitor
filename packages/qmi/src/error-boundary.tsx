import * as React from 'react';

import { ErrorPanel } from './error-panel';
import { Warning } from './warning';

interface ErrorBoundaryProps {
	children: React.ReactNode;
}

export class ErrorBoundary extends React.Component<ErrorBoundaryProps, Record<string, unknown>> {
	constructor( props: ErrorBoundaryProps ) {
		super( props );
		this.state = { hasError: false };
	}

	static getDerivedStateFromError( error: unknown ) {
		return { hasError: error };
	}

	render() {
		if ( this.state.hasError ) {
			return (
				<ErrorPanel>
					{ ( this.state.hasError instanceof Error ) ? (
						<>
							<p>
								<Warning>
									An error occurred while rendering this panel:
								</Warning>
							</p>
							<pre>
								{ this.state.hasError.stack }
							</pre>
						</>
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
