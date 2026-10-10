.. _adr-0016:

===================================================
ADR-0016: Public RequestFactory as the URI boundary
===================================================

:Status: Accepted
:Date: 2026-10-10
:Relation: Supplements ADR-0003; its middleware and transport boundaries remain.

.. _adr-0016-context:

Context
=======

A PSR-7 URI object may already have lost information from the original
string. Middleware cannot recover that information to distinguish forbidden
raw syntax. The registered TYPO3 path therefore needs validation before
Core processes the raw URI. The public
:literal:`TYPO3\CMS\Core\Http\RequestFactory` is the entry point for this
boundary; the internal :literal:`GuzzleClientFactory` is not replaced.

Core 13 and Core 14 differ in :literal:`readonly` inheritance. A version
number alone does not establish a compatible parent class. A conflicting
object mapping or different factory instances would also change the
qualified entry point.

.. _adr-0016-decision:

Decision
========

:file:`ext_localconf.php` registers :literal:`GuardedRequestFactory13` or
:literal:`GuardedRequestFactory14` according to the actual supported Core
shape, when no existing mapping is present. Registration is validated on
every request, including Disabled mode. Conflicts are neither overwritten
nor silently accepted. The Core factory and the PSR factory alias must
resolve to the same expected instance.

:literal:`RequestFactoryCompatibility` validates the supported version,
inheritance, visibility, return type, parameter names, types and defaults,
and constructor before loading the replacement class. The parent's
:literal:`readonly` shape must match its Core branch. The public request
signature remains intact.

:literal:`RawRequestGuardTrait` validates the raw URI before PSR-7
processing in Enforce mode. Observe reports a denial and continues to
delegate; Disabled skips raw policy validation. The replacement then
delegates to the exact original Core factory, preserving method, options
and context. Middleware ordering, Core context restrictions and the
controlled terminal transport from :ref:`adr-0003` remain additional
boundaries.

.. _adr-0016-alternatives:

Alternatives considered
=======================

* **PSR-7 middleware alone:** cannot validate raw information already lost.
* **Replace the internal GuzzleClientFactory:** couples the extension to an
  internal Core API and replaces more than the required URI boundary.
* **One replacement class for both Core branches:** ignores incompatible
  :literal:`readonly` inheritance rules.
* **Accept only a version number or any existing mapping:** establishes
  neither the actual ABI nor the active factory identity.

.. _adr-0016-consequences:

Consequences
============

The registered Core factory gains an earlier validation boundary without
a second HTTP stack. Incompatible APIs or conflicting registration produce
a controlled failure. Projects with their own factory replacement need an
explicit integration. Early bootstrap calls, independent clients and
direct sockets remain outside this boundary. For an already constructed
PSR-18 request, raw URI validation remains the caller's responsibility;
see :ref:`api-raw-uri` and :ref:`security-coverage`.

.. _adr-0016-verification:

Sources and evidence
====================

The implementation is in :file:`Classes/Http/RequestFactoryCompatibility.php`,
:file:`Classes/Http/RawRequestFactoryRegistration.php`,
:file:`Classes/Http/RawRequestGuardTrait.php` and the two replacement
classes. Wiring is in :file:`Configuration/Services.yaml` and
:file:`ext_localconf.php`.

:file:`Tests/Unit/RequestFactoryCompatibilityTest.php`,
:file:`Tests/Unit/RequestFactoryAbiContractTest.php`,
:file:`Tests/Unit/RawRequestFactoryModeTest.php` and
:file:`Tests/Unit/CoreConfigurationShapeTest.php` maintain compatibility,
mode and configuration controls. The genuine Core bootstrap probe in
:file:`Tests/Functional/production-bootstrap.php` checks factory identity
and raw syntax rejection with separate contact counters. Previously
executed results are recorded under :ref:`verification-report`.
Authoring this ADR inspected the current sources; it did not execute these
tests again or produce a new Native mutation measurement.

.. _adr-0016-reassessment-trigger:

Reconsideration trigger
======================

Changes to the public Core signature, inheritance, object registration or
PSR alias wiring require renewed ABI and bootstrap qualification. An
expanded coverage promise needs evidence for each additional entry path.
