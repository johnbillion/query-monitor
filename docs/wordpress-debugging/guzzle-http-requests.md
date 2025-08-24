# Debugging Guzzle HTTP Requests

::: tip New
This feature is new in Query Monitor 3.19.0
:::

Query Monitor can log HTTP requests made with the [Guzzle HTTP client library](https://docs.guzzlephp.org/), a popular PHP HTTP client used by many WordPress plugins and applications. Since Guzzle bypasses WordPress's HTTP API, these requests don't normally appear in Query Monitor's HTTP panel. The Guzzle middleware solves this problem.

## Basic Usage

To log Guzzle requests in Query Monitor, add the middleware to your Guzzle client:

```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

// Create a handler stack with Query Monitor middleware
$stack = HandlerStack::create();
$stack->push( QM_Collector_HTTP::guzzle_middleware() );

// Create your Guzzle client
$client = new Client( [ 'handler' => $stack ] );

// Make requests as normal - they'll appear in Query Monitor
$response = $client->get( 'https://api.example.com/users' );
$response = $client->post( 'https://api.example.com/data', [
    'json' => [ 'key' => 'value' ]
] );
```

## Advanced Examples

### Using with Existing Handler Stack

If you already have a custom handler stack, simply add the Query Monitor middleware:

```php
$stack = HandlerStack::create();
$stack->push( Middleware::retry( /* retry config */ ) );
$stack->push( QM_Collector_HTTP::guzzle_middleware() ); // Add QM middleware
$stack->push( Middleware::log( $logger, $formatter ) );

$client = new Client( [ 'handler' => $stack ] );
```

### Plugin Integration

For WordPress plugins using Guzzle, you can conditionally add the middleware only when Query Monitor is available:

```php
class MyPlugin {
    private function createHttpClient() {
        $stack = HandlerStack::create();

        // Add Query Monitor middleware if available
        if ( method_exists( 'QM_Collector_HTTP', 'guzzle_middleware' ) ) {
            $stack->push( QM_Collector_HTTP::guzzle_middleware() );
        }

        return new Client( [
            'handler' => $stack,
            'timeout' => 30,
            'base_uri' => 'https://api.example.com/'
        ] );
    }
}
```

## What Information is Captured

When using the Guzzle middleware, Query Monitor captures the same detailed information as WordPress HTTP API requests:

### Request Information
- **URL** - The complete request URL
- **Method** - HTTP method (GET, POST, PUT, DELETE, etc.)
- **Headers** - All request headers
- **Body** - Request body content
- **Timeout** - Request timeout setting
- **SSL Settings** - Certificate verification settings

### Response Information
- **Status Code** - HTTP response code (200, 404, 500, etc.)
- **Headers** - All response headers
- **Body** - Response content
- **Size** - Response size in bytes

### Performance & Debugging
- **Execution Time** - How long the request took
- **Stack Trace** - Which code made the request
- **Component** - Which plugin/theme triggered the request
- **Errors** - Any exceptions or failed requests

### Error Handling

Failed Guzzle requests are properly handled and displayed:

```php
try {
    $response = $client->get( 'https://api.example.com/nonexistent' );
} catch ( GuzzleHttp\Exception\ClientException $e ) {
    // Exception message will still be shown in Query Monitor
}
```

## Related Documentation

- [Profiling and Logging](https://querymonitor.com/wordpress-debugging/profiling-and-logging/)
- [Guzzle HTTP Documentation](https://docs.guzzlephp.org/)
