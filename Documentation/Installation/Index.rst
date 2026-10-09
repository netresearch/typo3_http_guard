.. _installation:

============
Installation
============

.. _installation-requirements:

Requirements
============

This alpha version is restricted to TYPO3 **13.4.35** and **14.3.7**.
PHP must meet the requirements of the selected TYPO3 Core. This extension
permits PHP 8.2 through 8.5. PHP 8.2, 8.3, 8.4 and 8.5 have
been qualified with these complete SDK combinations:

.. list-table:: Exact dependencies of the controlled transport
    :header-rows: 1

    * - Guzzle
      - Promises
      - PSR-7
      - Usage
    * - 7.15.3
      - 2.5.2
      - 2.13.0
      - Official classic Core archives 13.4.35 and 14.3.7
    * - 7.15.5
      - 2.5.3
      - 2.13.1
      - Qualified Composer installation
    * - 8.2.0
      - 3.0.2
      - 3.1.0
      - Qualified Composer installation

Composer constraints list the individual permitted versions. At runtime,
HTTP Guard also checks the **complete combination**. Mixing entries from
different rows is rejected. A new Core or Guzzle patch version requires
qualification before it is supported; successful Composer resolution
alone does not establish compatibility.

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

.. _installation-composer:

With Composer
=============

The repository provides the complete extension source. The alpha package
has not been published on Packagist. For a local installation, copy or
clone the extension into :file:`packages/nr_http_guard/` in the TYPO3
project. Add a path repository to the existing project configuration:

.. code-block:: json
    :caption: Addition to the project's composer.json

    {
        "repositories": {
            "nr-http-guard-local": {
                "type": "path",
                "url": "packages/nr_http_guard",
                "options": {
                    "symlink": false,
                    "versions": {"netresearch/nr-http-guard": "0.1.0"}
                }
            }
        }
    }

Then install the single extension package:

.. code-block:: bash
    :caption: Installation in a TYPO3 project

    composer require netresearch/nr-http-guard:0.1.0 --with-all-dependencies
    vendor/bin/typo3 cache:flush
    vendor/bin/typo3 http-guard:config-check
    vendor/bin/typo3 http-guard:doctor

These examples assume a dependency lock approved by the project. Compare
the resolved dependencies with the table before deployment. The extension
does not require an additional :literal:`netresearch/http-guard` package.

.. _installation-classic:

Classic installation without Composer
=====================================

The :file:`nr_http_guard_0.1.0.zip` archive contains extension files
directly at its root. It has no enclosing project folder or additional
vendor directory. The official TYPO3 installation supplies Guzzle,
PSR components and Symfony.

1. Prepare a supported classic TYPO3 installation. The official Core
   archives contain the SDK combination in the first table row.
2. Import the extension ZIP using the Extension Manager's upload function.
   If the hosting environment does not offer that route, extract the
   complete archive into :file:`typo3conf/ext/nr_http_guard/` and activate
   the extension in the Extension Manager.
3. Rebuild the system and dependency injection caches. Retain the supplied
   :file:`composer.json`: TYPO3 needs its metadata even in classic mode
   and registers both embedded PHP namespaces itself.
4. Configure the policy in the project configuration and run diagnostics.
   In classic projects, the CLI entry point is usually
   :file:`typo3/sysext/core/bin/typo3`. The actual path depends on how
   the project links the Core.

.. code-block:: bash
    :caption: Diagnostics for a classic installation

    php typo3/sysext/core/bin/typo3 cache:flush
    php typo3/sysext/core/bin/typo3 http-guard:config-check
    php typo3/sysext/core/bin/typo3 http-guard:doctor

The archive supports local installation. Publishing to the TYPO3
Extension Repository is a separate step; this alpha has not been
published to TER.

The official guides explain
`classic TYPO3 installation using archives
<https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/Administration/Installation/ClassicMode/TarballZip.html>`_
and an extension's
`composer.json for classic compatibility
<https://docs.typo3.org/permalink/t3coreapi:ext-composer-json-classic-compatible>`_.

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

Update the extension, address rules and dependency lock together.
Rebuild caches and restart long-lived workers after each policy or
package change. Existing clients retain their immutable configuration
snapshot until they are replaced.

Before removing the extension, adjust bound clients and service aliases
in the project. Deactivation removes the additional protection from the
TYPO3 HTTP path. Deployment and rollback steps are described in
:ref:`operations-rollout` and :ref:`operations-rollback`.
