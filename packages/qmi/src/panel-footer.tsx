import * as React from "react";

interface iPanelFooterProps {
	cols: number;
	label: string;
	count: number;
}

export class PanelFooter extends React.Component<iPanelFooterProps, {}> {

	render() {
		return (
			<tfoot>
				<tr>
					<td colSpan={this.props.cols}>
						{this.props.label}
						&nbsp;
						<span className="qm-items-number">{QM_i18n.number_format( this.props.count )}</span>
					</td>
					{this.props.children}
				</tr>
			</tfoot>
		);
	}

}
