<?php declare(strict_types = 1);
/**
 * Frame registry for deduplicating stack trace frames in JSON output.
 *
 * Frames are keyed by their ID but its numeric index is also stored for memory-efficient JSON output.
 *
 * @package query-monitor
 */

class QM_Frame_Registry {

	/**
	 * Unique frames keyed by "id\0args\0file".
	 *
	 * @var array<string, int>
	 */
	private static $index = array();

	/**
	 * Ordered list of unique frames.
	 *
	 * @var list<QM_Data_Stack_Frame>
	 */
	private static $frames = array();

	/**
	 * Register a frame (without line number) and return its index.
	 */
	public static function register( QM_Data_Stack_Frame $frame ): int {
		$key = $frame->id . "\0" . ( $frame->args ?? '' ) . "\0" . ( $frame->file ?? '' );

		if ( isset( self::$index[ $key ] ) ) {
			return self::$index[ $key ];
		}

		$count = count( self::$frames );

		$lookup_frame = new QM_Data_Stack_Frame();
		$lookup_frame->id = $frame->id;
		$lookup_frame->file = $frame->file;

		if ( $frame->args !== null ) {
			$lookup_frame->args = $frame->args;
		} else {
			unset( $lookup_frame->args );
		}

		unset( $lookup_frame->line );

		self::$frames[] = $lookup_frame;
		self::$index[ $key ] = $count;

		return $count;
	}

	public static function get_frame( int $index ): QM_Data_Stack_Frame {
		return self::$frames[ $index ];
	}

	/**
	 * @return list<QM_Data_Stack_Frame>
	 */
	public static function get_frames(): array {
		return self::$frames;
	}
}
