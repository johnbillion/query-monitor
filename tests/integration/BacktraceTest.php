<?php declare(strict_types = 1);

namespace QM\Tests;

class BacktraceTest extends Test {

	/**
	 * @param mixed[] $trace
	 * @param array<string, mixed> $args
	 * @return Supports\TestBacktrace
	 */
	private function create_backtrace( array $trace, array $args = array() ): Supports\TestBacktrace {
		$bt = new Supports\TestBacktrace( $args );
		$bt->set_trace( $trace );

		return $bt;
	}

	public function testFrameFileLineIsShifted(): void {
		$trace = array(
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 10,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 20,
			),
			array(
				'function' => 'first_function',
				'file' => '/app/entry.php',
				'line' => 30,
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// All 3 frames present. Frame 0 has no frame above and nothing
		// was trimmed, so it gets null file/line.
		self::assertCount( 3, $frames );

		self::assertSame( 'third_function', $frames[0]->id );
		self::assertNull( $frames[0]->file );
		self::assertNull( $frames[0]->line );

		// second_function gets third_function's original file/line.
		self::assertSame( 'second_function', $frames[1]->id );
		self::assertSame( '/app/second.php', $frames[1]->file );
		self::assertSame( 10, $frames[1]->line );

		// first_function gets second_function's original file/line.
		self::assertSame( 'first_function', $frames[2]->id );
		self::assertSame( '/app/first.php', $frames[2]->file );
		self::assertSame( 20, $frames[2]->line );
	}

	public function testIgnoreClassTrimsFromTop(): void {
		$trace = array(
			array(
				'class' => 'wpdb',
				'type' => '->',
				'function' => 'query',
				'file' => '/app/caller.php',
				'line' => 5,
			),
			array(
				'function' => 'my_function',
				'file' => '/app/entry.php',
				'line' => 15,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// wpdb trimmed. my_function and bootstrap remain.
		self::assertCount( 2, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
		self::assertSame( 'bootstrap', $frames[1]->id );

		// my_function gets the trimmed wpdb frame's file/line.
		self::assertSame( '/app/caller.php', $frames[0]->file );
		self::assertSame( 5, $frames[0]->line );

		// bootstrap gets my_function's original file/line.
		self::assertSame( '/app/entry.php', $frames[1]->file );
		self::assertSame( 15, $frames[1]->line );
	}

	public function testPerInstanceIgnoreFuncTrimsFromTop(): void {
		$trace = array(
			array(
				'function' => 'wp_remote_get',
				'file' => '/app/caller.php',
				'line' => 5,
			),
			array(
				'function' => 'my_function',
				'file' => '/app/entry.php',
				'line' => 15,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_func' => array(
				'wp_remote_get' => true,
			),
		) );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// wp_remote_get trimmed. my_function and bootstrap remain.
		self::assertCount( 2, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
		self::assertSame( 'bootstrap', $frames[1]->id );

		// my_function gets the trimmed frame's file/line.
		self::assertSame( '/app/caller.php', $frames[0]->file );
		self::assertSame( 5, $frames[0]->line );
	}

	public function testWpHookFilteredMidStack(): void {
		$trace = array(
			array(
				'function' => 'my_function',
				'file' => '/app/hook.php',
				'line' => 10,
			),
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/plugin.php',
				'line' => 20,
			),
			array(
				'function' => 'do_action',
				'file' => '/app/entry.php',
				'line' => 30,
				'args' => array( 'init' ),
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// WP_Hook is always filtered out. my_function and do_action remain.
		self::assertCount( 2, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
		self::assertSame( 'do_action', $frames[1]->id );
		self::assertSame( "'init'", $frames[1]->args );
	}

	public function testFullHttpStackTrimming(): void {
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/http.php',
				'line' => 5,
			),
			array(
				'class' => 'WP_Http',
				'type' => '->',
				'function' => 'request',
				'file' => '/app/http-get.php',
				'line' => 10,
			),
			array(
				'class' => 'WP_Http',
				'type' => '->',
				'function' => 'get',
				'file' => '/app/remote.php',
				'line' => 15,
			),
			array(
				'function' => 'wp_remote_get',
				'file' => '/app/caller.php',
				'line' => 20,
			),
			array(
				'function' => 'my_function',
				'file' => '/app/entry.php',
				'line' => 30,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_class' => array(
				'WP_Http' => true,
			),
			'ignore_func' => array(
				'wp_remote_get' => true,
			),
		) );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// WP_Hook, WP_Http x2, wp_remote_get all trimmed.
		// my_function and bootstrap remain.
		self::assertCount( 2, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
		self::assertSame( 'bootstrap', $frames[1]->id );

		// my_function gets the last trimmed frame's file/line (wp_remote_get).
		self::assertSame( '/app/caller.php', $frames[0]->file );
		self::assertSame( 20, $frames[0]->line );

		// bootstrap gets my_function's original file/line.
		self::assertSame( '/app/entry.php', $frames[1]->file );
		self::assertSame( 30, $frames[1]->line );
	}

	public function testGetCallerReturnsFirstFrameAfterTrimming(): void {
		$trace = array(
			array(
				'class' => 'wpdb',
				'type' => '->',
				'function' => 'query',
				'file' => '/app/caller.php',
				'line' => 5,
			),
			array(
				'function' => 'my_function',
				'file' => '/app/entry.php',
				'line' => 15,
			),
		);

		$bt = $this->create_backtrace( $trace );
		$caller = $bt->get_caller();

		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'my_function', $caller->id );
	}

	public function testGetCallerMatchesFirstSerializedFrame(): void {
		$trace = array(
			array(
				'function' => 'my_function',
				'file' => '/app/caller.php',
				'line' => 10,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// get_caller and serialized frame 0 are the same function.
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'my_function', $caller->id );
		self::assertSame( 'my_function', $frames[0]->id );
	}

	public function testGetCallerWithIgnoreClassTrimming(): void {
		$trace = array(
			array(
				'class' => 'wpdb',
				'type' => '->',
				'function' => 'query',
				'file' => '/app/wpdb.php',
				'line' => 5,
			),
			array(
				'function' => 'my_function',
				'file' => '/app/entry.php',
				'line' => 15,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// get_caller returns my_function (first frame after wpdb trimmed).
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'my_function', $caller->id );

		// my_function is also the first serialized frame.
		self::assertSame( 'my_function', $frames[0]->id );
	}

	public function testGetCallerReturnsFalseForEmptyTrace(): void {
		$bt = $this->create_backtrace( array() );
		self::assertFalse( $bt->get_caller() );
	}

	public function testQmClassesAreAlwaysFiltered(): void {
		$trace = array(
			array(
				'class' => 'QM_Collector',
				'type' => '->',
				'function' => 'process',
				'file' => '/app/qm.php',
				'line' => 5,
			),
			array(
				'function' => 'my_function',
				'file' => '/app/entry.php',
				'line' => 15,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// QM_Collector filtered out. my_function and bootstrap remain.
		self::assertCount( 2, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
		self::assertSame( 'bootstrap', $frames[1]->id );
	}

	public function testCallSiteWithStackTrace(): void {
		$callsite = new \QM_Data_Callsite();
		$callsite->file = '/app/third.php';
		$callsite->filename = 'third.php';
		$callsite->line = 70;

		$trace = array(
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 50,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 46,
			),
			array(
				'function' => 'first_function',
				'file' => '/app/entry.php',
				'line' => 30,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'callsite' => $callsite,
		) );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// All 3 frames present. Frame 0 gets the call site's file/line,
		// since that's the location inside third_function where the error occurred.
		self::assertCount( 3, $frames );
		self::assertSame( 'third_function', $frames[0]->id );
		self::assertSame( '/app/third.php', $frames[0]->file );
		self::assertSame( 70, $frames[0]->line );

		// second_function gets third_function's original file/line (shifted).
		self::assertSame( 'second_function', $frames[1]->id );
		self::assertSame( '/app/second.php', $frames[1]->file );
		self::assertSame( 50, $frames[1]->line );

		// The call site is preserved separately.
		self::assertNotNull( $data['callsite'] );
		self::assertSame( '/app/third.php', $data['callsite']->file );
		self::assertSame( 70, $data['callsite']->line );
	}

	public function testTransientStyleIgnoreFuncPreservesCallLocation(): void {
		// Simulates set_transient being called from third_function,
		// which is called by second_function, etc.
		$trace = array(
			array(
				'function' => 'set_transient',
				'file' => '/app/third.php',
				'line' => 68,
			),
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 50,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 46,
			),
			array(
				'function' => 'first_function',
				'file' => '/app/entry.php',
				'line' => 30,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_func' => array(
				'set_transient' => true,
			),
		) );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// get_caller returns third_function (first frame after trimming).
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'third_function', $caller->id );

		// All frames after trimming are visible.
		self::assertCount( 3, $frames );
		self::assertSame( 'third_function', $frames[0]->id );
		self::assertSame( 'second_function', $frames[1]->id );
		self::assertSame( 'first_function', $frames[2]->id );

		// third_function links to where it called set_transient.
		self::assertSame( '/app/third.php', $frames[0]->file );
		self::assertSame( 68, $frames[0]->line );

		// second_function links to where it calls third_function.
		self::assertSame( '/app/second.php', $frames[1]->file );
		self::assertSame( 50, $frames[1]->line );

		// first_function gets second_function's raw file/line.
		self::assertSame( '/app/first.php', $frames[2]->file );
		self::assertSame( 46, $frames[2]->line );
	}

	public function testTransientStyleIgnoreFuncWithHookTrimming(): void {
		// Full realistic stack: WP_Hook dispatches a callback that
		// eventually calls set_transient.
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/transient.php',
				'line' => 5,
			),
			array(
				'function' => 'set_transient',
				'file' => '/app/third.php',
				'line' => 68,
			),
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 50,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 46,
			),
			array(
				'function' => 'first_function',
				'file' => '/app/entry.php',
				'line' => 30,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_func' => array(
				'set_transient' => true,
			),
		) );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// WP_Hook and set_transient trimmed. third_function is the caller.
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'third_function', $caller->id );

