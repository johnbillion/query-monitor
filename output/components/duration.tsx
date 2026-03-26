import { useContext } from 'preact/hooks';
import * as Utils from '../utils';
import { MainContext } from '../contexts/main-context';

interface Props {
	value: number;
	secondsLabel?: boolean;
}

export const Duration = ( { value, secondsLabel = false }: Props ) => {
	const { durationUnit } = useContext( MainContext );

	if ( durationUnit === 'ms' ) {
		return <>{ Utils.numberFormat( value * 1000, 1 ) } ms</>;
	}

	const formatted = Utils.formatDuration( value );

	return secondsLabel ? `${ formatted } seconds` : formatted;
};
