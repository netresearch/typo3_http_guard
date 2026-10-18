.. _changelog:

=========
Changelog
=========

.. _changelog-014:

Version 0.1.4 (2026-10-18)
==========================

This alpha maintenance release updates documentation and adds 101 unit test
cases for configuration and policy boundaries, DNS validation and resolver
fallback, TYPO3 registration, runtime checks and the transport lifecycle.
HTTP protection behavior and dependency constraints are unchanged.

Both manual languages use Netresearch cards, teal accents and footer branding.
Links point to the published manuals; dated publication observations remain
available. The repository now includes :file:`.bestpractices.json` with
source-backed OpenSSF proposals and explanations for individual criteria.
Four static-analysis false positives are documented at the reviewed TYPO3
initialization calls in development and test fixtures.

.. _changelog-013:

Version 0.1.3 (2026-10-11)
==========================

This alpha patch aligns the canonical build and Git source archives with TER's
package boundary. The two documentation editor configuration files remain in
the repository and are excluded from installable archives.

Immutable source verification reads only their two explicit development-file
declarations as data. Historical releases retain their recorded package contents.

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
