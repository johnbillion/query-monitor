<?php
/*
Copyright 2009-2016 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Languages extends QM_Collector {

	public $id = 'languages';

	public function name() {
		return __( 'Languages', 'query-monitor' );
	}

	public function __construct() {

		parent::__construct();

		add_filter( 'override_load_textdomain', array( $this, 'log_file_load' ), 99, 3 );

	}

	public function process() {
		$this->data['locale'] = get_locale();
	}

	/**
	 * Store log data.
	 *
	 * @param bool   $override Whether to override the text domain. Default false.
	 * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
	 * @param string $mofile   Path to the MO file.
	 * @return bool
	 */
	public function log_file_load( $override, $domain, $mofile ) {

		$trace    = new QM_Backtrace;
		$filtered = $trace->get_filtered_trace();
		$caller   = array();

		foreach ( $filtered as $i => $item ) {

			if ( in_array( $item['function'], array(
				'load_plugin_textdomain',
				'load_theme_textdomain',
				'load_default_textdomain',
			), true ) ) {
				$caller = $item;
				$display = $i + 1;
				if ( isset( $filtered[ $display ] ) ) {
					$caller['display'] = $filtered[ $display ]['display'];
				}
				break;
			}

		}

		if ( empty( $caller ) ) {
			if ( isset( $filtered[1] ) ) {
				$caller = $filtered[1];
			} else {
				$caller = $filtered[0];
			}
		}

		if ( ! isset( $caller['file'] ) && isset( $filtered[0]['file'] ) && isset( $filtered[0]['line'] ) ) {
			$caller['file'] = $filtered[0]['file'];
			$caller['line'] = $filtered[0]['line'];
		}

		$this->data['languages'][] = array(
			'caller' => $caller,
			'domain' => $domain,
			'mofile' => $mofile,
			'found'  => file_exists( $mofile ) ? filesize( $mofile ): false,
		);

		return $override;

	}

}

# Load early to catch early errors
QM_Collectors::add( new QM_Collector_Languages );
