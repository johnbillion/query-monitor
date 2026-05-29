---
title: Database Queries
---

# Debugging database queries with Query Monitor

The database query panels are where Query Monitor originally earned its name. There are several sub-panels which help you understand and optimise the database queries that WordPress performs during a page load.

## All Queries

The main Queries panel shows every individual SQL query that was executed during the page load. For each query you'll see:

* The SQL statement, colour-coded by type (SELECT, INSERT, UPDATE, DELETE)
* The caller and the full call stack so you can trace the query to its origin
* The component responsible (WordPress core, a plugin, or a theme)
* The number of rows affected or returned
* The execution time, highlighted if it exceeds the slow query threshold
* The error message if the query resulted in an error being returned from the dataabse server

You can filter the list by query type to quickly narrow down what you're looking for. The "Non-SELECT" filter is particularly useful for identifying queries that are writing to the database on a page load where you wouldn't expect them.

## Queries by Caller

This panel aggregates queries by the function that called them. It shows the total number of queries and the total time for each caller, broken down by query type. This helps you answer the question "which function is responsible for the most database activity?"

Click the caller name to jump to the main Queries panel filtered to that caller.

## Queries by Component

The Queries by Component panel is one of the most useful panels for identifying poorly performing plugins or themes. It groups all database queries by the component that triggered them and shows the total count and time for each.

This panel is sorted by total time, so the component with the highest database overhead will appear at the top. If you're trying to figure out why your site is slow, start here.

## Duplicate Queries

The Duplicate Queries panel shows SQL queries that were executed more than once during the page load with identical query strings. This is a common source of performance problems -- it often means that a piece of code is running a query inside a loop instead of querying once and caching the result.

For each duplicate query, you'll see the query text, the number of times it was executed, and which callers and components were responsible. The "potential troublemakers" column helps you pinpoint potentially multiple sources of identical queries.

## Slow Queries

Queries that exceed a time threshold are shown in the Slow Queries panel. These are the queries that are most likely to be causing performance problems on your site and should be investigated.

## Query Errors

If any database query produces an error, the Query Errors panel will appear and show you the full error message alongside the query and its caller. This is invaluable for debugging queries that reference tables or columns that don't exist, or that have syntax errors.
