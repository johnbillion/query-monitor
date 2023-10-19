import * as React from 'react';

import { ErrorMessage } from './error';
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
				<ErrorMessage id="qm-error-boundary">
					{ ( this.state.hasError instanceof Error ) ? (
						<>
							<p>
								<Warning /> An error occurred while rendering this panel:
							</p>
							<pre>
								{ this.state.hasError.stack }
							</pre>
						</>
					) : (
						<p>
							<Warning /> An unknown error occurred while rendering this panel.
						</p>
					) }
				</ErrorMessage>
			);
		}

		return this.props.children;
	}
}
