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
	const { element, children } = props;

	React.useLayoutEffect(() => {
		// Clear any existing content in the element
		element.innerHTML = '';
		element.classList.add( 'qm-error' );
	}, [element]);

	return ReactDOM.createPortal( children, element );
}
