import { NonTabularPanel } from './non-tabular-panel';
import { type ComponentChildren } from 'preact';

interface Props {
	children: ComponentChildren;
}

export const EmptyPanel = ( { children }: Props ) => (
	<NonTabularPanel>
		<section className="qm-empty">
			{ children }
		</section>
	</NonTabularPanel>
);
