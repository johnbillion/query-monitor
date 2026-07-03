import { NonTabularPanel } from './non-tabular-panel';
import { type ComponentChildren } from 'preact';
import { __ } from '@wordpress/i18n';

interface Props {
	children: ComponentChildren;
}

export const ErrorPanel = ( { children }: Props ) => (
	<NonTabularPanel title={ __( 'Error', 'query-monitor' ) }>
		<section className="qm-error">
			{ children }
		</section>
	</NonTabularPanel>
);
