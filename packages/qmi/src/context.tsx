import {
	createContext,
} from 'react';

export const Context = createContext( {
	editor: '',
	setEditor: ( editor: string ) => {},
	theme: 'auto',
	setTheme: ( theme: string ) => {},
} );
