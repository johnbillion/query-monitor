#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

if [[ ! -z "$CODESPACE_NAME" ]]
then
	WP_URL="https://${CODESPACE_NAME}-2502.githubpreview.dev"
else
	WP_URL="http://localhost:2502"
fi

# Prep:
WP="wp --url=${WP_URL}"

# Reset or install the test database:
echo "Installing database..."
$WP db reset --yes

# Install WordPress:
echo "Installing WordPress..."
$WP core install \
	--title="Example" \
	--admin_user="admin" \
	--admin_password="admin" \
	--admin_email="admin@example.com" \
	--skip-email \
	--require="bin/mysqli_report.php"
echo "Home URL: $WP_URL"
$WP plugin activate query-monitor
