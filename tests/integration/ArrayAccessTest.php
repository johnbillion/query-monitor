<?php declare(strict_types = 1);

namespace QM\Tests;

class ArrayAccessTest extends Test {

	public function testDataObjectSupportsArrayAccess(): void {
		$data = new \QM_Data_Fallback();
		$data->dummy = 'hello';

		// Read via array access.
		self::assertSame( 'hello', $data['dummy'] );

		// Write via array access.
		$data['dummy'] = 'world';
		self::assertSame( 'world', $data->dummy );

		// isset via array access.
		self::assertTrue( isset( $data['dummy'] ) );
		self::assertFalse( isset( $data['nonexistent'] ) );
	}

	public function testExtractedClassSupportsArrayAccess(): void {
		$frame = new \QM_Data_Stack_Frame();
		$frame->id = 'my_function';
		$frame->file = '/app/test.php';
		$frame->line = 42;

		// Read via array access.
		self::assertSame( 'my_function', $frame['id'] );
		self::assertSame( '/app/test.php', $frame['file'] );
		self::assertSame( 42, $frame['line'] );

		// Write via array access.
		$frame['file'] = '/app/other.php';
		self::assertSame( '/app/other.php', $frame->file );

		// isset via array access.
		self::assertTrue( isset( $frame['id'] ) );
		self::assertFalse( isset( $frame['nonexistent'] ) );
	}

	public function testStandaloneClassSupportsArrayAccess(): void {
		$callback = new \QM_Data_Callback();
		$callback->callback_type = 'function';
		$callback->name = 'my_function';
		$callback->file = '/app/test.php';
		$callback->line = 10;

		self::assertSame( 'function', $callback['callback_type'] );
		self::assertSame( 'my_function', $callback['name'] );
	}
}
