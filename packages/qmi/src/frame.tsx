import {
	FrameItem,
} from 'qmi/data-types';
import * as React from 'react';

interface FrameProps {
	frame: FrameItem;
	expanded?: boolean;
	isFileName?: boolean;
}

export const Frame = ( { frame, expanded }: FrameProps ) => (
			<>
				<code>{ frame.display }</code>
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
