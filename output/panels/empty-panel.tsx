import { NonTabularPanel } from './non-tabular-panel';
import { type ComponentChildren } from 'preact';

interface Props {
	children: ComponentChildren;
}

export const EmptyPanel = ( { children }: Props ) => (
	<NonTabularPanel>
		<section>
			<div className="qm-notice">
				{ children }
			</div>
		</section>
	</NonTabularPanel>
);
