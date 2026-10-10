.. _adr-0017:

=========================================================
ADR-0017: Controlled DNS queries for the default resolver
=========================================================

:Status: Accepted
:Date: 2026-10-10
:Supersedes: ADR-0005 only for its default resolver choice.

.. _adr-0017-context:

Context
=======

:ref:`adr-0005` requires a complete, validated address set bound to the
connection. Its historical proposal also selects :literal:`dns_get_record()`
and records the absence of a hard interruption contract. The current
implementation explicitly controls the resolution path, response validation
and query deadlines. An implicit NSS, hosts-file or search-domain fallback
would introduce a different target or trust boundary.

.. _adr-0017-decision:

Decision
========

:literal:`LibraryServiceFactory` wires :literal:`StaticThenDnsResolver`
to :literal:`WireDnsQuery`. Only explicit static policy entries bypass DNS;
their addresses remain subject to policy. Other names are queried as exact
absolute FQDNs using A, AAAA and CNAME questions. There is no implicit NSS,
:file:`/etc/hosts` or search-domain fallback.

The wire backend uses numeric trusted nameservers from
:file:`/etc/resolv.conf`, read lazily at the first DNS query. At most three
nameservers are allowed. Known directives are recognized without adopting
their search or NSS semantics; unknown or invalid configuration is rejected.
A trusted resolver may use a private infrastructure address. This does not
grant permission for HTTP connections to private endpoint addresses.

:literal:`DnsPacketCodec` validates the transaction ID, question, response
status, types, lengths and compression bounds. Only a valid response with
the TC bit set repeats the same question over length-prefixed TCP within
the same query deadline. Repeated truncation or unverifiable answers do not
produce a usable resolution. The resolver follows the complete CNAME chain
and bounds both hops and addresses. :literal:`NativeOperation` converts
native warnings and notices to fixed failures without exposing their text.

The default deadline is one second per nameserver and question. Positive
memo entries last at most five seconds and no longer than the smallest
remaining TTL in the chain; using them still requires policy validation.
The complete address checks and connection pinning in ADR-0005 remain
binding. This ADR replaces only its historical default choice of
:literal:`dns_get_record()`.

.. _adr-0017-alternatives:

Alternatives considered
=======================

* **Unbounded dns_get_record as the default backend:** lacks equivalent
  explicit control over the query deadlines used here.
* **Implicit NSS, hosts or search domains:** changes the qualified resolution
  path and may introduce different targets.
* **Filter dangerous addresses or continue after DNS failure:** violates
  the complete address-set validation required by ADR-0005.
* **TCP after any UDP failure:** expands fallback beyond the validated
  truncation case.

.. _adr-0017-consequences:

Consequences
============

DNS has a bounded, auditable flow but remains separate network traffic to
trusted resolvers. The guard does not impose a process-wide network ban.
NSS-specific names need an explicit static assignment or a qualified
resolver implementation.

Three nameservers, nine names in the maximum CNAME chain and three query
types can theoretically take up to 81 seconds sequentially, plus local
overhead. This is not a guaranteed application and HTTP deadline. An SDK
timeout does not create a general resolver cancellation contract. Custom
resolvers are trusted internal extensions and must preserve complete,
bounded and verifiable answers.

.. _adr-0017-verification:

Sources and evidence
====================

The implementation is in :file:`Classes/HttpGuard/WireDnsQuery.php`,
:file:`Classes/HttpGuard/DnsPacketCodec.php`,
:file:`Classes/HttpGuard/StaticThenDnsResolver.php`,
:file:`Classes/HttpGuard/NativeOperation.php` and
:file:`Classes/Service/LibraryServiceFactory.php`. Current bounds and scope
are documented under :ref:`security-dns`.

:file:`Tests/HttpGuard/Unit/Policy/WireDnsQueryTest.php` maintains real local
UDP-to-TCP truncation and blackhole deadline probes, numeric-server controls
and lazy configuration reading. :file:`Tests/HttpGuard/Unit/Policy/WireIoBoundaryContractTest.php`
and :file:`Tests/HttpGuard/Unit/Policy/DnsWireBoundaryContractTest.php`
exercise I/O and packet boundaries. Separate target-contact controls are in
:file:`Tests/HttpGuard/Integration/DnsPolicyTransportTest.php`.
Previously executed source-bound results are under :ref:`verification-report`.
This ADR records inspected sources and does not establish a new test run.

.. _adr-0017-reassessment-trigger:

Reconsideration trigger
======================

New resolver protocols, NSS integration, search semantics or changes to
deadlines and caching require renewed trust and evidence qualification.
A different backend does not remove complete address validation or the
binding between the validated addresses and the connection.
