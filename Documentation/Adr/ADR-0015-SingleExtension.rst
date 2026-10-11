.. _decision-single-extension:
.. _adr-0015:

===================================
ADR-0015: One installable extension
===================================

:Status: Accepted
:Date: 2026-10-09
:Supersedes: ADR-0002, package and publication proposal

.. _decision-single-extension-context:

Context
=======

The extension must include all its runtime components and its complete
manual, and support installation in classic TYPO3 projects without a
Composer command. An additional required library package would make this
installation path depend on separate dependency management. The separation
between policy and transport code and TYPO3 integration remains necessary
for isolated tests and adapter development.

.. _decision-single-extension-choice:

Decision
========

One TYPO3 extension, :literal:`netresearch/nr-http-guard`, is supplied with
the key :literal:`nr_http_guard`. The security core is under
:file:`Classes/HttpGuard/`, and its runtime data is under
:file:`Resources/Private/HttpGuard/data/`. The public PHP namespaces and APIs
remain intact. TYPO3 registers both production namespaces from the bundled
PSR-4 metadata.

The extension does not contain a second vendor copy. Classic projects use
the dependencies of the official Core archive; Composer projects use their
project locks. Production constraints use semantic ranges with explicit
minima and supported majors. Runtime guards check the actual parent API,
SDK capabilities and middleware inventory. A single-use public cURL factory
limits each lease to at most one delegated factory creation, independently
of private SDK retry counters. Fixed Core/SDK fixtures remain reproducible
test snapshots; the currently tested official Core archives supply the
Guzzle 8 graph.

The complete manual, requirements overview and accepted decisions are under
:file:`Documentation/`; maintained tests and build tools are under
:file:`Tests/` and :file:`Build/`. The numbered historical ADRs remain in
:ref:`adr-index`. Original Markdown specifications, raw runs and the optional
foreign Vault overlay remain in immutable Git history and a verified
external archive, rather than another installable package.
See :ref:`development-requirements` for the original IDs and current mapping.

The implemented public Core RequestFactory boundary, controlled DNS wire
backend and single-use SDK factory are detailed in :ref:`adr-0016`,
:ref:`adr-0017` and :ref:`adr-0018`.

.. _adr-0015-alternatives:

Alternatives considered
=======================

The separate library and adapter packages proposed in :ref:`adr-0002`
require separate dependency installation in classic TYPO3 projects.
The single extension satisfies the requested TER installation path while
retaining the internal separation between the security kernel and adapter.
Bundling another vendor tree would duplicate Core dependencies and make
their maintenance ambiguous; the extension uses the installed Core's graph.

.. _decision-single-extension-consequences:

Consequences
============

Runtime loading paths and test configurations change; policy rules and
APIs remain unchanged. The additional classic dependency row extends only
the reproducible fixture coverage. Compatible patches and minors within
the semantic ranges remain usable without a new extension release. Actual
API incompatibilities and unsupported major graphs fail closed. Fixed and
floating native CI rows exercise Core, wire and mutation-sensitive behavior;
each recorded execution remains bound to its own source and dependency lock.

The package is a GPL-2.0-or-later extension that preserves the MIT notices
of its embedded core. Its internal framework-independent structure allows
separate core tests, but does not promise a second published Composer
product. Alpha version **0.1.1 is published on TER and Packagist**, with an
importable extension ZIP. Publication does not establish an operator pilot
or deployment of the historical nr-vault reference patch. The user has
authorized alpha development and merging after repeated independent reviews
and green applicable checks without an additional human acceptance gate.
