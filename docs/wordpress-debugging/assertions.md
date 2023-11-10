---
title: Assertions
parent: WordPress debugging
---

# Performing assertions in Query Monitor

{: .new }
This feature is new in Query Monitor 3.15

Query Monitor allows developers to perform assertions which will log an error in the Logs panel in Query Monitor when they fail. This is a convenience wrapper around the logging feature which allows you to get alerted to problems without performing conditional logic.

Here's what assertions look like in the Logs panel:

[![Query Monitor's Logging Panel](../../assets/assertions.png)](../../assets/assertions.png)

Let's take a look at how to use this feature and what it's useful for.

## Basic usage

```php
do_action( 'qm/assert', $value === 5 );
do_action( 'qm/assert', $value === 5, 'Value is 5' );
do_action( 'qm/assert', $value === 5, 'Value is 5', $value );
```

The `qm/assert` action accepts an assertion value as its first parameter which you'll usually provide in the form of an expression. This should be a boolean `true` or `false` value, although technically anything truthy or falsey is accepted.

If the assertion fails then Query Monitor will show an error in the Logs panel, which in turn causes a red warning to appear in the admin toolbar so you get notified about the failure. If the assertion passes then a debug level message will be shown in the Logs panel, which helps you confirm that your assertion is being executed.

The second parameter is an optional short description of the assertion. If provided, this will be shown along with the assertion failure or pass message.

The third parameter is an optional value of any type that will get output below the error message if the assertion fails. This is useful for debugging an unexpected value.

{: .warning }
Be careful not to log very large values such as an array of post objects or the raw response from an HTTP request. If you really need to debug the value of something large, use a tool such as [step debugging in Xdebug](https://xdebug.org/docs/step_debug) or [debugging in Ray](https://myray.app/).

## More examples

You can use this assertion feature to ensure your code is behaving as expected, for example to assert how many database queries are being performed or not performed:

```php
foreach ( $posts as $post ) {
	$before = $wpdb->num_queries;
	$this->process_post( $post );
	$after = $wpdb->num_queries;

	// Assert that no database queries are performed as we process each post:
	do_action( 'qm/assert', $after === $before );
}
```

Preconditions can be used to assert a certain state before performing logic based on expectations:

```php
do_action( 'qm/assert', is_array( $data ), 'Data is an array', $data );
do_action( 'qm/assert', array_key_exists( 'foo', $data ), 'Data contains foo', $data );
```

Postconditions can be used to assert that a particular outcome occured:

```php
do_action( 'qm/assert', did_action( 'my-action' ) );
```

The static assertion method on the `QM` class can be used instead of calling `do_action()`:

```php
QM::assert( $value === 5 );
QM::assert( $value === 5, 'Value is 5' );
QM::assert( $value === 5, 'Value is 5', $value );
```

## Differences from `assert()`

This feature differs from the native `assert()` function in PHP because they serve different purposes.

* The `assert()` function in PHP will terminate execution of the script if the assertions fails, this is not true for assertions in Query Monitor. Think of this like a soft assertion that raises an error instead. Code should behave as expected regardless of whether the assertion passes.
* Query Monitor logs passed assertions too. This is useful for verifying that your assertion is being executed.
* Assertions in Query Monitor will always be performed and logged as necessary. The `assert()` function in PHP will only perform the assertion if assertions are enabled in the `php.ini` configuration.
* Assertions in Query Monitor can be passed an optional value to output for debugging purposes, which is not possible with `assert()`.

## Notes on usage

Assertions are primarily a development tool to identify bugs or sub-optimal behaviour in your code. This is distinct from error handling or data validation, which assertions are not intended for.

Just as with the `assert()` function in PHP, your code must handle the situation where your assertion fails because in a production environment the code will continue to execute past the assertion.

## Profiling and logging

[Read more about the profiling and logging functionality in Query Monitor](./profiling-and-logging/).
