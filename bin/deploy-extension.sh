#!/usr/bin/env bash

# Publishes the extension to the Chrome Web Store.
#
# Required environment variables:
#   CLIENT_ID      - Google OAuth2 client ID
#   CLIENT_SECRET  - Google OAuth2 client secret
#   REFRESH_TOKEN  - Google OAuth2 refresh token

set -euo pipefail

PUBLISHER_ID="331778f9-1ecf-49e0-a587-cc32658f43ce"
EXTENSION_ID="ohcllkgjhacaegbcdapjeeloikdgkcpo"

for var in CLIENT_ID CLIENT_SECRET REFRESH_TOKEN; do
	if [ -z "${!var:-}" ]; then
		echo "Error: ${var} is not set"
		exit 1
	fi
done

ZIP_FILE="extension.zip"

if [ ! -f "$ZIP_FILE" ]; then
	echo "Error: ${ZIP_FILE} not found. Run 'npm run build:extension' first."
	exit 1
fi

# Get access token
TOKEN_RESPONSE=$(curl -s -X POST https://oauth2.googleapis.com/token \
	-d "client_id=${CLIENT_ID}" \
	-d "client_secret=${CLIENT_SECRET}" \
	-d "refresh_token=${REFRESH_TOKEN}" \
	-d "grant_type=refresh_token")
ACCESS_TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.access_token')
if [ "$ACCESS_TOKEN" = "null" ] || [ -z "$ACCESS_TOKEN" ]; then
	echo "Failed to obtain access token"
	echo "$TOKEN_RESPONSE" | jq .
	exit 1
fi

API_BASE="https://chromewebstore.googleapis.com"

# Upload
RESPONSE=$(curl -s -w "\n%{http_code}" \
	-X POST "${API_BASE}/upload/v2/publishers/${PUBLISHER_ID}/items/${EXTENSION_ID}:upload" \
	-H "Authorization: Bearer ${ACCESS_TOKEN}" \
	-T "$ZIP_FILE")
HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
echo "$BODY" | jq .
if [ "$HTTP_CODE" -ne 200 ]; then
	echo "Upload failed with HTTP $HTTP_CODE"
	exit 1
fi
UPLOAD_STATUS=$(echo "$BODY" | jq -r '.uploadState')
if [ "$UPLOAD_STATUS" != "SUCCESS" ]; then
	echo "Upload state: $UPLOAD_STATUS"
	exit 1
fi

# Publish
RESPONSE=$(curl -s -w "\n%{http_code}" \
	-X POST "${API_BASE}/v2/publishers/${PUBLISHER_ID}/items/${EXTENSION_ID}:publish" \
	-H "Authorization: Bearer ${ACCESS_TOKEN}")
HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')
echo "$BODY" | jq .
if [ "$HTTP_CODE" -ne 200 ]; then
	echo "Publish failed with HTTP $HTTP_CODE"
	exit 1
fi
