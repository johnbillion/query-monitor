import { Duration } from './duration';

interface Props {
	rows: {
		ltime: number;
	}[];
}

export const TotalTime = ( { rows }: Props ) => (
	<Duration value={ rows.reduce( ( a, b ) => a + b.ltime, 0 ) }/>
);
