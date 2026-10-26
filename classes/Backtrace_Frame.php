<?php declare(strict_types = 1);
/**
 * Stack trace frame for internal use. Not to be confused with the QM_Data_Stack_Frame class used for output.
 *
 * @package query-monitor
 */

class QM_Backtrace_Frame {
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string|null
	 */
	public $args;

	/**
	 * @var string|null
	 */
	public $file;

	/**
	 * @var int|null
	 */
	public $line;

	/**
	 * @var ?string
	 */
	public $function;

	/**
	 * @var ?string
	 */
	public $class;

	/**
	 * Whether this frame should keep its original file/line
	 * instead of being shifted during serialization.
	 *
	 * @var bool
	 */
	public $keep_file_line = false;


	public function to_data(): QM_Data_Stack_Frame {
		$data = new QM_Data_Stack_Frame();
		$data->id = $this->id;
		$data->args = $this->args;
		$data->file = $this->file;
		$data->line = $this->line;

		return $data;
	}
}
