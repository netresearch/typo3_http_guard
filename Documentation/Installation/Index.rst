.. _installation:

============
Installation
============

Install and activate the extension to protect existing calls through
TYPO3's registered :php:`RequestFactory` automatically. The default
:literal:`enforce` mode blocks private, loopback, cloud metadata and
special-purpose destinations, including URLs supplied by users, imports
or external payloads. No application rewrite or endpoint configuration
is required to enable this default protection.

.. _installation-composer:

With Composer
=============

Install the published 0.1 series from
`Packagist <https://packagist.org/packages/netresearch/nr-http-guard>`_:

.. code-block:: bash
    :caption: Installation in a TYPO3 project

    composer require netresearch/nr-http-guard:^0.1
    vendor/bin/typo3 cache:flush
    vendor/bin/typo3 http-guard:config-check
    vendor/bin/typo3 http-guard:doctor

Composer registers the extension automatically. After rebuilding caches,
ordinary RequestFactory calls use the protected default. Version 0.1.0
is an alpha for evaluation. Review the project's resolved dependencies
against :ref:`installation-requirements` before deployment. The extension
does not require an additional :literal:`netresearch/http-guard` package.

.. _installation-classic:

Classic installation without Composer
=====================================

Install :literal:`nr_http_guard` from the
`TYPO3 Extension Repository
<https://extensions.typo3.org/extension/nr_http_guard>`_.
Activate it in the Extension Manager and rebuild caches. This enables
default protection without a custom policy.

The TER package contains extension files directly at its root.
The source archives from
`GitHub releases <https://github.com/netresearch/typo3_http_guard/releases>`_
contain one :file:`nr-http-guard/` directory. Neither archive includes an
additional vendor directory. The official TYPO3 installation supplies
Guzzle, PSR components and Symfony.

1. Prepare a supported classic TYPO3 installation. The official Core
   archives tested at 13.4.36 and 14.3.8 contain Guzzle 8.2.0, Promises
   3.0.2 and PSR-7 3.1.0, within the supported ranges.
2. Install from TER or import its extension ZIP using the Extension
   Manager's upload function. For a manual installation from a GitHub
   release, extract the source archive and copy the **contents** of its
   :file:`nr-http-guard/` directory into
   :file:`typo3conf/ext/nr_http_guard/`. The extension's
   :file:`composer.json` must be directly inside that target directory.
   Activate the extension in the Extension Manager.
3. Rebuild the system and dependency injection caches. Retain the supplied
   :file:`composer.json`: TYPO3 needs its metadata even in classic mode
   and registers both embedded PHP namespaces itself.
4. Run diagnostics. Custom policy configuration is optional for public
   requests; internal integrations need explicitly bound endpoint clients.
   In classic projects, the CLI entry point is usually
   :file:`typo3/sysext/core/bin/typo3`. The actual path depends on how
   the project links the Core.

.. code-block:: bash
    :caption: Diagnostics for a classic installation

    php typo3/sysext/core/bin/typo3 cache:flush
    php typo3/sysext/core/bin/typo3 http-guard:config-check
    php typo3/sysext/core/bin/typo3 http-guard:doctor

The official guides explain
`classic TYPO3 installation using archives
<https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/Administration/Installation/ClassicMode/TarballZip.html>`_
and an extension's
`composer.json for classic compatibility
<https://docs.typo3.org/permalink/t3coreapi:ext-composer-json-classic-compatible>`_.

.. _installation-requirements:

Requirements
============

Composer supports TYPO3 :literal:`^13.4.36 || ^14.3.8` and PHP
:literal:`^8.2`. PHP must also meet the selected Core's requirements.
Compatible updates within these ranges do not require a new HTTP Guard
release. The runtime checks the actual Core parent API and SDK capabilities
and rejects incompatible APIs before a native send.

.. list-table:: Supported transport dependency ranges
    :header-rows: 1

    * - Guzzle
      - Promises
      - PSR-7
    * - :literal:`^7.15.2`
      - :literal:`^2.5.1`
      - :literal:`^2.13.0`
    * - :literal:`^8.2`
      - :literal:`^3.0.2`
      - :literal:`^3.1`

The minima provide the required transport APIs. Composer must resolve a
compatible complete graph within the supported majors. Each transfer
lease allocates at most one native handle; hidden SDK retries cannot
bypass the per-attempt policy boundary.

The fixed Core/SDK fixtures record reproducible tests at specific versions,
not the full set of installable dependencies. Current compatibility checks
are described in :ref:`verification-semantic-support`. Historical kernel
coverage spans PHP 8.2–8.5; future compatible PHP versions are permitted
but are not claimed as already tested. Classic extension metadata permits
TYPO3 13.4.36–14.3.99 and PHP 8.2.0–8.99.99. The classic TYPO3 upper bound
is narrower than the Composer ranges and currently targets the 13.4/14.3
LTS branches. The contiguous TER notation cannot express the separate
Core 14 minimum: the runtime rejects Core 14.0–14.2 and 14.3 patches before
14.3.8. Core's own PHP requirements and the same runtime checks apply.

The controlled transport requires :literal:`ext-curl`, the
:literal:`curl_multi_exec` function and, for its functionality, at least
libcurl 7.59.0. This minimum version is not a security assessment.
Install operating system and PHP security updates according to the
respective vendor's guidance. The process needs access to its controlled
DNS servers or explicitly configured static host entries.

.. warning::
    HTTP, HTTPS, ALL and NO_PROXY process variables are conservatively
    rejected regardless of case. This includes :literal:`NO_PROXY=*`.
    A corporate proxy requires a separately qualified adapter; this
    version does not support proxy operation.

.. _installation-check:

Verify the installation
=======================

:literal:`config-check` validates the schema and reports the policy
revision. :literal:`doctor` checks the mode, registry, Core and SDK
versions, cURL capabilities and proxy variables. Neither command sends
HTTP to a target. Exit code 0 from :literal:`doctor` confirms the
supported protected mode. Exit code 2 identifies an explicitly
unprotected mode; exit code 3 indicates invalid configuration or an
unsupported capability. See :ref:`operations-diagnostics` for details.

After activation, test a controlled public target followed by a forbidden
internal target. The latter must leave the target's TCP and HTTP counters
unchanged. Project tests must call the actual RequestFactory or bound
client in use. Successful diagnostics do not qualify an arbitrary SDK path.

.. _installation-update:

Updates and removal
===================

Update the project dependency lock within the supported ranges. A compatible
dependency patch or minor does not require updating the extension. Review
extension and address-rule updates separately when those components change.
Rebuild caches and restart long-lived workers after each policy or
package change. Existing clients retain their immutable configuration
snapshot until they are replaced.

Before removing the extension, adjust bound clients and service aliases
in the project. Deactivation removes the additional protection from the
TYPO3 HTTP path. Deployment and rollback steps are described in
:ref:`operations-rollout` and :ref:`operations-rollback`.
