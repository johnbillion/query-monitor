import {
	FrameItem,
} from 'qmi/data-types';
import {
	FileName,
} from 'qmi';
import * as React from 'react';

interface FrameProps {
	frame: FrameItem;
	expanded?: boolean;
	isFileName?: boolean;
}

export const Frame = ( { frame, expanded, isFileName }: FrameProps ) => (
	<>
		<FileName
			text={ frame.display }
			file={ frame.file }
			line={ frame.line }
			isFileName={ isFileName }
		/>
		{ expanded && (
			<>
				<br/>
				<span className="qm-info qm-supplemental">
					@TODO
				</span>
			</>
		) }
	</>
);
