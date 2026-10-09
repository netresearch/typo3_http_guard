.. _development:

=====================
Development and tests
=====================

.. _development-layout:

One extension, two internal namespaces
======================================

.. list-table:: Extension source layout
    :header-rows: 1

    * - Path
      - Purpose
    * - :file:`Classes/`
      - TYPO3 adapter under :php:`Netresearch\NrHttpGuard`.
    * - :file:`Classes/HttpGuard/`
      - Embedded security core under :php:`Netresearch\HttpGuard`.
    * - :file:`Configuration/`
      - TYPO3 services, aliases and RequestFactory registration.
    * - :file:`Resources/Private/HttpGuard/data/`
      - Runtime address rules, shared corpus and source metadata.
    * - :file:`Documentation/`
      - Complete manual and reusable examples.
    * - :file:`Tests/Unit/`
      - Unit tests for the TYPO3 adapter.
    * - :file:`Tests/HttpGuard/`
      - Policy, transport and real wire tests for the embedded core.

The core reads neither TYPO3 globals nor Vault secrets. The TYPO3 adapter
provides the configuration, registry, resolver, clock and reporter. The
separation in the code allows isolated core tests; it does not require a
second installable library. See :ref:`decision-single-extension` for the
packaging decision.

.. _development-unit:

Local checks
============

Development uses the tools listed in :file:`composer.json`. These are not
part of the classic runtime. The combined PHPUnit entry point uses both
unit test directories:

.. code-block:: bash
    :caption: Check the extension and its embedded core

    vendor/bin/phpunit --configuration phpunit.xml --testsuite Unit
    vendor/bin/phpstan analyse --configuration Build/phpstan-http-guard.neon --no-progress

This PHPStan configuration checks only the embedded security core under
:file:`Classes/HttpGuard/` at level 8.

Wire tests require controlled fixtures and destination counters. They are
not run as ordinary offline unit tests. For an isolated policy check with
a minimal qualified SDK vendor, use the separate kernel bootstrap:

.. code-block:: bash
    :caption: Isolated policy tests without a TYPO3 dependency

    HTTP_GUARD_TEST_AUTOLOAD=/absolute/sdk/vendor/autoload.php \
        /absolute/sdk/vendor/bin/phpunit \
        --configuration Build/phpunit-http-guard.xml \
        Tests/HttpGuard/Unit/Policy

The kernel bootstrap loads only the selected dependencies and this
package's production and test namespaces. The additional configuration
:file:`Build/phpstan-http-guard.neon` checks :file:`Classes/HttpGuard/` at
PHPStan level 8.

.. _development-evidence:

Evidence and reproduction
=========================

The technical verification distinguishes policy results, native transfer
construction and actual new TCP and HTTP contacts. A mock handler alone
does not prove pinning. The wire fixtures use their own synthetic addresses
and DNS responses; third-party production destinations are not required
for the tests.

The complete reproduction runners, dependency locks, container versions,
runtime data, source hashes, JUnit outputs and mapping of the original
45 requirements to 84 tests are in the **optional source and evidence
package** under :file:`verification/` and :file:`evidence/`. They are not
runtime dependencies of the TER ZIP. Its file
:file:`verification/requirements-and-tests.md` explains the actual verified
scope and outstanding acceptance steps for each requirement.

The checks cover in particular:

* IPv4 and IPv6 CIDR boundaries, mapped IPv6, metadata and narrow endpoint
  permissions.
* Complete DNS chains, TC retry over TCP, limits, NSS exclusion and
  memoization and expiry checks.
* Actual cURL pinning, missing capabilities, options, proxy SAPI behaviour,
  redirects, retries, streaming and cancellation lifetimes.
* Real TYPO3 DI and RequestFactory bootstraps, context restrictions, CLI
  commands and classic archives without a separately installed library.
* Optional Vault migration with independent resource and OAuth bindings.
* Isolated mutants that remove denials or violate pinning or fallback
  restrictions; witnesses check actual contacts before the assertions.

The PHP and SDK core matrix is separate from the framework matrix. A core
test run on PHP 8.2 does not automatically qualify every TYPO3 version for
that PHP version. New Core or SDK versions, handlers, protocols or permitted
options require source comparison and repetition of the relevant policy,
wire, integration and mutation tests.

The historical microbenchmark ran on the recorded local host. It does not
satisfy the outstanding measurement on the named CI reference required by
the plan. A complete technical test run replaces neither an independent
human security review nor the pilot on an actual operator instance.

.. _development-docs:

Render the manual
=================

The sources under :file:`Documentation/` use the current phpDocumentor
Guides configuration. :file:`Settings.cfg` is not required. The official
TYPO3 documentation describes the
`rendering container
<https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/RenderingDocs/Index.html>`_.

.. code-block:: bash
    :caption: Official renderer in the extension directory

    docker run --rm -v "$PWD":/project \
        ghcr.io/typo3-documentation/render-guides:latest \
        --config=Documentation --no-progress --fail-on-log

The generated HTML is written to :file:`Documentation-GENERATED-temp/`.
If you provide an :literal:`--output` option, the destination must be inside
the mounted directory so that the files are retained. Run the renderer
with an option that treats warnings as failures. The exit code from an
unchecked default invocation does not prove that references are valid.

.. toctree::
    :maxdepth: 1

    Licenses
    Verification
    Dependencies
    Assessment
