.. _decision-single-extension:

=========================
One installable extension
=========================

:Status: Accepted
:Date: 2026-10-09
:Reference: Revises the two-package layout from the original ADR-002

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
qualified locks. The runtime contract continues to check exact, complete
SDK tuples. The tuple 7.15.3 / 2.5.2 / 2.13.0 included in both tested
official Core archives receives targeted additional qualification after
the previous implementation rejected it as expected.

The manual, installation paths, configuration fields, APIs and operational
limits are fully documented under :file:`Documentation/`. Additional
specifications, detailed execution evidence and the optional Vault patch
remain a separate source and evidence package. They are not installed as
runtime prerequisites.

.. _decision-single-extension-consequences:

Consequences
============

Runtime loading paths and test configurations change; policy rules and
APIs remain unchanged. The additional classic dependency row extends only
the exact qualified tuple. Untested patch versions and mixed versions
remain blocked. The PHP and SDK matrix is rerun against the combined source,
as are real Core archives, wire tests and mutation tests.

The package is a GPL-2.0-or-later extension that preserves the MIT notices
of its embedded core. Its internal framework-independent structure allows
separate core tests, but does not promise a second published Composer
product. The alpha version is supplied locally as an importable ZIP. TER
publication and operator acceptance remain separate actions.
