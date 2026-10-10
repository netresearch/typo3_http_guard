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
    * - :file:`Tests/Functional/`
      - Genuine Core decoration, classic activation and mode bootstraps.
    * - :file:`Tests/HttpGuard/`
      - Policy, transport and real wire tests for the embedded core.

The adapter supplies immutable configuration, registry, resolver, clock and
reporter. The independent embedded core allows isolated tests within this
one installable extension; see :ref:`decision-single-extension`.

.. _development-unit:

Local checks
============

Development dependencies come from :file:`composer.json` and install under
:file:`.Build/vendor/`. PHPUnit :literal:`^11.5`, PHPStan :literal:`^2.3`
and :literal:`netresearch/typo3-ci-workflows:^1.12` provide the shared tools.
The development directory and tool configurations are excluded from the
classic extension package.

.. code-block:: bash
    :caption: Install tools and run deterministic local checks

    composer install
    composer check:harness
    composer check:secrets
    composer check:local

CaptainHook installs commit-message and pre-commit checks. Staged secrets
are checked before quality; missing tools and failed checks must fail.
Installation alone proves no passing run. CI remains the merge authority;
see :file:`CONTRIBUTING.md` for hook scope and isolated checkouts.

.. list-table:: Project quality entry points
    :header-rows: 1

    * - Composer command
      - Scope
    * - :literal:`ci:test:php:cgl`
      - Shared PHP style, dry run.
    * - :literal:`ci:test:php:rector`
      - PHP 8.2-compatible modernization, dry run.
    * - :literal:`ci:test:php:phpstan`
      - Curated kernel and actual Core profiles; configured level 10.
    * - :literal:`ci:test:php:unit`
      - Offline adapter and kernel Unit tests.
    * - :literal:`ci:test:php:functional`
      - Controlled native transport tests.
    * - :literal:`ci:test:php:architecture`
      - Documented architecture boundaries.
    * - :literal:`ci:test:php:mutation`
      - All-source Infection analysis, including uncovered mutants.
    * - :literal:`ci:test:php:fuzz`
      - Deterministic input/property checks.
    * - :literal:`ci:test:php:performance`
      - Normalization/classification/policy benchmark.

Commands, configuration and execution are separate evidence. Curated static
rules use no baseline or broad suppression. Analysis-only HandlerStack/Client
stubs retain actual SDK methods/storage and document Guzzle 7's missing
template plus both majors' open constructor/:literal:`getConfig` contracts.
Native controls preserve caller defaults; neither stub runs in production.

The integrated offline suite passes **1,535 Unit tests and 7,199 assertions**
on each genuine Core 13/Guzzle 7 and Core 14/Guzzle 8 graph on PHP 8.5.11.
Kernel/Core 13/14 level 10, architecture and Rector are clean. Recorded style
has zero changes in 196 files across the GPL extension/test/tool and MIT
kernel scopes. The final 263-input full-source run passes 90.43%/91.54%
with no skipped/ignored mutants; focused results retain separate bindings.
See :ref:`verification-integrated-local` for complete 850 quality evidence
and the unchanged 90%/90% targets.

Wire tests require dedicated targets and counters; serialize runs that
share them. Ordinary Unit tests remain offline. A minimal SDK installation
can run policy tests with the separate bootstrap:

.. code-block:: bash
    :caption: Isolated policy tests with an explicitly selected SDK loader

    HTTP_GUARD_TEST_AUTOLOAD=/absolute/sdk/vendor/autoload.php \
        /absolute/sdk/vendor/bin/phpunit \
        --configuration Build/phpunit-http-guard.xml \
        Tests/HttpGuard/Unit/Policy

Fixed fixtures preserve exact Core and SDK snapshots. Four floating native
Core 13/14 and Guzzle 7/8 rows resolve compatible updates on pull requests
and weekly schedules; eight native rows and 14 fixed PHP CI cells remain
separate matrices. See :ref:`verification-semantic-support` for recorded
versions and :ref:`assessment-reconciliation` for current remediation.

.. _development-functional-routes:

Functional fixture routes
=========================

The three executed Core entrypoints live in :file:`Tests/Functional/`.
Select an already prepared genuine fixture and controlled wire targets:

