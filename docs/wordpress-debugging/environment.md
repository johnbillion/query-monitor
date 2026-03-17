---
title: Environment
---

# Viewing environment information with Query Monitor

The Environment panel in Query Monitor gives you an overview of the server and software configuration for your WordPress site. This saves you from having to dig through `phpinfo()` output or SSH into your server.

## What information is shown

The panel is divided into sections:

### PHP

Key PHP configuration values including the version number, memory limit, error reporting level, extensions, and other settings that affect how PHP runs.

### Database

Your database server type, version, host, user, and various other configuration values.

### WordPress

The WordPress version, environment information, and various WordPress-level configuration values.

### Server

Information about the HTTP server software (Apache, Nginx, etc.) and the operating system.

## When is this useful?

* Checking the PHP version or memory limit without needing server access
* Verifying that required PHP extensions are loaded
* Confirming database server details when debugging query issues
