import { type ComponentChildren } from 'preact';
import { useContext } from 'preact/hooks';
import { PanelContext } from '../contexts/panel-context';

interface Props {
	title: string;
	children: ComponentChildren;
}

export const NonTabularPanel = ( { title, children }: Props ) => {
	const {
		id,
	} = useContext( PanelContext );

	return (
		<div
			aria-labelledby="qm-panel-title"
			className="qm qm-panel-show qm-non-tabular"
			id={ `qm-${id}` }
			role="tabpanel"
			// Allows programmatic focus when switching tabs.
			tabIndex={ -1 }
		>
			<div className="qm-boxed">
				<h2 className="qm-screen-reader-text" id="qm-panel-title">
					{ title }
				</h2>
				{ children }
			</div>
		</div>
	);
};
