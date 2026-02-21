import * as Utils from '../utils';
interface Props {
	value: number;
}

export const Time = ( { value }: Props ) => (
	<>
		{ Utils.numberFormat( value, 4 ) }
	</>
);
