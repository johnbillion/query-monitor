import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

import { Toggle } from './components/toggle';

interface Props {
	stack?: string[];
	defaultExpanded?: boolean;
}

export const StackCaller = ( { stack, defaultExpanded = false }: Props ) => {
	const [ expanded, setExpanded ] = React.useState( defaultExpanded );

	if ( ! stack?.length ) {
		return (
			<>
				{ __( 'Unknown', 'query-monitor' ) }
			</>
		);
	}

	const [ caller, ...frames ] = stack;
	const hasStack = frames.length > 0;

	return (
		<>
			{ hasStack && ! defaultExpanded && (
				<Toggle
					expanded={ expanded }
					onToggle={ () => setExpanded( ! expanded ) }
					context={ caller }
				/>
			) }
			<ol>
				<li>
					<code>{ caller }</code>
				</li>
				{ hasStack && expanded && (
					frames.map( ( frame, i ) => (
						<li key={ i }>
							<code>{ frame }</code>
						</li>
					) )
				) }
			</ol>
		</>
	);
};