.. code-block:: bash
    :caption: Host and selected-container Core routes

    bash Build/Scripts/runTests.sh -s integration -f .Build/fixtures/core14g8
    bash Build/Scripts/runTests.sh -s classic -f .Build/classic-sites/classic14 -- active
    bash Build/Scripts/runTests.sh -s mode -f .Build/fixtures/core14g8 -- observe
    bash Build/Scripts/runTests.sh -s integration -f .Build/fixtures/core14g8 -p 8.5 -t 14

Without runtime flags the wrapper uses host PHP. Selected-container routes
use the official :literal:`suite_http_guard_functional` hook and the selected
digest-pinned image. Ephemeral host-network containers reach the
separately prepared witness destinations used by these entries. They mount
the physically validated project, preserve literal fixture arguments, and
probe the actual fixture PHP/Core graph before running the entrypoint.
External fixtures and escaping loader symlinks fail before delegation.
The fixture graph is unchanged; generic suites retain their shared routes.

Containers run as the caller without capabilities and with a read-only
root filesystem. Cleanup removes captured owned IDs; failures propagate.
Core 13 fixtures may omit :literal:`composer/semver`: the probe loads only
that namespace from development tools, retaining the fixture's Core/SDK.

The 27 offline routing controls establish argument, path, ownership and
failure behavior. Separate real qualification passed 20 host and 20
selected-container routes on PHP 8.5.11 and 8.5.10 respectively. Those records
are bound to their tested sources and graphs. See :file:`Build/Fixtures/README.md`
for fixture preparation and routing details.

.. _development-mutation-scope:

Mutation scope in CI
====================

Pull-request verification selects production PHP files added, modified or
renamed relative to the validated base commit. A NUL-separated Git file list
is validated and passed as positional paths to Infection, which mutates
the complete selected files, including unchanged functions. Direct and
nested :file:`Classes/` files are included. MSI and Covered MSI both retain
90% thresholds, default mutators, uncovered mutants and measured failures.

When no production PHP was added, modified or renamed, the workflow records
an explicit skipped mutation measurement. Unit and native behavior still
run; that skip establishes no mutation score. Scheduled runs, pushes to
:literal:`main` and manual dispatch retain the complete :file:`Classes/`
scope without changed-file selection. Full alpha qualification is recorded
separately with its actual measured score and source binding; configuring
the threshold does not establish that it passed.

.. _development-evidence:

Evidence and reproduction
=========================

The technical verification distinguishes policy results, native transfer
construction and actual new TCP and HTTP contacts. A mock handler alone
does not prove pinning. The wire fixtures use their own synthetic addresses
and DNS responses; third-party production destinations are not required
for the tests.

Maintained runners and fixtures live under :file:`Tests/` and :file:`Build/`;
source-bound summaries live under :file:`Build/Reports/Assessment/`.
:ref:`development-requirements` maps the original 45 requirements, 84
scenarios and eight invariants to current tests and immutable history.
:ref:`verification-report` distinguishes completed runs from archived
measurements; these development records are not TER runtime dependencies.

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
that PHP version. Compatible dependency patches and minors remain
installable within the supported ranges. Changes to supported majors,
minima, handlers, protocols or permitted options require source comparison
and the relevant policy, wire, integration and mutation tests.

The named policy benchmark and historical measurements retain their own
source/runtime boundaries under :ref:`assessment-reconciliation`.

.. _development-docs:

Render the manual
=================

The sources under :file:`Documentation/` use the current phpDocumentor
Guides configuration. :file:`Settings.cfg` is not required. The official
TYPO3 documentation describes the
`rendering container
<https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/Howto/RenderingDocs/Index.html>`_.

.. code-block:: bash
    :caption: Official renderer in the extension directory

    docker run --rm -v "$PWD":/project \
        ghcr.io/typo3-documentation/render-guides@sha256:fcf1ea87377ac401ce595b8c320c03b2b1bf2505ec0109561ca2fe56d7d71fc1 \
        --config=Documentation --no-progress --fail-on-log

HTML is written to :file:`Documentation-GENERATED-temp/`. A custom output
must stay inside the mounted directory. Use :literal:`--fail-on-log` so that
warnings fail the build; a default exit code alone proves no valid references.
This digest identifies the recorded renderer. Updating it requires fresh
English and German rendering before claiming another successful build.

.. toctree::
    :maxdepth: 1

    Licenses
    Requirements
    Verification
    Dependencies
    Assessment
    Reconciliation
    ReleaseProvenance
