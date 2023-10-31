import {
	Utils,
	Context,
} from 'qmi';
import * as React from 'react';

import {
	sprintf,
} from '@wordpress/i18n';

interface FileProps {
	text: string,
	file: string,
	line?: number,
	isFileName?: boolean,
}

export const FileName = ( { text, file, line = 0, isFileName = false }: FileProps ) => {
	const {
		editor,
	} = React.useContext( Context );

	if ( ! file ) {
		return ( isFileName )
			? <> { text } </>
			: <code>{ text }</code>;
	}

	const linkLine = line || 1;
	const format = Utils.getEditorFormat( editor );

	if ( ! format ) {
		let displayValue = file;

		if ( line ) {
			displayValue += `:${ line }`;
		}

		return ( isFileName )
			? <> { displayValue } </>
			: <code>{ displayValue }</code>;
	}

	const output = sprintf(
		format,
		file, // @todo rawurlencode
		linkLine
	);

	return ( isFileName )
		? <a href={ output }>{ output }</a>
		: <a href={ output }><code>{ text }</code></a>;
};
