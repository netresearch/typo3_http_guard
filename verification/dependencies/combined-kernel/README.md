# Kernel-only verification fixtures

These manifests are temporary test-project inputs for the combined `netresearch/nr-http-guard` extension. They are not separately installable production libraries. The extension has one production Composer manifest at its root and includes its kernel in `Classes/HttpGuard`.

The fixtures retain the frozen Guzzle/Promises/PSR-7 tuples and PHPUnit 11 dependency versions, and map autoloading to the combined extension layout. They omit TYPO3 and Symfony service dependencies so the independent kernel can be tested on PHP 8.2 through 8.5. TYPO3 bootstrap and service integration are tested separately with the complete root package.

The parent directory contains immutable manifests, locks and audit evidence from the earlier two-package candidate. Their historical package names and paths are provenance, not a requirement of the combined extension.
