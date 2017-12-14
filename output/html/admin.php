<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_Admin extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
	}

	public function template() {
		?>
		<div class="qm qm-half" id="{{ data._collector.id }}">
			<table cellspacing="0">
				<caption>{{ data._collector.name }}</caption>
				<thead class="screen-reader-text">
					<tr>
						<th><?php esc_html_e( 'Data', 'query-monitor' ); ?></th>
						<th><?php esc_html_e( 'Property', 'query-monitor' ); ?></th>
						<th><?php esc_html_e( 'Value', 'query-monitor' ); ?></th>
					</tr>
				</thead>
				<tbody class="qm-group">
					<# var first = true; for ( var key in data.current_screen ) { #>
						<tr>
							<# if ( first ) { #>
								<th class="qm-ltr" rowspan="{{ Object.keys( data.current_screen ).length }}">get_current_screen()</th>
							<# } #>
							<td>{{ key }}</td>
							<td>{{ data.current_screen[ key ] }}</td>
						</tr>
					<# first = false; } #>
				</tbody>
				<tbody>
					<tr>
						<th class="qm-ltr">$pagenow</th>
						<td colspan="2">{{ data.pagenow }}</td>
					</tr>
					<# if ( data.list_table ) { #>
						<tr>
							<th rowspan="2"><?php esc_html_e( 'Column Filters', 'query-monitor' ); ?></th>
							<td colspan="2">{{{ data.list_table_markup.columns_filter }}}</td>
						</tr>
						<tr>
							<td colspan="2">{{{ data.list_table_markup.sortables_filter }}}</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Column Action', 'query-monitor' ); ?></th>
							<td colspan="2">{{{ data.list_table_markup.column_action }}}</td>
						</tr>
					<# } #>
				</tbody>
			</table>
		</div>
		<?php
	}

}

function register_qm_output_html_admin( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'admin' ) ) {
		$output['admin'] = new QM_Output_Html_Admin( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_admin', 70, 2 );
