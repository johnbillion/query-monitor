import React, { Component } from 'react';

class PanelFooter extends Component {

	render() {
		return (
			<tfoot>
				<tr>
					<td colSpan={this.props.cols}>
						{this.props.label}
						&nbsp;
						<span class="qm-items-number">{QM_i18n.number_format( this.props.count )}</span>
					</td>
					{this.props.children}
				</tr>
			</tfoot>
		);
	}

}

export default PanelFooter;
