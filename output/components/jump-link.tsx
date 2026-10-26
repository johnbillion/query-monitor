import { type ComponentChildren } from 'preact';
import { useContext } from 'preact/hooks';
import { MainContext } from '../contexts/main-context';

interface Props {
	targetPanel: string;
	rowIndex: number;
	children: ComponentChildren;
}

export const JumpLink = ( { targetPanel, rowIndex, children }: Props ) => {
	const { switchToPanel } = useContext( MainContext );

	return (
		<button
			className="qm-filter-trigger"
			onClick={ () => {
				switchToPanel( targetPanel, {}, rowIndex );
			} }
		>
			{ children }
		</button>
	);
};
