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

		if ( ! isset( $this->queries[ $i ] ) ) {
			return $result;
		}

		$this->queries[ $i ]['trace'] = new QM_Backtrace();

		if ( ! isset( $this->queries[ $i ][3] ) ) {
			$this->queries[ $i ][3] = $this->time_start;
		}

		if ( $this->last_error ) {
			$code = 'qmdb';

			if ( $this->dbh instanceof mysqli ) {
				$code = mysqli_errno( $this->dbh );
			}

			$this->queries[ $i ]['result'] = new WP_Error( $code, $this->last_error );
		} else {
			$this->queries[ $i ]['result'] = $result;
		}

		return $result;
	}

}
