.. _licenses:

=================
Licenses and data
=================

.. _licenses-extension:

Extension and security core
===========================

The single TYPO3 extension :literal:`netresearch/nr-http-guard` is supplied
under **GPL-2.0-or-later**. The complete GPLv2 text is in :file:`LICENSE.txt`
at the extension root.

The embedded security core under :file:`Classes/HttpGuard/` was created
under MIT. Its license and copyright notices are preserved; the complete
text is in :file:`LICENSE-HttpGuard.txt`. Combining the code does not create
a second published package or claim to relicense the core.
:file:`LICENSES.md` describes the license mapping within the package.

Third-party runtime libraries are not bundled again in a separate vendor
directory. The supported TYPO3 installation provides these components;
their own license notices continue to apply. The optional nr-vault patch
belongs to the existing GPL-licensed nr-vault project.

.. _licenses-branding:

Netresearch logo
===============

The unchanged logo under :file:`Resources/Public/Icons/Extension.svg` is
copyright Netresearch DTT GmbH and licensed under
`CC-BY-SA-4.0 <https://creativecommons.org/licenses/by-sa/4.0/>`_.
The SVG retains its original license and attribution notices.

.. _licenses-sources:

Address data and sources
========================

The bundled data under
:file:`Resources/Private/HttpGuard/data/security-corpus/` contains the
derived rules, binary test corpus and a source manifest. It distinguishes
the retrieval date, registry version, hash of the downloaded original and
hash of the derived rules. The primary registries are the
`IANA IPv4 Special-Purpose Address Registry
<https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml>`_
and the
`IANA IPv6 Special-Purpose Address Registry
<https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml>`_.

Additional metadata denials are justified in the same source metadata. The
respective source licenses and rights to recorded third-party material
remain in effect. Original specifications and ADRs are preserved unchanged
in the optional evidence package. This manual does not retrospectively
alter their original statements.

Address data is versioned with the extension. A rule update requires a new
approval with corpus, CIDR and actual wire checks, and replacement of the
affected policy snapshots.
