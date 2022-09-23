<?php
/**
 * Abstract output class for HTTP headers.
 *
 * @package query-monitor
 */

abstract class QM_Output_Headers extends QM_Output {

	/**
	 * @return void
	 */
	public function output() {

		$id = $this->collector->id;

		foreach ( $this->get_output() as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				$value = json_encode( $value );
			}

			# Remove illegal characters (Header may not contain NUL bytes)
			$value = str_replace( chr( 0 ), '', $value );

			header( sprintf( 'X-QM-%s-%s: %s', $id, $key, $value ) );
		}

	}

}
