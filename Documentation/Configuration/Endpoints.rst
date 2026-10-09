.. _configuration-endpoints:

=================
Endpoint profiles
=================

A profile binds an approved integration to its origin, methods and narrow
networks. The client obtains its binding through
:php:`EndpointClientFactoryInterface::forEndpoint()` when the trusted
service is constructed. Never derive the profile ID from user fields,
URL parameters or request headers.

.. _configuration-endpoint-fields:

Fields
======

.. confval:: origin
    :type: string
    :default: no default; required

    Exact HTTP or HTTPS origin comprising scheme, host and optional port.
    No path, trailing slash, query, fragment or credentials are allowed.
    For example: :literal:`https://erp.internal.example:8443`.
    IPv6 literals require square brackets. Default ports are canonicalized.

.. confval:: allowedCidrs
    :type: list<string>
    :default: no default; required

    Non-empty list. IPv4 networks must be no broader than /24; IPv6 networks
    no broader than /64. Specify individual addresses as /32 or /128
    respectively. All resolved addresses must match these networks, even
    for an endpoint resolving publicly. Operator restrictions and hard
    metadata restrictions take precedence.

.. confval:: methods
    :type: list<string>
    :default: no default; required

    Non-empty list of valid HTTP method tokens. Comparison is exact and
    case sensitive. Standard names such as :literal:`GET` and
    :literal:`POST` are uppercase. :literal:`CONNECT` is always excluded.

.. confval:: redirects
    :type: string
    :default: 'none'

    :literal:`none` forbids redirects. :literal:`same-origin` permits them
    within the same origin, subject to the general redirect limit.
    A redirect does not grant permission to access another endpoint.

.. confval:: allowLoopback
    :type: boolean
    :default: false

    Loopback requires both :literal:`true` and exactly one permitted
    /32 IPv4 or /128 IPv6 address. The entire :literal:`127.0.0.0/8`
    network remains invalid. This setting does not permit other special
    or metadata addresses.

.. confval:: purpose
    :type: string
    :default: no default; required

    Non-empty UTF-8 purpose with at most 200 characters and no control
    characters. Describe the actual connection, such as ERP order
    synchronization.

.. confval:: owner
    :type: string
    :default: no default; required

    Non-empty responsible team or role, at most 120 UTF-8 characters,
    without control characters. Do not include credentials or personal
    secrets.

.. confval:: reviewAfter
    :type: string|null
    :default: null

    Actual calendar date in :literal:`YYYY-MM-DD` format. After that date,
    :literal:`config-check` and :literal:`doctor` report an overdue review;
    the profile is not disabled automatically.

.. confval:: expiresAt
    :type: string|null
    :default: null

    Actual timestamp with timezone, for example
    :literal:`2027-04-01T00:00:00Z`. Optional fractional seconds have at
    most six digits. A date without time or an implicit server timezone
    is not accepted. After expiry, every new attempt is denied, including
    redirects, retries and sends after secret or body preparation.
    Transfers already in progress are not immediately revoked.

.. _configuration-endpoint-networks:

Address classes and revocation
==============================

A bound permission can allow narrow RFC1918, ULA and CGNAT networks.
Loopback has the additional single-address rule. Link-local, multicast,
documentation networks, special reserved ranges and known metadata
addresses do not receive a general exception.

IPv4-mapped IPv6 targets are classified as their embedded IPv4 address
and compared with IPv4 networks. A configuration such as
:literal:`::ffff:10.23.4.12/128` is invalid; use
:literal:`10.23.4.12/32` instead.

Profile changes require replacing the configuration snapshot and its
clients. A different registry rejects foreign or outdated contexts.
A client retained in an old worker still owns its old registry: restart
that worker to revoke access operationally. A DNS cache hit bypasses
neither the profile checks of the registry in use nor
:literal:`expiresAt`.
