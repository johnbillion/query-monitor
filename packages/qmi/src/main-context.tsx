import {
	createContext,
} from 'react';

export const MainContext = createContext( {
	editor: '',
	setEditor: ( editor: string ) => {},
	theme: 'auto',
	setTheme: ( theme: string ) => {},
} );
