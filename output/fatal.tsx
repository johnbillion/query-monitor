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

class AdminMenu extends React.Component<iAdminMenuProps, Record<string, unknown>> {
	constructor( props: iAdminMenuProps ) {
		super( props );

		this.props.element.innerHTML = '';
		this.props.element.classList.add( 'qm-error' );
	}

	render() {
		return ReactDOM.createPortal( this.props.children, this.props.element );
	}
}
