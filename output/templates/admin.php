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
