<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorDoingItWrongTest extends Test {

	/**
	 * @var \QM_Collector_Doing_It_Wrong
	 */
	public $collector;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->collector = new \QM_Collector_Doing_It_Wrong();
		$this->collector->set_up();
	}

	#[\Override]
	public function tear_down(): void {
		$this->collector->tear_down();

		parent::tear_down();
	}

	/**
	 * @dataProvider dataDeprecatedCalls
	 * @param callable(): void $trigger
	 * @param class-string<\QM_Wrong<*>> $expected_class
	 */
	public function testItCollectsDeprecatedCall( callable $trigger, string $deprecated, string $expected_class, string $expected_message ): void {
		$this->setExpectedDeprecated( $deprecated );

		$trigger();

		$this->assertCollected( $expected_class, $expected_message );
	}

	/**
	 * @return array<string, array{
	 *   0: callable(): void,
	 *   1: string,
	 *   2: class-string<\QM_Wrong<*>>,
	 *   3: string,
	 * }>
	 */
	public function dataDeprecatedCalls(): array {
		return array(
			'argument' => array(
				static function (): void {
					_deprecated_argument( 'my_function', '2.0.0' );
				},
				'my_function',
				\QM_Deprecated_Argument_Run::class,
				'Function my_function was called with an argument that is deprecated since version 2.0.0 with no alternative available.',
			),
			'argument with message' => array(
				static function (): void {
					_deprecated_argument( 'my_function', '2.0.0', 'Use <code>$foo</code> instead.' );
				},
				'my_function',
				\QM_Deprecated_Argument_Run::class,
				'Function my_function was called with an argument that is deprecated since version 2.0.0! Use $foo instead.',
			),
			'file' => array(
				static function (): void {
					_deprecated_file( 'my_file.php', '2.0.0' );
				},
				'my_file.php',
				\QM_Deprecated_File_Included::class,
				'File my_file.php is deprecated since version 2.0.0 with no alternative available.',
			),
			'file with replacement and message' => array(
				static function (): void {
					_deprecated_file( 'my_file.php', '2.0.0', 'new_file.php', '<em>Extra</em> info.' );
				},
				'my_file.php',
				\QM_Deprecated_File_Included::class,
				'File my_file.php is deprecated since version 2.0.0! Use new_file.php instead. Extra info.',
			),
			'function' => array(
				static function (): void {
					_deprecated_function( 'my_function', '2.0.0' );
				},
				'my_function',
				\QM_Deprecated_Function_Run::class,
				'Function my_function is deprecated since version 2.0.0 with no alternative available.',
			),
			'function with replacement' => array(
				static function (): void {
					_deprecated_function( 'my_function', '2.0.0', 'new_function' );
				},
				'my_function',
				\QM_Deprecated_Function_Run::class,
				'Function my_function is deprecated since version 2.0.0! Use new_function instead.',
			),
			'hook' => array(
				static function (): void {
					_deprecated_hook( 'my_hook', '2.0.0' );
				},
				'my_hook',
				\QM_Deprecated_Hook_Run::class,
				'Hook my_hook is deprecated since version 2.0.0 with no alternative available.',
			),
			'hook with replacement and message' => array(
				static function (): void {
					_deprecated_hook( 'my_hook', '2.0.0', 'new_hook', '<em>Extra</em> info.' );
				},
				'my_hook',
				\QM_Deprecated_Hook_Run::class,
				'Hook my_hook is deprecated since version 2.0.0! Use new_hook instead. Extra info.',
			),
		);
	}

	/**
	 * @dataProvider dataDeprecatedClassCalls
	 */
	public function testItCollectsDeprecatedClass( string $replacement, string $expected_message ): void {
		if ( ! function_exists( '_deprecated_class' ) ) {
			self::markTestSkipped( '_deprecated_class() requires WordPress 6.4 or later.' );
		}

		$this->setExpectedDeprecated( 'My_Class' );

		_deprecated_class( 'My_Class', '2.0.0', $replacement );

		$this->assertCollected( \QM_Deprecated_Class_Run::class, $expected_message );
	}

	/**
	 * @return array<string, array{
	 *   0: string,
	 *   1: string,
	 * }>
	 */
	public function dataDeprecatedClassCalls(): array {
		return array(
			'no replacement' => array(
				'',
				'Class My_Class is deprecated since version 2.0.0 with no alternative available.',
			),
			'replacement' => array(
				'New_Class',
				'Class My_Class is deprecated since version 2.0.0! Use New_Class instead.',
			),
		);
	}

	public function testItCollectsDoingItWrong(): void {
		$this->setExpectedIncorrectUsage( 'my_function' );

		_doing_it_wrong( 'my_function', 'This is <strong>wrong</strong>.', '2.0.0' );

		$this->assertCollected(
			\QM_Doing_It_Wrong_Run::class,
			'Function my_function was called incorrectly. This is wrong. (This message was added in version 2.0.0.)'
		);
	}

	public function testItCollectsDeprecatedConstructor(): void {
		// WP_UnitTestCase doesn't handle deprecated constructors.
		add_filter( 'deprecated_constructor_trigger_error', '__return_false' );

		_deprecated_constructor( 'My_Class', '2.0.0', 'My_Parent' );

		$this->assertCollected(
			\QM_Deprecated_Constructor_Run::class,
			'The called constructor method for My_Class class in My_Parent is deprecated since version 2.0.0! Use <code>__construct()</code> instead.'
		);
	}

	/**
	 * @param class-string<\QM_Wrong<*>> $expected_class
	 */
	private function assertCollected( string $expected_class, string $expected_message ): void {
		$actions = $this->collector->get_data()->actions;

		self::assertCount( 1, $actions );
		self::assertInstanceOf( $expected_class, $actions[0] );

		// Some messages have a trailing space when the optional message is empty.
		self::assertSame( $expected_message, trim( $actions[0]->jsonSerialize()['message'] ) );
	}

}
