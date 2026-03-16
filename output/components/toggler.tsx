import { useState } from 'preact/hooks';
import { ComponentChildren } from 'preact';

import { Toggle } from './toggle';

interface Props {
	summary: ComponentChildren;
	children: ComponentChildren;
}

export const Toggler = ( { summary, children }: Props ) => {
	const [ expanded, setExpanded ] = useState( false );

	return (
		<>
			<Toggle
				expanded={ expanded }
				onToggle={ () => setExpanded( ! expanded ) }
			/>
			{ summary }
			{ expanded && children }
		</>
	);
};
