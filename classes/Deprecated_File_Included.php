<?php declare(strict_types = 1);
/**
 * Class representing a deprecated file being included.
 *
 * @package query-monitor
 */

/**
 * @extends QM_Wrong<array{
 *   file: string,
 *   replacement: string,
 *   version: string,
 *   message: string,
 * }>
 */
final class QM_Deprecated_File_Included extends QM_Wrong {
	public function get_message(): string {
		[
			'file' => $file,
			'replacement' => $replacement,
			'version' => $version,
			'message' => $message,
		] = $this->args;

		if ( $replacement ) {
			return sprintf(
				/* translators: 1: PHP file name, 2: Version number, 3: Alternative file name, 4: Optional message regarding the change. */
				__( 'File %1$s is deprecated since version %2$s! Use %3$s instead. %4$s', 'query-monitor' ),
				$file,
				$version,
				$replacement,
				$message
			);
		}

		return sprintf(
			/* translators: 1: PHP file name, 2: Version number, 3: Optional message regarding the change. */
			__( 'File %1$s is deprecated since version %2$s with no alternative available. %3$s', 'query-monitor' ),
			$file,
			$version,
			$message
		);
	}
}
