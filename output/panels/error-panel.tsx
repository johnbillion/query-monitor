import { NonTabularPanel } from './non-tabular-panel';
import { type ComponentChildren } from 'preact';

interface Props {
	children: ComponentChildren;
}

export const ErrorPanel = ( { children }: Props ) => (
	<NonTabularPanel>
		<section>
			<div className="qm-error">
				{ children }
			</div>
		</section>
	</NonTabularPanel>
);
