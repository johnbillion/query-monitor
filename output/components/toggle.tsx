import {
	__,
	sprintf,
} from '@wordpress/i18n';

interface Props {
	expanded: boolean;
	onToggle: () => void;
	context?: string;
}

export const Toggle = ( { expanded, onToggle, context }: Props ) => {
	const label = context
		? sprintf(
			/* translators: %s: Context for the toggle button, e.g. a function name */
			__( 'Toggle full call stack for %s', 'query-monitor' ),
			context
		)
		: __( 'Toggle full call stack', 'query-monitor' );

	return (
		<button
			aria-expanded={ expanded ? 'false' : 'true' }
			aria-label={ label }
			className="qm-toggle"
			onClick={ onToggle }
		>
			<span aria-hidden="true">
				{ expanded ? '-' : '+' }
			</span>
		</button>
	);
};