		// third_function, second_function, first_function remain.
		self::assertCount( 3, $frames );
		self::assertSame( 'third_function', $frames[0]->id );

		// third_function links to where it called set_transient.
		self::assertSame( '/app/third.php', $frames[0]->file );
		self::assertSame( 68, $frames[0]->line );
	}

	public function testDoingItWrongIgnoreFunc(): void {
		// Simulates _doing_it_wrong() being called from third_function
		// via the doing_it_wrong_run action hook.
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/functions.php',
				'line' => 5,
			),
			array(
				'function' => '_doing_it_wrong',
				'file' => '/app/third.php',
				'line' => 72,
			),
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 50,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 46,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_hook' => array(
				'doing_it_wrong_run' => true,
			),
			'ignore_func' => array(
				'_doing_it_wrong' => true,
			),
		) );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// _doing_it_wrong and WP_Hook trimmed. third_function is the caller.
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'third_function', $caller->id );

		self::assertCount( 2, $frames );
		self::assertSame( 'third_function', $frames[0]->id );
		self::assertSame( 'second_function', $frames[1]->id );

		// third_function links to where it called _doing_it_wrong.
		self::assertSame( '/app/third.php', $frames[0]->file );
		self::assertSame( 72, $frames[0]->line );
	}

	public function testDeprecatedFunctionIgnoreFunc(): void {
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/functions.php',
				'line' => 5,
			),
			array(
				'function' => '_deprecated_function',
				'file' => '/app/third.php',
				'line' => 72,
			),
			array(
				'function' => 'old_function',
				'file' => '/app/caller.php',
				'line' => 20,
			),
			array(
				'function' => 'bootstrap',
				'file' => '/app/index.php',
				'line' => 1,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_hook' => array(
				'deprecated_function_run' => true,
			),
			'ignore_func' => array(
				'_deprecated_function' => true,
			),
		) );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// old_function is the caller (first frame after trimming).
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'old_function', $caller->id );

		// old_function links to where it called _deprecated_function.
		self::assertSame( '/app/third.php', $frames[0]->file );
		self::assertSame( 72, $frames[0]->line );
	}

	public function testIncludeRequireFramesTrimmedFromBottom(): void {
		$trace = array(
			array(
				'function' => 'my_function',
				'file' => '/app/hook.php',
				'line' => 10,
			),
			array(
				'function' => 'do_action',
				'file' => '/app/plugin.php',
				'line' => 20,
				'args' => array( 'init' ),
			),
			array(
				'function' => 'require_once',
				'file' => '/app/wp-config.php',
				'line' => 30,
				'args' => array( '/app/wp-settings.php' ),
			),
			array(
				'function' => 'require_once',
				'file' => '/app/wp-load.php',
				'line' => 40,
				'args' => array( '/app/wp-config.php' ),
			),
			array(
				'function' => 'require_once',
				'file' => '/app/wp-admin/admin.php',
				'line' => 50,
				'args' => array( '/app/wp-load.php' ),
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// The three require_once frames at the bottom should be trimmed.
		self::assertCount( 2, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
		self::assertSame( 'do_action', $frames[1]->id );
	}

	public function testMidStackIncludeFramesArePreserved(): void {
		$trace = array(
			array(
				'function' => 'my_function',
				'file' => '/app/template.php',
				'line' => 10,
			),
			array(
				'function' => 'require',
				'file' => '/app/loader.php',
				'line' => 20,
				'args' => array( '/app/template.php' ),
			),
			array(
				'function' => 'load_template',
				'file' => '/app/theme.php',
				'line' => 30,
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// The require frame is mid-stack (load_template is below it
		// and is not an include/require), so it's preserved.
		self::assertCount( 3, $frames );
		self::assertSame( 'my_function', $frames[0]->id );
	}

	public function testWpHookMethodsFilteredAndDoActionKeepsFileLine(): void {
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/class-wp-hook.php',
				'line' => 341,
			),
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'do_action',
				'file' => '/app/class-wp-hook.php',
				'line' => 365,
			),
			array(
				'function' => 'do_action',
				'file' => '/app/third.php',
				'line' => 62,
				'args' => array( 'banana' ),
			),
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 50,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 46,
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// WP_Hook methods are filtered out. do_action and user functions remain.
		self::assertCount( 3, $frames );
		self::assertSame( 'do_action', $frames[0]->id );
		self::assertSame( "'banana'", $frames[0]->args );
		self::assertSame( 'third_function', $frames[1]->id );
		self::assertSame( 'second_function', $frames[2]->id );

		// do_action keeps its original file/line (where third_function calls it).
		self::assertSame( '/app/third.php', $frames[0]->file );
		self::assertSame( 62, $frames[0]->line );

		// third_function also gets do_action's file/line (same call point).
		self::assertSame( '/app/third.php', $frames[1]->file );
		self::assertSame( 62, $frames[1]->line );

		// second_function gets third_function's file/line (shifted normally).
		self::assertSame( '/app/second.php', $frames[2]->file );
		self::assertSame( 50, $frames[2]->line );
	}

	public function testNestedHooksHandledCorrectly(): void {
		// A hook callback fires another hook.
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/class-wp-hook.php',
				'line' => 341,
			),
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'do_action',
				'file' => '/app/class-wp-hook.php',
				'line' => 365,
			),
			array(
				'function' => 'do_action',
				'file' => '/app/my-function.php',
				'line' => 62,
				'args' => array( 'banana' ),
			),
			array(
				'function' => 'my_function',
				'file' => '/app/class-wp-hook.php',
				'line' => 341,
			),
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/class-wp-hook.php',
				'line' => 365,
			),
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'do_action',
				'file' => '/app/plugin.php',
				'line' => 522,
			),
			array(
				'function' => 'do_action',
				'file' => '/app/settings.php',
				'line' => 775,
				'args' => array( 'init' ),
			),
		);

		$bt = $this->create_backtrace( $trace );
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// All WP_Hook methods filtered. Both do_actions and my_function remain.
		self::assertCount( 3, $frames );
		self::assertSame( 'do_action', $frames[0]->id );
		self::assertSame( "'banana'", $frames[0]->args );
		self::assertSame( 'my_function', $frames[1]->id );
		self::assertSame( 'do_action', $frames[2]->id );
		self::assertSame( "'init'", $frames[2]->args );

		// do_action('banana') keeps its original file/line.
		self::assertSame( '/app/my-function.php', $frames[0]->file );
		self::assertSame( 62, $frames[0]->line );

		// do_action('init') keeps its original file/line.
		self::assertSame( '/app/settings.php', $frames[2]->file );
		self::assertSame( 775, $frames[2]->line );
	}

	public function testIgnoreHookRemovesHookDispatchFrames(): void {
		// Simulates a timing collector trace where the backtrace is
		// captured inside a qm/start action callback.
		$trace = array(
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'apply_filters',
				'file' => '/app/class-wp-hook.php',
				'line' => 341,
			),
			array(
				'class' => 'WP_Hook',
				'type' => '->',
				'function' => 'do_action',
				'file' => '/app/class-wp-hook.php',
				'line' => 365,
			),
			array(
				'function' => 'do_action',
				'file' => '/app/third.php',
				'line' => 87,
				'args' => array( 'qm/start' ),
			),
			array(
				'function' => 'third_function',
				'file' => '/app/second.php',
				'line' => 50,
			),
			array(
				'function' => 'second_function',
				'file' => '/app/first.php',
				'line' => 46,
			),
		);

		$bt = $this->create_backtrace( $trace, array(
			'ignore_hook' => array(
				'qm/start' => true,
			),
		) );

		$caller = $bt->get_caller();
		$data = $bt->jsonSerialize();
		$frames = $data['frames'];

		// The qm/start hook dispatch (do_action + WP_Hook methods) is
		// removed by ignore_hook. third_function is the caller.
		self::assertInstanceOf( \QM_Data_Stack_Frame::class, $caller );
		self::assertSame( 'third_function', $caller->id );

		self::assertCount( 2, $frames );
		self::assertSame( 'third_function', $frames[0]->id );
		self::assertSame( 'second_function', $frames[1]->id );

		// third_function has null file/line because the ignored hook dispatch
		// frame was removed by filter_trace (not trim_top_frames), so its
		// file/line doesn't feed into top_frame_location.
		self::assertNull( $frames[0]->file );
		self::assertNull( $frames[0]->line );
	}
}
