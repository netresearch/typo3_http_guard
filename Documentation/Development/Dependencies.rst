.. _dependency-report:

================================
Dependencies and security status
================================

.. note::
    This page translates the dependency report recorded on 9 October 2026.
    Its source is the unchanged
    `German report at commit 7a3a39b
    <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/docs/Abhaengigkeiten.md>`_.
    Historical findings are bound to its recorded package revisions.
    The separate current-resolution section below records the new audit;
    the translated historical report is preserved after that section.

.. _dependency-report-current:

Current patched Core resolution
===============================

On 9 October 2026, TYPO3 released
`13.4.36 <https://get.typo3.org/release-notes/13.4.36>`_ and
`14.3.8 <https://get.typo3.org/release-notes/14.3.8>`_. Both releases require
:literal:`enshrined/svg-sanitize` **1.0.0**, which fixes the three recorded
SVG advisories. The extension now restricts Core to these exact patches.
It does not alias, replace or modify the framework's sanitizer dependency.

Fresh full Core/Backend/Frontend/Fluid resolutions for both patches with
Guzzle 7.15.5 / Promises 2.5.3 / PSR7 2.13.1 and Guzzle 8.2.0 / Promises
3.0.2 / PSR7 3.1.0 all select sanitizer 1.0.0. All four vulnerability
audits report **zero advisory findings**. Composer's advisory blocking
remains enabled; active preparation and CI contain no ignored advisory IDs.

The security workflow resolves two isolated production dependency graphs
from the actual root manifest, one for each exact Core target. Its
preflight checks reject differences between package constraints and the
qualified fixtures before matrix overrides. After resolution, the installed
runtime check validates the real Core, parent ABI and complete SDK tuple
before generating a CycloneDX SBOM. Each Core job preserves its own manifest,
lock, SBOM and audit JSON. The audit fails on any vulnerability advisory or
an unavailable audit service; abandoned-package metadata is reported
separately. These workflow guards are implemented and locally checked;
fresh remote execution is required before the change is merged.

Core 13 still requires the abandoned upstream package
:literal:`doctrine/annotations` and no replacement is declared. Its default
Composer audit exits 1 solely for this maintenance warning. The explicit
:literal:`--abandoned=report` policy preserves the warning and fails on any
vulnerability advisory. Both Core 14 graphs also pass the default audit
with exit 0. This distinction keeps maintenance status separate from known
vulnerabilities; it does not certify the dependency graph for all uses.

Both official classic distributions now bundle Guzzle 8.2.0 / Promises
3.0.2 / PSR7 3.1.0 and sanitizer 1.0.0. Their tarball SHA256 values are
verified against the official release notes. RequestFactory and
GuzzleClientFactory sources are byte-identical to the previous Core patches;
actual patched-Core runtime results are recorded in
:ref:`verification-current-core`.

Active fixture manifests live under :file:`Build/Fixtures/`. The original
twelve manifests and twelve lock files remain byte-for-byte in three
explicit ZIP archives under :file:`evidence/`, with original-path and SHA256
mappings. They describe historical runs and are never installation inputs.
Dependency discovery therefore no longer sees them as active lock files;
historical audit output and Git history remain unchanged.

.. _dependency-report-composer:

Historical Composer resolution
==============================

The security core embedded directly in the extension accepts three complete,
exact combinations:

* Guzzle 7.15.3 / Promises 2.5.2 / PSR-7 2.13.0, from the then-official
  classic Core archives.
* Guzzle 7.15.5 / Promises 2.5.3 / PSR-7 2.13.1.
* Guzzle 8.2.0 / Promises 3.0.2 / PSR-7 3.1.0.

There is no additional production package for the core. In addition to the
Composer constraints, the runtime rejects unknown or mixed combinations
before native send. Minimal test locks are resolved with PHP 8.2 platform
requirements; higher PHP versions do not replace checking the lower bound.

The recorded extension limited Core to 13.4.35 or 14.3.7. These Core versions
were tested in four Composer bootstrap/RequestFactory instances and in
their official complete classic distributions. The mapping accounts for
complete distributions reporting :literal:`typo3/cms`, rather than
:literal:`typo3/cms-core`, in the InstalledVersions catalogue. The extension
therefore determines the Core revision through the actual TYPO3 version.
The Vault patch applies to the base commit named in its manifest and adds
no mandatory guard dependency to the existing project.

The recorded TYPO3/Vault test resolution contains
:literal:`enshrined/svg-sanitize` 0.22.0 with three audit findings published
on 8 October 2026: CVE-2026-107379, CVE-2026-107380 and CVE-2026-107381.
The complete audit also lists the abandoned development tool
:literal:`symplify/rule-doc-generator-contracts`. These findings are not new
guard dependencies. The audit remains part of the recorded delivery;
production acceptance of the affected complete dependency resolution is
outstanding.

For the recorded isolated Core test instances, the three specific SVG advisory
IDs were excepted during installation. These instances process no SVGs.
The delivery's production manifests contain no such exceptions. The
exception enables integration tests; it does not demonstrate safe SVG
processing. At that revision, deployment acceptance required fixing or
assessing the affected framework dependency against its actual use.
The primary advisory sources are
`DTD crash
<https://github.com/advisories/GHSA-v383-3rw5-q8rf>`_,
`stored XSS
<https://github.com/advisories/GHSA-9rjx-3jch-6vjf>`_
and
`resolver denial of service
<https://github.com/advisories/GHSA-m9xh-6747-9r6f>`_.

.. _dependency-report-platform:

libcurl and operating system
============================

Multi-address pinning has a functional lower bound of libcurl 7.59.0,
derived from the
`official CURLOPT_RESOLVE documentation
<https://curl.se/libcurl/c/CURLOPT_RESOLVE.html>`_.
The software cannot determine from a version string alone which security
fixes a distributor has backported.

The recorded local Ubuntu 24.04 execution used libcurl 8.5.0 from package
:literal:`8.5.0-2ubuntu10.15`. That revision matches the Noble fix listed in
`USN-8820-1 <https://ubuntu.com/security/notices/USN-8820-1>`_.
The notice was published on 24 September 2026. Package and changelog
records are under :file:`evidence/runtime-security` in the source repository.
This demonstrates that package revision; it does not replace a later check
of all vendor advisories.

The recorded PHP test images contain a different build: Alpine 3.24.2 with
libcurl 8.22.0 and package revision 8.22.0-r0. The
`curl page for 8.22.0 <https://curl.se/docs/vuln-8.22.0.html>`_
listed no published vulnerabilities at the time of the recorded check.
The
`version-specific JSON file <https://curl.se/docs/vuln-8.22.0.json>`_
is preserved as a downloaded original. This does not establish a general
claim about other libcurl, OpenSSL or operating-system builds. Images are
recorded by digest and by the actual executed PHP, OS and curl data.

.. _dependency-report-updates:

Updates
=======

Updating transport dependencies requires a new source review of handlers,
options, redirects, proxy environment and hidden retry paths. Then the full
applicable P0 corpus, real TYPO3/Vault paths and six targeted mutations for
each Guzzle major are repeated. Only after that are the manifest and runtime
tuples extended. A new Composer resolution alone is not approval.

New address data is updated from the documented primary sources during
development and release, checked and supplied as a versioned corpus. The
request path does not download security lists from the Internet.

The later twelve-cell kernel matrix and the three minimal audited fixture
locks are under :file:`verification/evidence/extension-matrix/` and
:file:`verification/dependencies/combined-kernel/` in the recorded source
repository. These test arrangements do not install a second HTTP Guard
production library. The operational guidance for these limits is supplied
directly with the extension under :file:`Documentation/`.
