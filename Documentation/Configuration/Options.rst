.. _configuration-options:

===============
General options
===============

All paths are relative to :literal:`EXTCONF.nr_http_guard`.
Missing fields use the defaults below.

.. _configuration-mode:

Mode and addresses
==================

.. confval:: schemaVersion
    :type: integer
    :default: 1

    Only schema version 1 is accepted.

.. confval:: mode
    :type: string
    :default: 'enforce'

    :literal:`enforce` controls the transport and blocks denied requests.
    :literal:`observe` evaluates and reports decisions but does not block
    additional requests or provide verified transport binding.
    :literal:`disabled` disables the additional protection. The mode never
    changes automatically on error. Existing Core or Vault checks remain
    effective in their respective adapter.

.. confval:: deniedCidrs
    :type: list<string>
    :default: []

    Additional operator restrictions for IPv4 or IPv6. These networks
    remain denied even with a valid endpoint profile. An empty value does
    not remove built-in restrictions. IPv4-mapped IPv6 CIDRs are not
    accepted; configure the corresponding IPv4 CIDR instead.

.. confval:: endpoints
    :type: map<string, array>
    :default: []

    At most 128 named profiles. All profile fields are documented in
    :ref:`configuration-endpoints`.

.. _configuration-resolver:

Resolver
========

.. confval:: resolver.staticHosts
    :type: map<string, list<string>>
    :default: []

    Exact canonical hostnames with complete, non-empty IP lists.
    Wildcards, IP hosts as keys and operating system search domains are
    forbidden. Static responses pass the same address and endpoint checks
    as DNS responses. A static entry grants no permission by itself.

.. confval:: resolver.cacheTtlSeconds
    :type: integer
    :default: 5

    Range: 0 to 5 seconds. Zero disables DNS memoization. Entries never
    exceed the smallest remaining TTL in the complete response and CNAME
    chain. Negative or incomplete responses are not cached as positive
    results.

.. confval:: resolver.cacheMaxHosts
    :type: integer
    :default: 32

    Range: 1 to 1024. Maximum positive host entries per resolver instance.
    When the cache is full, its oldest entry is evicted.

.. confval:: resolver.maxAddresses
    :type: integer
    :default: 64

    Range: 1 to 64. Larger responses are rejected in full rather than
    truncated to their first permitted candidates.

.. confval:: resolver.maxCnameHops
    :type: integer
    :default: 8

    Range: 0 to 8. Cycles and longer chains are rejected. Zero prohibits
    following a CNAME redirect.

.. _configuration-redirect-tls:

Redirects and TLS
=================

.. confval:: redirects.max
    :type: integer
    :default: 5

    Range: 0 to 10. Upper limit for supported redirect paths. Endpoint
    profiles may additionally forbid redirects entirely. PSR-18
    :php:`sendRequest()` never follows redirects, regardless of this value.

.. confval:: tls.requireVerification
    :type: boolean
    :default: false

    With :literal:`true`, callers cannot disable TLS verification.
    The default :literal:`false` does not disable verification itself:
    the transport normally uses :literal:`verify=true`, but permits an
    explicitly configured :literal:`verify=false` SDK option.
    :literal:`doctor` warns when policy does not require verification.
    For internal HTTPS targets, use an appropriate CA bundle and
    :literal:`requireVerification=true`.

.. _configuration-logging:

Logging
=======

.. confval:: logging.allowedSampleRate
    :type: integer|float
    :default: 0

    A finite number from 0 to 1. Fraction of allowed decisions to log;
    the default is zero percent. Denial counters remain available even
    when log events are rate limited.

.. confval:: logging.hostMode
    :type: string
    :default: 'hash'

    :literal:`hash` pseudonymizes the host using the configured HMAC key.
    Without a key, the host is omitted. :literal:`plain` logs the canonical
    host in clear text and is an explicit operator choice. Paths, queries
    and complete URLs are omitted in both modes.

.. confval:: logging.hostHmacKeyEnv
    :type: string|null
    :default: null

    Name of a real process environment variable containing the HMAC key.
    The name uses ASCII letters, digits and underscores, starts with a
    letter or underscore, and contains at most 128 characters.
    The key itself must not be part of a policy array or request.

.. confval:: logging.denyRateLimitPerMinute
    :type: integer
    :default: 60

    Range: 1 to 10000. Rate limits denial log events, never policy
    enforcement. The event format is documented in
    :ref:`operations-logging`.
