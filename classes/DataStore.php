<?php declare(strict_types = 1);
/**
 * Streaming data store.
 *
 * Collected data is by default streamed to a per-request NDJSON file on disk, one record
 * per line, so collectors don't need to hold their full data set in memory and so the
 * data can be read back out-of-band.
 *
 * @package query-monitor
 */

class QM_Data_Store {
	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Absolute path to this request's data file.
	 */
	private string $file;

	/**
	 * Public URL of this request's data file.
	 */
	private string $url;

	/**
	 * Open file handle.
	 *
	 * @var resource|null
	 */
	private $handle = null;

	/**
	 * Whether writing has stopped.
	 */
	private bool $stopped = false;

	/**
	 * Bytes written so far.
	 */
	private int $bytes = 0;

	/**
	 * Per-collector, per-field record counts.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $counts = [];

	private function __construct() {
		$dir = self::get_dir();
		$now = microtime( true );
		$name = sprintf(
			'%s-%03d-%s.ndjson',
			gmdate( 'Y-m-d-H-i-s', (int) $now ),
			(int) ( fmod( $now, 1 ) * 1000 ),
			bin2hex( random_bytes( 16 ) )
		);

		$this->file = "{$dir['path']}/{$name}";
		$this->url = "{$dir['url']}/{$name}";

		register_shutdown_function( [ $this, 'close' ] );
	}

	public static function init() : self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Writes a single record for the given collector and field.
	 *
	 * @param string $collector_id
	 * @param string $field
	 * @param mixed  $record
	 * @return int The index of the record.
	 */
	public function write( string $collector_id, string $field, $record ) : int {
		$index = $this->counts[ $collector_id ][ $field ] ?? 0;
		$this->counts[ $collector_id ][ $field ] = $index + 1;

		$this->append( [
			'c' => $collector_id,
			'f' => $field,
			'd' => $record,
		] );

		return $index;
	}

	/**
	 * Writes the single metadata record for the given collector.
	 *
	 * @param string $collector_id
	 * @param array{enabled: bool, data: mixed} $meta
	 */
	public function write_meta( string $collector_id, array $meta ) : void {
		$this->append( [
			'c' => $collector_id,
			'f' => '@meta',
			'd' => $meta,
		] );
	}

	public function get_url() : string {
		return $this->url;
	}

	/**
	 * The request ID for this request, which is the data file's name.
	 */
	public function get_id() : string {
		return basename( $this->file );
	}

	/**
	 * Whether any data has been written for this request.
	 */
	public function has_data() : bool {
		return ( $this->bytes > 0 );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function append( array $data ) : void {
		if ( $this->stopped ) {
			return;
		}

		$line = wp_json_encode( $data );

		if ( false === $line ) {
			return;
		}

		$handle = $this->get_handle();

		if ( ! $handle ) {
			return;
		}

		$written = fwrite( $handle, "{$line}\n" );

		if ( false === $written ) {
			$this->stopped = true;
			return;
		}

		$this->bytes += $written;

		if ( $this->bytes >= GB_IN_BYTES ) {
			$this->stopped = true;
		}
	}

	/**
	 * Opens the file handle on first use, creating the directory if needed.
	 *
	 * @return ?resource
	 */
	private function get_handle() {
		if ( null === $this->handle && ! $this->stopped ) {
			$dir = dirname( $this->file );

			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}

			$handle = @fopen( $this->file, 'ab' );

			if ( false === $handle ) {
				$this->stopped = true;
			} else {
				$this->handle = $handle;
			}
		}

		return $this->handle;
	}

	public function close() : void {
		if ( is_resource( $this->handle ) ) {
			fclose( $this->handle );
			$this->handle = null;
		}
	}

	/**
	 * The directory the data file is written to.
	 *
	 * @return array{
	 *   path: string,
	 *   url: string,
	 * }
	 */
	public static function get_dir() : array {
		$uploads = wp_get_upload_dir();

		$dir = [
			'path' => "{$uploads['basedir']}/query-monitor",
			'url' => "{$uploads['baseurl']}/query-monitor",
		];

		/**
		 * Filters the directory the streamed data file is written to.
		 *
		 * @since x.y.z
		 *
		 * @param array{path: string, url: string} $dir The data directory path and URL.
		 */
		return apply_filters( 'qm/data/dir', $dir );
	}
}
