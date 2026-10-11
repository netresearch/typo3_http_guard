.. _security:

=========================
Security model and limits
=========================

.. _security-attempt:

A connection attempt
====================

The outer boundary checks authority, context, permitted options and
registry position. Every new SDK leaf attempt then reaches the final
guard terminal, where the policy is evaluated afresh. One invalid
candidate rejects the entire address set. The native transfer is
constructed only when send progress starts. After DNS and body
preparation, profile validity and the plan are checked again.

All approved addresses are bound to the host using
:literal:`CURLOPT_RESOLVE`. URL authority and hostname remain intact
for the Host header, TLS SNI and certificate verification. Failed
pinning or connection establishment never falls back to an unbound
DNS, stream or default handler.

Each native attempt owns a cURL-multi handler and a cURL factory without
a retained easy-handle pool. Fresh connections, forbidden reuse,
disabled native DNS caching, fixed protocols and the absence of
external sharing handles prevent reuse of previous routing or
connection state. A hidden SDK rewind retry cannot bypass the terminal.

Cancellation, callback errors and transport errors clean up resources
owned by the affected attempt. A policy error cancels that call even
if an external retry decision would otherwise resend it. Ordinary
network retries require a new policy plan.

.. _security-dns:

DNS and static resolution
=========================

The default resolver queries A, AAAA and CNAME records for the exact
absolute FQDN. It uses neither NSS, search-domain expansion nor
:file:`/etc/hosts` as an implicit fallback. Static hosts are only the
explicit policy entries.

The DNS wire backend uses numeric, trusted nameservers from
:file:`/etc/resolv.conf`. The file is read lazily on the first DNS
request. Up to three nameservers are permitted. Known directives
such as :literal:`search`, :literal:`domain`, :literal:`options` and
:literal:`sortlist` are recognized without adopting their search or
NSS behavior. Unknown server configuration is rejected.

UDP responses are checked for transaction ID, question, RCODE, types,
lengths and compression boundaries. A set TC bit repeats the same
query over length-prefixed TCP within the same query deadline.
Repeatedly truncated, inconsistent, empty or incomplete responses
are not accepted. The complete CNAME chain is followed to its
terminal address and constrained by limits.

The default deadline is one second per nameserver and query. With
three nameservers, nine names in a maximum-length CNAME chain and
three query types, sequential resolution can theoretically take up
to 81 seconds plus local overhead. This is not a guaranteed total
deadline for the application and HTTP. SDK timeouts do not provide
a general resolver cancellation contract. A custom resolver is a
trusted internal extension and must preserve the same complete,
bounded and verifiable response semantics.

Positive DNS memo entries last at most five seconds and never longer
than the smallest remaining TTL in the chain. A memo hit still passes
the address, operator, profile and expiry checks of the policy
snapshot in use.

.. _security-addresses:

Address rules
=============

Addresses are evaluated in binary form, including CIDR boundaries
and IPv4-mapped IPv6. Non-public and special networks are blocked
by the default policy. The rules use versioned IANA registries and
additional fixed, documented metadata restrictions. Link-local,
multicast, documentation or reserved ranges and metadata targets
do not receive a general endpoint exception.

Narrow private RFC1918, ULA and CGNAT networks and individually
approved loopback addresses can be permitted for bound clients.
Operator restrictions take precedence. See
:ref:`configuration-endpoints` for the exact exception configuration.

The supplied rules and source hash metadata are under
:file:`Resources/Private/HttpGuard/data/security-corpus/`.
Changing these rules is a policy update with a new revision and
renewed regression and wire testing; it is not a spontaneous
runtime permission change.

.. _security-redirects:

Redirects, credentials and TLS
=============================

Response middleware may rewrite a Location. The guard boundary checks
the resulting Location afterwards, before the SDK's next send.
Ordinary requests remain on the same origin and cannot downgrade
HTTPS to HTTP. This also applies to 307 and 308 responses preserving
method and body. A profile can forbid redirects altogether.
Public Fetch creates its own requests without integration secrets
and can permit public origin changes under the same address policy.
PSR-18 does not follow redirects.

TLS verification, CA bundles and mTLS remain transport options subject
to the additional TLS policy. HTTP Guard does not replace correct
certificate configuration, CA trust or secret management.

.. _security-proxy:

Proxies and incoming headers
===========================

Explicit proxy routes and actual HTTP, HTTPS, ALL and NO_PROXY process
variables are rejected, even when a NO_PROXY match is expected.
The guard reads process variables locally rather than trusting the
incoming :literal:`Proxy` HTTP header as proxy configuration.
PHP SAPIs may remove that header from :php:`$_SERVER`; SAPI sanitization
and the independent process check are different mechanisms.

.. _security-coverage:

Coverage and trust boundary
===========================

Protection covers the registered Core RequestFactory path after
activation and the bound clients produced by guard factories.
Inconsistent middleware positions or ABI and version conflicts
explicitly reject the protected path. Existing Core context
restrictions remain cumulative. A flat legacy Vault host list does
not grant private access to the public Core client.

The extension cannot contain arbitrary PHP code execution. These
paths require their own integration:

* Early bootstrap calls before registration.
* Independent Guzzle clients, external SDKs and direct cURL or socket calls.
* A request-specific Guzzle handler replacing the stack before the first
  middleware entry.
* Incoming PSR-15 middleware and other processing unrelated to outbound HTTP.
* Vault without deliberate integration of the optional adapter patch.

Raw URI information may be lost before PSR-18; see
:ref:`api-raw-uri` for the caller's validation responsibility.
Policy snapshots do not update automatically in existing workers;
see :ref:`operations-changes`. Observe mode, offline diagnostics and
agent review do not substitute for enforcement at the actual transport.

.. _security-alpha:

Alpha acceptance
================

Version 0.1.1 is a published alpha for evaluation. The user-authorized alpha
scope requires independent agent review, resolved findings and green
applicable checks; it adds no separate human approval or operator-pilot gate.
Neither activity is claimed completed. Both remain recommendations when
assessing production use. See :ref:`assessment-reconciliation` for exact
assessment dispositions and external publication limits.
