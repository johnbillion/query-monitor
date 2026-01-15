import * as React from 'react';
import * as ReactDOM from 'react-dom';

type Props = {
	adminMenuElement?: HTMLElement;
}

export const Fatal = ( props: Props ) => {
	const adminMenuElement = props.adminMenuElement;

	if ( ! adminMenuElement ) {
		return null;
	}

	return (
		<FatalAdminMenu element={ adminMenuElement }>
			<a
				className="ab-item"
				href="#qm-fatal"
			>
				PHP Fatal Error
			</a>
		</FatalAdminMenu>
	);
};

interface iAdminMenuProps {
	element: HTMLElement;
	children: React.ReactNode;
}

const FatalAdminMenu = ( props: iAdminMenuProps ) => {
	React.useMemo(() => {
		// Clear any existing content in the element
		props.element.innerHTML = '';
		props.element.classList.add( 'qm-error' );
		return true;
	}, [ props.element ]);

	return ReactDOM.createPortal( props.children, props.element );
}
