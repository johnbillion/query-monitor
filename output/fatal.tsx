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
		<AdminMenu element={ adminMenuElement }>
			<a
				className="ab-item"
				href="#qm-fatal"
			>
				PHP Fatal Error
			</a>
		</AdminMenu>
	);
};

interface iAdminMenuProps {
	element: HTMLElement;
	children: React.ReactNode;
}

const AdminMenu = ( props: iAdminMenuProps ) => {
	React.useMemo(() => {
		props.element.classList.add( 'qm-error' );
		return true;
	}, []);

	return ReactDOM.createPortal( props.children, props.element );
}
