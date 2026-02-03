import * as Utils from '../utils';
import { MainContext } from '../contexts/main-context';
import { Icon } from './icon';
import * as React from 'react';

import {
	sprintf,
} from '@wordpress/i18n';

interface Props {
	text: string,
	file: string,
	line?: number,
	isFileName?: boolean,
	expanded?: boolean,
}

export const FileName = ( { text, file, line = 0, isFileName = false, expanded = false }: Props ) => {
	const {
		editor,
		settings,
	} = React.useContext( MainContext );

	const displayText = isFileName ? text : Utils.shortenFqn( text );

	if ( ! file ) {
		return ( isFileName )
			? <>{ displayText }</>
			: <code>{ displayText }</code>;
	}

	let mappedFile = file;

	for ( const [ source, replacement ] of Object.entries( settings.file_path_map ) ) {
		if ( mappedFile.startsWith( source ) ) {
			mappedFile = replacement + mappedFile.slice( source.length );
			break;
		}
	}

	const linkLine = line || 1;
	const format = Utils.getEditorFormat( editor );

	if ( ! format ) {
		if ( isFileName ) {
			return <>{ displayText }</>;
		}

		return (
			<>
				<code>
					{ displayText }
				</code>
				{ expanded && (
					<>
						<br/>
						<span className="qm-info qm-supplemental">
							{ `${mappedFile}:${line}` }
						</span>
					</>
				) }
			</>
		);
	}

	const output = sprintf(
		format,
		mappedFile, // @todo rawurlencode
		linkLine
	);

	return (
		<a className="qm-edit-link" href={ output }>
			{ isFileName ?
				<>{ displayText }</>
				:
				<code>{ displayText }</code>
			}
			<Icon name="edit"/>
		</a>
	);
};
