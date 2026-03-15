<?php declare(strict_types = 1);
/**
 * Stack trace frame for internal use. Not to be confused with the QM_Data_Stack_Frame class used for output.
 *
 * @package query-monitor
 */

class QM_Trace_Frame {
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $display;

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
	 * @var ?array<int, string>
	 */
	public $args;

	public function to_data(): QM_Data_Stack_Frame {
		$data = new QM_Data_Stack_Frame();
		$data->id = $this->id;
		$data->display = $this->display;
		$data->file = $this->file;
		$data->line = $this->line;

		return $data;
	}
}
