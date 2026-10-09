.. _start:

.. _http-guard:

==========
HTTP Guard
==========

Install and activate HTTP Guard to add server-side request forgery (SSRF)
protection to TYPO3's standard HTTP client, :php:`RequestFactory`. Existing
calls through the registered factory are protected automatically; no
application rewrite or custom policy configuration is needed.

When an application fetches URLs from user input, imports or external
payloads, the default :literal:`enforce` mode blocks private, loopback,
cloud metadata and special-purpose destinations before a connection starts.
DNS results and redirects are checked too. Allowed connections are bound
to verified IP addresses.

Version 0.1.1 is an alpha release. It includes the security core, the TYPO3
adapter, the address rules and this manual in **one** extension with the key
:literal:`nr_http_guard`. A classic installation requires neither a Composer
command nor an additional HTTP Guard package.

.. _http-guard-start:

Install and activate
====================

For a Composer project, install the published 0.1 series and rebuild caches:

.. code-block:: bash
    :caption: Installation in a TYPO3 project

    composer require netresearch/nr-http-guard:^0.1
    vendor/bin/typo3 cache:flush

Composer registers the extension automatically. For a classic installation,
install :literal:`nr_http_guard` from
`TER <https://extensions.typo3.org/extension/nr_http_guard>`_, then activate
it in the Extension Manager and rebuild caches.
Default protection is active without endpoint configuration.
See :ref:`installation` for requirements and diagnostics.

Internal integrations need an endpoint profile and a client explicitly
bound to that profile. Adding a profile does not grant ordinary
RequestFactory calls access to internal destinations.

.. _http-guard-next:

Next steps
==========

* :ref:`installation`: Requirements and installation with Composer or ZIP.
* :ref:`configuration`: Every policy field and an internal endpoint example.
* :ref:`api`: Bound PSR-18 clients and Public Fetch in your own project.
* :ref:`operations`: Diagnostics, rollout, changes and rollback.
* :ref:`security`: DNS, transport, redirects and coverage limits.

.. _http-guard-manual:

Manual
======

.. toctree::
    :maxdepth: 2

    Installation/Index
    Configuration/Index
    Api/Index
    Operations/Index
    Security/Index
    Development/Index
    Decisions/Index

.. _http-guard-scope:

Scope
=====

The extension does not replace a network firewall. It controls the
documented TYPO3 HTTP path after registration and the clients created by its
factories. Incoming PSR-15 middleware, early bootstrap requests, third-party
SDK clients, direct cURL calls and custom socket connections require
separate integration. See :ref:`security-coverage` for the complete scope.

The optional nr-vault adaptation is a separate migration. The global
extension does not protect Vault automatically. The required adapter and
its evidence are provided in the additional source and evidence package.
This manual is sufficient to install and operate the extension.
