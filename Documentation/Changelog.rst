.. _changelog:

=========
Changelog
=========

.. _changelog-012:

Version 0.1.2 (2026-10-11)
==========================

This alpha maintenance release tightens malformed input handling and the
transfer lifecycle. It rejects bracketed IPv4/DNS authorities and invalid
resolver records before connection planning, validates configuration and
transport options, and settles leases that lose a request or supported runtime.
Reporting remains bounded and redacted.

Development uses shared style, level 10 static analysis, architecture,
fuzz, policy-performance and whole-file mutation checks. Coverage reports use
measured repository paths; release verification uses supported provenance
options. Symfony Console 8 is permitted alongside 7.4 in compatible graphs.

All fourteen original numbered architecture proposals are restored in full
in English and German under :ref:`adr-index`. ADR-0015 through ADR-0018 record
the accepted package, public RequestFactory, controlled DNS wire and single-use
cURL choices and their relationships to those historical proposals. Their
restoration does not change runtime behavior or turn specified tests into runs.

Current requirements and decisions live in this manual, tests in Tests and
support tools in Build. Original drafts, obsolete runs and the foreign Vault
reference overlay remain in immutable Git history. The extension remains one
package with its embedded security core and English/German manual; the registered
RequestFactory path receives default protection without a Vault migration.

Version 0.1.1 (2026-10-09)
==========================

Corrected the shared DCO workflow's event selection for pull requests and main
pushes. Its recorded published artifacts and verification are described under
:ref:`release-provenance`.

Version 0.1.0 (2026-10-09)
==========================

Initial alpha source with the embedded security core, TYPO3 integration,
endpoint clients, controlled native transport and offline diagnostics.
The first tag's publisher was blocked before artifacts and TER publication;
version 0.1.1 retained the protection behavior and corrected that workflow.

The repository's `complete changelog
<https://github.com/netresearch/typo3_http_guard/blob/main/CHANGELOG.md>`_
keeps the original entries. Verification counts apply to their recorded source,
not automatically to a later version. Alpha status does not establish human
security review, an operator pilot, certification or external manual approval.
