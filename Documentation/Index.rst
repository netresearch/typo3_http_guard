.. _start:

.. _http-guard:

==========
HTTP Guard
==========

.. image:: /Images/netresearch-underline.svg
    :alt:
    :width: 200px
    :class: no-border

Install and activate HTTP Guard to add server-side request forgery (SSRF)
protection to TYPO3's standard HTTP client, :php:`RequestFactory`. Existing
calls through the registered factory are protected automatically; no
application rewrite or custom policy configuration is needed.

When an application fetches URLs from user input, imports or external
payloads, the default :literal:`enforce` mode blocks private, loopback,
cloud metadata and special-purpose destinations before a connection starts.
DNS results and redirects are checked too. Allowed connections are bound
to verified IP addresses.

Version 0.1.3 is an alpha release. It includes the security core, the TYPO3
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

.. card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :card-height: 100

    .. card:: 📦 Installation

        Check requirements and install with Composer or from TER.

        .. card-footer:: :ref:`Install HTTP Guard <installation>`
            :button-style: btn btn-primary stretched-link

    .. card:: ⚙️ Configuration

        Review policy fields and configure profiles for internal endpoints.

        .. card-footer:: :ref:`Configure policies <configuration>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 🚀 Client APIs

        Use bound PSR-18 clients and Public Fetch in your own project.

        .. card-footer:: :ref:`Explore the APIs <api>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 🛠️ Operations

        Diagnose requests and plan rollout, changes and rollback.

        .. card-footer:: :ref:`Operate HTTP Guard <operations>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 🔒 Security

        Understand DNS, transport, redirect checks and coverage limits.

        .. card-footer:: :ref:`Read the security boundaries <security>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 💻 Development

        Set up the development environment and run the verification tools.

        .. card-footer:: :ref:`Contribute to HTTP Guard <development>`
            :button-style: btn btn-secondary stretched-link

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
    Adr/Index
    Changelog

.. _http-guard-scope:

Scope
=====

The extension does not replace a network firewall. It controls the
documented TYPO3 HTTP path after registration and the clients created by its
factories. Incoming PSR-15 middleware, early bootstrap requests, third-party
SDK clients, direct cURL calls and custom socket connections require
separate integration. See :ref:`security-coverage` for the complete scope.

The optional nr-vault adaptation is a separate migration. The global
extension does not protect Vault automatically. The API chapter links the
recorded reference patch and its historical evidence under :ref:`api-vault`.
This manual is sufficient to install and operate the extension.

.. card-grid::
    :columns: 1

    .. card:: [n] A Netresearch extension

        .. image:: /Images/netresearch-underline.svg
            :alt:
            :width: 120px
            :class: no-border

        Professional TYPO3 development, integration and consulting
        by Netresearch DTT GmbH.

        .. card-footer:: `netresearch.de <https://www.netresearch.de>`__
            :button-style: btn btn-secondary stretched-link
