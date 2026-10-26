import { NonTabularPanel } from './non-tabular-panel';
import { type ComponentChildren } from 'preact';
import { __ } from '@wordpress/i18n';

interface Props {
	children: ComponentChildren;
}

export const EmptyPanel = ( { children }: Props ) => (
	<NonTabularPanel title={ __( 'No Data', 'query-monitor' ) }>
		<section className="qm-empty">
			{ children }
		</section>
	</NonTabularPanel>
);
