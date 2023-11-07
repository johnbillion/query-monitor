---
title: Assertions
parent: WordPress debugging
---

# Performing assertions in Query Monitor

Query Monitor allows developers to perform assertions which will log an error in the Query Monitor interface when they fail. This is a convenience wrapper around the logging feature which allows you to get alerted to problems without performing conditional logic.

Let's take a look at how to use this feature and what it's useful for.

## Heading

```php
do_action( 'qm/assert', $value === 5 );
do_action( 'qm/assert', $value === 5, 'Value is 5' );
do_action( 'qm/assert', $value === 5, 'Value is 5', $value );
```

```php
foreach ( $posts as $post ) {
	$before = $wpdb->num_queries;
	$this->process_post( $post );
	$after = $wpdb->num_queries;

	// Assert that no database queries are performed as we process each post:
	do_action( 'qm/assert', $after === $before );
}
```

Preconditions

```php
do_action( 'qm/assert', is_array( $data ), 'Data is an array', $data );
do_action( 'qm/assert', array_key_exists( 'foo', $data ), 'Data contains foo', $data );
```

Postconditions

```php
do_action( 'qm/assert', did_action( 'my-action' ) );
```

A failed assertion will log an error in the Logs panel and trigger a notification in Query Monitor's admin toolbar.

Here's what the Logs panel looks like:

[![Query Monitor's Logging Panel](../../assets/assertions.png)](../../assets/assertions.png)

The static assertion method on the `QM` class can be used instead of calling `do_action()`:

```php
QM::assert( $value === 5 );
QM::assert( $value === 5, 'Value is 5' );
QM::assert( $value === 5, 'Value is 5', $value );
```

Be careful with the size of the value used for debugging!

Note that this feature doesn't support interpolation.

## Differences with `assert()`

This feature differs from the `assert()` function in PHP.

* The `assert()` function in PHP will terminate execution of the script if the assertions fails, this is not true for assertions in Query Monitor. This has advantages and disadvantages...
* The first parameter must be an expression that results in a boolean true or false.
* Query Monitor logs passed assertions too.
* Processing does not terminate if the assertion fails. Think of this like a soft assertion that raises an error instead. Code should behave as expected regardless of whether the assertion passes.
* Assertions in Query Monitor will always be performed and logged as necessary. The `assert()` function in PHP will only perform the assertion if assertions are enabled in the php.ini configuration.
* You can pass an optional value to output for debugging

## Notes on usage

Assertions are primarily a development tool to identify bugs or sub-optimal behaviour. This is distinct from error handling or data validation.

If you pass a string it will not be evaluated as an assertion (same behaviour as `assert()` since PHP 8).
