<?php declare(strict_types = 1);
/**
 * Class representing something that's kind of wrong.
 *
 * @package query-monitor
 */

/**
 * @template TArgs of array
 */
abstract class QM_Wrong implements JsonSerializable {
	private QM_Backtrace $backtrace;

	/**
	 * @phpstan-var TArgs $args
	 */
	protected array $args;

	/**
	 * @phpstan-param TArgs $args
	 */
	public function __construct( QM_Backtrace $backtrace, array $args = [] ) {
		$this->backtrace = $backtrace;
		$this->args = $args;
	}

	abstract public function get_message(): string;

	final public function get_trace(): QM_Backtrace {
		return $this->backtrace;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return [];
	}
}
