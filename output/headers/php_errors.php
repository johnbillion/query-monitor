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

class QM_Output_Headers_PHP_Errors extends QM_Output_Headers {

	public function get_output() {

		$data = $this->collector->get_data();
		$headers = array();

		if ( empty( $data['errors'] ) ) {
			return array();
		}

		$count = 0;

		foreach ( $data['errors'] as $type => $errors ) {

			foreach ( $errors as $key => $error ) {

				$count++;

				# @TODO we should calculate the component during process() so we don't need to do it
				# separately in each output.
				$component = $error->trace->get_component();
				$output_error = array(
					'type'      => $error->type,
					'message'   => wp_strip_all_tags( $error->message ),
					'file'      => $error->file,
					'line'      => $error->line,
					'stack'     => $error->trace->get_stack(),
					'component' => $component->name,
				);

				$key = sprintf( 'error-%d', $count );
				$headers[ $key ] = json_encode( $output_error );

			}

		}

		return array_merge( array(
			'error-count' => $count,
		), $headers );

	}

}

function register_qm_output_headers_php_errors( array $output, QM_Collectors $collectors ) {
	if ( $collector = QM_Collectors::get( 'php_errors' ) ) {
		$output['php_errors'] = new QM_Output_Headers_PHP_Errors( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/headers', 'register_qm_output_headers_php_errors', 110, 2 );
