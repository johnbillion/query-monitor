---
title: Headers
---

# Debugging HTTP headers with Query Monitor

The Headers panels in Query Monitor show you the HTTP request headers sent by the browser and the response headers sent by your server for the current page load.

## Request Headers

The Request Headers panel shows all of the HTTP headers that the browser sent with the request. This includes headers like `Accept`, `User-Agent`, `Cookie`, and any custom headers. This can be useful when debugging caching behaviour, content negotiation, or authentication.

## Response Headers

The Response Headers panel shows all of the HTTP headers that your server sent back in the response. This includes headers like `Content-Type`, `Cache-Control`, `X-Powered-By`, and any custom headers added by WordPress, plugins, or your server configuration.

## When is this useful?

* Debugging caching issues by checking `Cache-Control`, `Expires`, and `ETag` headers
* Verifying that security headers like `X-Content-Type-Options` or `Content-Security-Policy` are being sent
* Checking redirect-related headers
* Confirming that a plugin or server configuration is setting the headers you expect
