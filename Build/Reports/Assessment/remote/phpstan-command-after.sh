set -euo pipefail
vendor/bin/phpstan analyse --configuration Build/phpstan-http-guard.neon --no-progress
if [ "$MATRIX_CORE" = "13.4.35" ]; then
  HTTP_GUARD_TEST_AUTOLOAD="$PWD/vendor/autoload.php" vendor/bin/phpstan analyse \
    --configuration Build/phpstan-typo3-core13.neon --no-progress
else
  HTTP_GUARD_TEST_AUTOLOAD="$PWD/vendor/autoload.php" vendor/bin/phpstan analyse \
    --configuration Build/phpstan-typo3.neon \
    --autoload-file Build/phpstan-bootstrap14.php --no-progress
fi
