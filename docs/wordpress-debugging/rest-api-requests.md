---
title: REST API requests
---

# Debugging WordPress REST API requests with Query Monitor

Query Monitor includes a feature which allows you to see comprehensive performance information about a REST API request on your site.

## Authentication

Just like requests to the front end or the admin area of your site, in order to see debugging information for the REST API you need to perform a request which is authenticated as a user who has permission to view Query Monitor’s output, for example an Administrator.

* This usually means including a valid `_wpnonce` parameter in the URL, the value of which you can get by visiting `wp-admin/admin-ajax.php?action=rest-nonce`
* Alternatively you can pass an Application Password if you’re using WordPress 5.6 or later

## Overview and PHP error information

The following additional HTTP headers will be included in the response:

* `x-qm-overview-time_taken` – Response generation time in seconds
* `x-qm-overview-time_usage` – Response generation time as a percentage of PHP’s max execution time limit
* `x-qm-overview-memory` – Memory usage in kB
* `x-qm-overview-memory_usage` – Memory usage as a percentage of PHP’s memory limit
* `x-qm-php_errors-error-count` – Number of PHP errors that occurred (0 or more)
* `x-qm-php_errors-error-{n}` – Details about each individual PHP error

## Full performance and debugging information

When a REST API request is performed which requests [an enveloped response via the `?_envelope` parameter](https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/#_envelope), an additional `qm` property will be present in the JSON response with information about:

* `qm.db_queries.dbs` – All database queries
* `qm.db_queries.dupes` – Duplicate database queries
* `qm.db_queries.errors` – Database queries with errors
* `qm.cache` – Object cache stats for hits and misses
* `qm.http` – HTTP API requests and response details
* `qm.logger` – Logged messages and variables
* `qm.transients` – Updated transients

The information is somewhat trimmed down from the information that you would see in the main Query Monitor panel for a regular HTML request, but it contains the key information that you need to investigate performance issues.

The `qm.db_queries` property contains overview information as well as full details for each individual SQL query, including timing, stack traces, and returned rows, and the `qm.http` property includes details about the each requested URL, response code, timing, and stack traces.

## Example data

Given a GET request to a default endpoint such as `example.com/wp-json/wp/v2/posts/?_envelope&_wpnonce=<nonce>`, you would not typically expect to see server-side HTTP API requests or transients being updated on every request, but QM will now expose this information so you can investigate!

Here’s an example of the `qm` property in a response:

```json
{
  "db_queries": {
    "dbs": {
      "$wpdb": {
        "total": 15,
        "time": 0.0108,
        "queries": [
          {
            "sql": "SELECT option_name, option_value FROM wp_options WHERE autoload = 'yes'",
            "time": 0.0011,
            "stack": [
              "wp_load_alloptions()",
              "is_blog_installed()",
              "wp_not_installed()"
            ],
            "result": 317
          },
          {
            "sql": "SELECT * FROM wp_users WHERE ID = '1' LIMIT 1",
            "time": 0.0003,
            "stack": [
              "WP_User::get_data_by()",
              "WP_User->__construct()",
              "wp_set_current_user()",
              "_wp_get_current_user()",
              "wp_get_current_user()",
              "get_current_user_id()",
              "get_user_option()",
              "Classic_Editor::get_settings()",
              "Classic_Editor::init_actions()",
              "do_action('plugins_loaded')"
            ],
            "result": 1
          },
          {
            "sql": "SELECT wp_posts.ID FROM wp_posts WHERE 1=1  AND wp_posts.post_type = 'post' AND ((wp_posts.post_status = 'publish')) ORDER BY wp_posts.post_date DESC LIMIT 0, 5",
            "time": 0.0003,
            "stack": [
              "WP_Query->get_posts()",
              "WP_Query->query()",
              "get_posts()",
              "DoubleUnderscore\\entrypoint()",
              "do_action('init')"
            ],
            "result": 5
          }
        ]
      }
    },
    "errors": {
      "total": 1,
      "errors": [
        {
          "caller": "do_action('init')",
          "caller_name": "do_action('init')",
          "sql": "SELECT * FROM table_that_does_not_exist",
          "ltime": 0,
          "result": {
            "errors": {
              "1146": [
                "Table 'wp.table_that_does_not_exist' doesn't exist"
              ]
            }
          }
        }
      ]
    },
    "dupes": {
      "total": 1,
      "queries": {
        "SELECT wp_posts.ID FROM wp_posts WHERE 1=1 AND wp_posts.post_type = 'post' AND ((wp_posts.post_status = 'publish')) ORDER BY wp_posts.post_date DESC LIMIT 0, 5": [
          3,
          14,
          35
        ]
      }
    }
  },
  "cache": {
    "hit_percentage": 67.8,
    "hits": 931,
    "misses": 442
  },
  "http": {
    "total": 1,
    "time": 0.6586,
    "requests": [
      {
        "url": "https://example.org",
        "method": "GET",
        "response": {
          "code": 200,
          "message": "OK"
        },
        "time": 0.6586,
        "stack": [
          "WP_Http->request()",
          "WP_Http->get()",
          "wp_remote_get()",
          "DoubleUnderscore\\entrypoint()",
          "do_action('init')"
        ]
      }
    ]
  },
  "logger": {
    "warning": [
      {
        "message": "Preloading was not found, generating fresh",
        "stack": [
          "DoubleUnderscore\\dispatcher()",
          "DoubleUnderscore\\entrypoint()",
          "do_action('init')"
        ]
      }
    ],
    "debug": [
      {
        "message": "Language: en_US",
        "stack": [
          "DoubleUnderscore\\do_logs()",
          "DoubleUnderscore\\entrypoint()",
          "do_action('init')"
        ]
      }
    ]
  }
}
```
