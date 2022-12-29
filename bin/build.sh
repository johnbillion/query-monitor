#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

# Install non-dev Composer dependencies:
composer remove composer/installers --update-no-dev
composer dump-autoload --no-dev

# Wrap the call to `setClassMapAuthoritative` in a `method_exists` check:
sed -i.bak 's/\$loader->setClassMapAuthoritative(true);/if (method_exists(\$loader,"setClassMapAuthoritative")){\n            \$loader->setClassMapAuthoritative(true);\n        }/' vendor/composer/autoload_real.php
rm vendor/composer/autoload_real.php.bak

# Remove autoloading for `\Composer\InstalledVersions`:
sed -i.bak '/Composer\\\\InstalledVersions/d' vendor/composer/autoload_classmap.php
rm vendor/composer/autoload_classmap.php.bak
