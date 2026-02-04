import { type ComponentChildren } from 'preact';
import { useContext } from 'preact/hooks';
import { PanelContext } from '../contexts/panel-context';

interface Props {
	children: ComponentChildren;
}

export const Panel = ( { children }: Props ) => {
	const {
		id,
	} = useContext( PanelContext );

	return (
		<div
			aria-labelledby="qm-panel-title"
			className="qm qm-panel-show"
			id={ `qm-${id}` }
			role="tabpanel"
			tabIndex={ -1 }
		>
			{ children }
		</div>
	);
};
