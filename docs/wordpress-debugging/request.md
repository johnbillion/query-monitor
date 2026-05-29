---
title: Request
---

# Debugging the request with Query Monitor

The Request panel in Query Monitor shows you detailed information about how WordPress handled the current request. This useful for debugging URL routing, rewrite rules, and query variables.

## What information is shown

The full URL of the current request is shown along with the regular expression that matched the request URL, the internal query string that WordPress derived from the rewrite rule, and the parsed query variables which ultimately determine what content WordPress displays.

If multiple rewrite rules match the current URL, you'll see all of them. WordPress uses the first match, but seeing the alternatives helps when debugging rewrite rule conflicts.

The panel also shows the queried object for the current request, basic information about the currently authenticated user, and site and network information when Multisite is in use.

## When is this useful?

* Debugging custom rewrite rules that aren't matching as expected
* Verifying which query variables WordPress is using for a given URL
* Confirming that a URL resolves to the correct post or taxonomy term
* Understanding why a request is returning a 404 when you expect it to match
