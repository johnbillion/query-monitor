# Debugging Guzzle HTTP Requests

Query Monitor can log HTTP requests made with the [Guzzle HTTP client library](https://docs.guzzlephp.org/), a popular PHP HTTP client that's used by many WordPress plugins and applications. This enables comprehensive debugging of HTTP requests that bypass WordPress's built-in HTTP API.

## Why Use Guzzle Middleware?

Many modern WordPress plugins and applications use Guzzle for HTTP requests because it offers:

- Advanced features like connection pooling and concurrent requests
- Better error handling and retry mechanisms  
- Support for modern HTTP features
- PSR-7 compliant request/response objects

However, since Guzzle bypasses WordPress's HTTP API, these requests don't normally appear in Query Monitor's HTTP panel. The Guzzle middleware solves this problem.

## Basic Usage

To log Guzzle requests in Query Monitor, add the middleware to your Guzzle client:

```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

// Create a handler stack with Query Monitor middleware
$stack = HandlerStack::create();
$stack->push(QM_Collector_HTTP::guzzle_middleware());

// Create your Guzzle client
$client = new Client(['handler' => $stack]);

// Make requests as normal - they'll appear in Query Monitor
$response = $client->get('https://api.example.com/users');
$response = $client->post('https://api.example.com/data', [
    'json' => ['key' => 'value']
]);
```

## Advanced Examples

### Using with Existing Handler Stack

If you already have a custom handler stack, simply add the Query Monitor middleware:

```php
$stack = HandlerStack::create();
$stack->push(Middleware::retry(/* retry config */));
$stack->push(QM_Collector_HTTP::guzzle_middleware()); // Add QM middleware
$stack->push(Middleware::log($logger, $formatter));

$client = new Client(['handler' => $stack]);
```

### Plugin Integration

For WordPress plugins using Guzzle, you can conditionally add the middleware only when Query Monitor is available:

```php
class MyPlugin {
    private function createHttpClient() {
        $stack = HandlerStack::create();
        
        // Add Query Monitor middleware if available
        if (class_exists('QM_Collector_HTTP')) {
            $stack->push(QM_Collector_HTTP::guzzle_middleware());
        }
        
        return new Client([
            'handler' => $stack,
            'timeout' => 30,
            'base_uri' => 'https://api.example.com/'
        ]);
    }
}
```

### Temporary Debugging

For temporary debugging of existing Guzzle usage, you can wrap your existing client:

```php
// Existing Guzzle client
$existingClient = new Client(['base_uri' => 'https://api.example.com/']);

// Wrap with QM middleware for debugging
$stack = HandlerStack::create();
$stack->push(QM_Collector_HTTP::guzzle_middleware());
$debugClient = new Client([
    'handler' => $stack,
    'base_uri' => 'https://api.example.com/'
]);

// Use debugClient instead of existingClient temporarily
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
    $response = $client->get('https://api.example.com/nonexistent');
} catch (GuzzleHttp\Exception\ClientException $e) {
    // Error will still be logged in Query Monitor
    // Shows as "guzzle_request_failed" with exception message
}
```

## Filtering and Organization

Guzzle requests appear alongside WordPress HTTP API requests in the HTTP panel and can be:

- **Filtered by component** - See which plugin made which requests
- **Filtered by status** - Find failed requests or specific response codes  
- **Filtered by host** - Group requests by destination
- **Sorted by time** - Find slow requests

The stack trace filtering automatically ignores Guzzle internal classes using the new `ignore_namespace` feature, so you see clean traces showing your actual calling code.

## Performance Considerations

The Query Monitor middleware has minimal performance impact:

- Only collects data when Query Monitor is active
- Uses efficient promise-based handling
- Doesn't modify request/response content
- Automatically disabled in production if QM is disabled

## Troubleshooting

### Middleware Not Working

If Guzzle requests aren't appearing in Query Monitor:

1. **Check Query Monitor is active** - The middleware automatically disables if QM isn't loaded
2. **Verify middleware order** - QM middleware should be one of the last in your stack
3. **Check Guzzle version** - Requires Guzzle 7.x (compatible with PHP 7.2+)

### Missing Stack Traces

If stack traces aren't showing your code:

- The middleware automatically ignores Guzzle internal classes
- Your calling code should appear at the top of the trace
- If you have custom HTTP abstraction layers, they may need to be added to ignore lists

### Large Response Bodies

Query Monitor captures response bodies for debugging. For very large responses:

- Consider using Guzzle's `stream` option for large downloads
- The middleware respects Guzzle's streaming settings
- Response body display in QM may be truncated for readability

## Requirements

- **PHP 7.2+** - Required by Guzzle 7.x
- **Guzzle 7.x** - The middleware is designed for Guzzle 7
- **Query Monitor** - Obviously required for logging functionality

## Related Documentation

- [HTTP API Requests Panel](https://querymonitor.com/wordpress-debugging/rest-api-requests/)
- [Profiling and Logging](https://querymonitor.com/wordpress-debugging/profiling-and-logging/)
- [Guzzle HTTP Documentation](https://docs.guzzlephp.org/)