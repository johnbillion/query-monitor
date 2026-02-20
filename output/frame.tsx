import {
	StackFrame,
} from './data-types';
import {
	FileName,
} from './components/file-name';
interface Props {
	frame: StackFrame;
	expanded?: boolean;
	isFileName?: boolean;
}

export const Frame = ( { frame, expanded, isFileName }: Props ) => (
	<>
		<FileName
			text={ frame.display }
			file={ frame.file }
			line={ frame.line }
			isFileName={ isFileName }
			expanded={ expanded }
		/>
	</>
);
