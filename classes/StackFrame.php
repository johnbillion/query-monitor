<?php declare(strict_types = 1);
/**
 * Function call stack trace frame container.
 *
 * @package query-monitor
 */

final class QM_StackFrame {
	/**
	 * @var string
	 */
	public $function;

	/**
	 * @var ?string
	 */
	public $file;

	/**
	 * @var ?int
	 */
	public $line;

	/**
	 * @var string
	 */
	public $params;

	/**
	 * @var ?string
	 */
	public $class;

	/**
	 * @var ?string
	 * @phpstan-var ?('->'|'::')
	 */
	public $type;

	/**
	 * @param array<string, mixed> $frame
	 * @phpstan-param array{
	 *   file: string,
	 *   line: int,
	 *   function: string,
	 *   args?: array<mixed>,
	 *   class: string,
	 *   type: '->'|'::',
	 * } $frame
	 */
	public static function from_class_frame( array $frame ): self {
		return new static(
			$frame['function'],
			$frame['file'],
			$frame['line'],
			'()',
			$frame['class'],
			$frame['type']
		);
	}

	/**
	 * @param array<string, mixed> $frame
	 * @param array<string, mixed> $options
	 * @phpstan-param array{
	 *   file: string,
	 *   line: int,
	 *   function: string,
	 *   args?: array<mixed>,
	 * } $frame
	 */
	public static function from_function_frame( array $frame, array $options = array() ): self {
		if ( isset( $options['show_args'][ $frame['function'] ], $frame['args'] ) ) {
			$show = $options['show_args'][ $frame['function'] ];

			if ( 'dir' === $show ) {
				$arg = QM_Util::standard_dir( $frame['args'][0] ?? '', '' );

				return new static(
					$frame['function'],
					$frame['file'],
					$frame['line'],
					"('{$arg}')"
				);
			}

			$args = array();
			for ( $i = 0; $i < $show; $i++ ) {
				if ( array_key_exists( $i, $frame['args'] ) ) {
					if ( is_string( $frame['args'][ $i ] ) ) {
						$args[] = '\'' . $frame['args'][ $i ] . '\'';
					} else {
						$args[] = QM_Util::display_variable( $frame['args'][ $i ] );
					}
				}
			}

			return new static(
				$frame['function'],
				$frame['file'],
				$frame['line'],
				'(' . implode( ',', $args ) . ')'
			);
		}

		return new static(
			$frame['function'],
			$frame['file'],
			$frame['line']
		);
	}

	/**
	 * @param array<string, mixed> $frame
	 * @phpstan-param array{
	 *   file?: string,
	 *   line?: int,
	 *   function: string,
	 *   args?: array<mixed>,
	 *   class?: string,
	 *   type?: '->'|'::',
	 * } $frame
	 */
	public static function from_minimal_frame( array $frame ): self {
		return new static(
			$frame['function'],
			$frame['file'] ?? null,
			$frame['line'] ?? null,
			'()',
			$frame['class'] ?? null,
			$frame['type'] ?? null
		);
	}

	/**
	 * @phpstan-param ?('->'|'::') $type
	 */
	protected function __construct(
		string $function,
		string $file = null,
		int $line = null,
		string $params = '()',
		string $class = null,
		string $type = null
	) {
		$this->function = $function;
		$this->file = $file;
		$this->line = $line;
		$this->params = $params;
		$this->class = $class;
		$this->type = $type;
	}

	public function get_fqn(): string {
		if ( isset( $this->class, $this->type ) ) {
			return $this->class . $this->type . $this->function;
		}

		return $this->function;
	}

	public function get_display(): string {
		return QM_Util::shorten_fqn( $this->get_fqn() ) . $this->params;
	}

	/**
	 * Attempts to determine the component responsible for the frame.
	 */
	public function get_component(): ?QM_Component {
		try {
			$file = $this->file;

			if ( isset( $this->class ) ) {
				if ( ! class_exists( $this->class, false ) ) {
					return null;
				}

				if ( ! method_exists( $this->class, $this->function ) ) {
					return null;
				}

				$ref = new ReflectionMethod( $this->class, $this->function );
				$file = $ref->getFileName();
			} elseif ( function_exists( $this->function ) ) {
				$ref = new ReflectionFunction( $this->function );
				$file = $ref->getFileName();
			}

			if ( ! $file ) {
				return null;
			}

			return QM_Util::get_file_component( $file );

		} catch ( ReflectionException $e ) {
			return null;
		}
	}
}
