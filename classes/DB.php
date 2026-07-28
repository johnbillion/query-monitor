<?php declare(strict_types = 1);
/**
 * Database class used by the database dropin.
 *
 * @package query-monitor
 */

class QM_DB extends wpdb {

	/**
	 * @var float
	 */
	public $time_start;

	/**
	 * Performs a MySQL database query, using current database connection.
	 *
	 * @see wpdb::query()
	 *
	 * @param string $query Database query
	 * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows
	 *                  affected/selected for all other queries. Boolean false on error.
	 */
	public function query( $query ) {
		if ( $this->show_errors ) {
			$this->hide_errors();
		}

		$result = parent::query( $query );
		$i = $this->num_queries - 1;

		if ( did_action( 'qm/cease' ) ) {
			// It's not possible to prevent the parent class from logging queries because it reads
			// the `SAVEQUERIES` constant and I don't want to override more methods than necessary.
			$this->queries = array();
		}

		// In some environments `$this->queries[ $i ]` can hold a non-array value.
		// Writing an array offset to it is a fatal error on PHP 8, so treat that
		// the same as a missing entry and bail.
		// See https://github.com/johnbillion/query-monitor/issues/1120.
		if ( ! is_array( $this->queries[ $i ] ?? null ) ) {
			return $result;
		}

		$this->queries[ $i ]['trace'] = new QM_Backtrace( array(
			'time' => $this->time_start,
		) );

		if ( $this->last_error && ! $this->suppress_errors ) {
			$code = 'qmdb';

			// This needs to remain in place to account for a user still on PHP 5. Don't want to kill their site.
			if ( $this->dbh instanceof mysqli ) {
				$code = mysqli_errno( $this->dbh );
			}

			$this->queries[ $i ]['result'] = new WP_Error( $code, $this->last_error );
		} else {
			$this->queries[ $i ]['result'] = (int) $result;
		}

		return $result;
	}

}
