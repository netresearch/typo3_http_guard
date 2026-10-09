.. _start:

.. _http-guard:

==========
HTTP Guard
==========

HTTP Guard controls outbound HTTP requests through the registered TYPO3
RequestFactory path. The transport checks the destination before each
connection attempt and binds the connection to the verified IP addresses.
Internal destinations also require an explicitly bound client.

Version 0.1.0 is an alpha release. It includes the security core, the TYPO3
adapter, the address rules and this manual in **one** extension with the key
:literal:`nr_http_guard`. A classic installation requires neither a Composer
command nor an additional HTTP Guard package.

.. _http-guard-start:

Getting started
===============

* :ref:`installation`: Requirements and installation with Composer or ZIP.
* :ref:`configuration`: Every policy field and an internal endpoint example.
* :ref:`api`: Bound PSR-18 clients and Public Fetch in your own project.
* :ref:`operations`: Diagnostics, rollout, changes and rollback.
* :ref:`security`: DNS, transport, redirects and coverage limits.

Without custom configuration, the mode is :literal:`enforce`. Private
addresses, loopback, metadata destinations and special-purpose networks are
then blocked for ordinary public requests. An endpoint profile alone does
not give an ordinary RequestFactory call additional permission.

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
