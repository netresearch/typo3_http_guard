.. _configuration:

=============
Configuration
=============

The policy is configured exclusively in
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']`.
It is loaded as an immutable snapshot. Changes to
:literal:`HTTP.allowed_hosts` or Vault's legacy host lists do not
create an HTTP Guard endpoint permission.

Add the examples to the project configuration, for example
:file:`config/system/additional.php` or, in a matching classic project
layout, :file:`typo3conf/AdditionalConfiguration.php`. HTTP Guard does
not provide a backend editor for security permissions.

.. _configuration-example:

Internal endpoint example
=========================

Replace the example host and address with the approved production
integration. The profile applies only to a client that trusted service
wiring binds to :literal:`erp-orders`.

.. literalinclude:: _Policy.php
    :language: php
    :caption: Project configuration for a bound ERP client

Static resolution is optional. Without it, the controlled DNS resolver
is used. Every resolved address must match the permitted network. A
valid public candidate alongside a forbidden private candidate does
not make the response acceptable.

.. _configuration-schema:

Schema and validation
=====================

Unknown fields, incorrect types, invalid origins and invalid CIDRs
produce :literal:`configuration_invalid`. Strings such as
:literal:`"5"` or :literal:`"true"` do not substitute for integers or
booleans. :literal:`CONNECT` is forbidden as an endpoint method.
Profile IDs contain at most 64 ASCII characters: letters, digits,
dots, underscores and hyphens, starting with a letter or digit.

The normalized policy and the SHA-256 hash of the supplied address rules
determine the revision. Equivalent forms are normalized. Changing the
rule file produces a different revision. To use a new revision,
recreate the registry, engine and clients. This does not automatically
revoke clients in existing workers.

.. toctree::
    :maxdepth: 1

    Options
    Endpoints
