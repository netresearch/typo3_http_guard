.. _adr-0018:

======================================================
ADR-0018: One cURL factory creation per transfer lease
======================================================

:Status: Accepted
:Date: 2026-10-10
:Relation: Supplements ADR-0003, ADR-0007 and ADR-0010.

.. _adr-0018-context:

Context
=======

A new native connection must not bypass the terminal policy check. An SDK
may request an internal retry while finishing a failed transfer, and body
preparation callbacks can re-enter handle creation. Private SDK retry
counters are not a stable boundary for these attempts.

The transfer lease in :ref:`adr-0010` therefore needs its own single-use
boundary immediately before native handle creation. This decision details
the controlled transport in :ref:`adr-0003` and :ref:`adr-0007`; it does not
restrict every independent use of cURL in the PHP process.

.. _adr-0018-decision:

Decision
========

:literal:`SingleUseCurlFactory` implements the public
:literal:`CurlFactoryInterface` and decorates :literal:`CurlFactory(0)`.
It consumes its attempt before the first delegation. Every second
:literal:`create()` request on the same instance fails with
:literal:`transport_unsupported` before the delegate creates another handle.
This includes failed and re-entrant body preparation. :literal:`release()`
delegates cleanup without resetting the consumed instance.

:literal:`TransferLease` constructs a new single-use factory and its own
:literal:`CurlMultiHandler` for each attempt. Connection and DNS state are
not carried between independent leases. Fresh connections, forbidden reuse
and disabled native DNS caching supplement connection-bound pinning.
Policy validity is checked after DNS and body preparation and before
network progress starts.

:literal:`RuntimeSupport` validates actual supported Guzzle 7 and 8 graphs
and the public factory and handler contracts before the guarded transfer.
The single-use boundary neither reads nor changes private SDK retry
counters. A genuine caller retry needs a new policy check and lease;
policy failures do not authorize an automatic retry.

Middleware ordering has a separate, deliberate dependency on the private
:literal:`HandlerStack.stack` inventory. :literal:`RuntimeSupport` probes
the installed shape with a known named entry;
:literal:`GuardedClientFactory` inspects it to verify ordering and reject
later changes. An incompatible inventory fails closed before protected
transport. This qualified coupling remains even though the factory fence
uses a public interface.

.. _adr-0018-alternatives:

Alternatives considered
=======================

* **Set a private SDK retry counter:** depends on internal SDK details.
* **Middleware before the first send only:** does not bound hidden handle
  creation within the same terminal attempt.
* **Reset the factory after release or failed preparation:** reopens a
  consumed lease for another native attempt.
* **Share a pool or lease between attempts:** carries an earlier decision
  into a new connection attempt.

.. _adr-0018-consequences:

Consequences
============

A lease can call the delegate's handle creation at most once. This bounds
factory attempts; it does not promise a particular number of packets or
of pinned addresses tried by cURL within one handle. Callback and SDK retry
failures remain controlled errors. Cleanup and cancellation still belong
to the lease; independent transfers own separate resources and do not
reuse connections.

Compatible SDK patches within the supported semantic ranges do not require
an extension release merely because their version changes. A changed
public interface or unsupported major graph is rejected before the guarded
transfer and requires renewed qualification. A private middleware inventory
change also requires requalification; public factory compatibility alone
does not establish compatibility of the entire SDK integration.

.. _adr-0018-verification:

Sources and evidence
====================

The implementation is in
:file:`Classes/HttpGuard/Transport/SingleUseCurlFactory.php`,
:file:`Classes/HttpGuard/Transport/TransferLease.php` and
:file:`Classes/HttpGuard/Transport/RuntimeSupport.php`. The separately
qualified private inventory use is in
:file:`Classes/HttpGuard/Client/GuardedClientFactory.php`.

:file:`Tests/HttpGuard/Unit/Transport/SingleUseCurlFactoryTest.php` maintains
create/release, failed preparation and re-entry controls. Its genuine
public :literal:`CurlFactory::finish()` comparison admits a second handle
without the fence and rejects it with the fence. That test creates native
handles but performs no handler tick or network I/O.
:file:`Tests/HttpGuard/Unit/Transport/TransferLeaseLifecycleContractTest.php`
maintains lease option and cleanup controls; separate contact and retry
probes are in :file:`Tests/HttpGuard/Integration/ProductionTransportTest.php`.
:file:`Tests/HttpGuard/Unit/Transport/RuntimeSupportTest.php` maintains
incompatible public API and private inventory shape controls.
Previously executed Native and contact evidence is recorded under
:ref:`verification-report`. Today's source inspection for this ADR is
neither a new test run nor a new transport measurement.

.. _adr-0018-reassessment-trigger:

Reconsideration trigger
======================

Changes to factory or handler contracts or the private middleware inventory,
alternative retry paths,
connection pooling or new transport interfaces require renewed single-use,
lifecycle and actual-contact qualification. Composer dependency resolution
alone does not establish that compatibility.
