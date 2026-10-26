import * as Utils from '../utils';
import { MainContext } from '../contexts/main-context';
import { Icon } from './icon';
import { useContext } from 'preact/hooks';

import {
	sprintf,
} from '@wordpress/i18n';

export interface SourceLocationProps {
	text: string,
	file: string | null,
	line?: number | null,
	isFileName?: boolean,
	expanded?: boolean,
}

export const SourceLocation = ( { text, file, line = 0, isFileName = false, expanded = false }: SourceLocationProps ) => {
	const {
		editor,
		settings,
	} = useContext( MainContext );

	const displayText = ( isFileName || expanded ) ? text : Utils.shortenFqn( text );

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
	const format = Utils.getEditorFormat( editor ) || ( settings.file_link_format || '' );

	if ( ! format ) {
		if ( isFileName ) {
			return <>{ line ? `${displayText}:${line}` : displayText }</>;
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
							{ `${Utils.stripAbspath( file, settings )}:${line}` }
						</span>
					</>
				) }
			</>
		);
	}

	const output = sprintf(
		format,
		encodeURIComponent( mappedFile ),
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
