.. _release-provenance:

===============================
Release provenance and integrity
===============================

.. _release-provenance-existing:

Verified published alpha
========================

`v0.1.1
<https://github.com/netresearch/typo3_http_guard/releases/tag/v0.1.1>`_
is bound to signed tag :literal:`v0.1.1` and source commit
:literal:`3997089a259b6b919eed4e6c3bf7f6715b948ad1`. Its ten published
assets are two source archives, two SBOMs, :file:`checksums.txt` and the
five matching Sigstore bundles. The registries carry version 0.1.1.

The original publishing run created the release and registry packages but
ended with a verification-command failure. The corrected
`verification-only run 37964411091
<https://github.com/netresearch/typo3_http_guard/actions/runs/37964411091>`_
passed against the original immutable publisher. Its publication jobs were
skipped and no assets were replaced. This is verification of an existing
release, not a new release or an assertion that the original workflow
finished green.

That run verifies all five keyless blob signatures, the signed checksum
file's four targets and provenance for the two archives. Every regular
archive file is compared with the canonical source manifest after removing
only the known :file:`nr-http-guard/` prefix. The published ZIP, TAR and TER
package contain the same 131 canonical files in the recorded release proof.

.. _release-provenance-commands:

Verify a downloaded release
===========================

Download the payloads and bundles, then verify signatures before trusting
the checksum file. Cosign and GitHub CLI are external verification tools;
these commands do not install or execute the extension archive.

.. code-block:: bash
    :caption: Download existing v0.1.1 assets

    gh release download v0.1.1 \
        --repo netresearch/typo3_http_guard \
        --dir http-guard-0.1.1
    cd http-guard-0.1.1

.. code-block:: bash
    :caption: Verify the ZIP and signed checksum manifest

    cosign verify-blob \
        --bundle nr-http-guard-0.1.1.zip.sigstore.json \
        --certificate-identity "https://github.com/netresearch/typo3-ci-workflows/.github/workflows/release-typo3-extension.yml@refs/heads/main" \
        --certificate-oidc-issuer "https://token.actions.githubusercontent.com" \
        nr-http-guard-0.1.1.zip
    cosign verify-blob \
        --bundle checksums.txt.sigstore.json \
        --certificate-identity "https://github.com/netresearch/typo3-ci-workflows/.github/workflows/release-typo3-extension.yml@refs/heads/main" \
        --certificate-oidc-issuer "https://token.actions.githubusercontent.com" \
        checksums.txt
    sha256sum --strict --check checksums.txt

Repeat the blob-signature command for the TAR and each SBOM with that
payload's own filename and bundle. A checksum match by itself does not
establish the signer's identity.

The workflow additionally uses :literal:`gh attestation verify` for each
archive, binding the source digest and tag, the certificate identity and
the original run's resolved reusable signer digest. It rejects self-hosted
runners and derives the expected archive manifest with trusted verifier
code while treating the old release source only as data. The exact
verification logic is in
`release.yml
<https://github.com/netresearch/typo3_http_guard/blob/main/.github/workflows/release.yml>`_.

.. _release-provenance-scope:

Provenance and SBOM scope
========================

The signed SLSA-v1 statement attests **the two source archives** and their
source/tag/builder binding. It does not attest the SBOMs. Project-controlled
build execution plus signed provenance does not by itself establish an
isolated SLSA level 3 build or certification.

The release SPDX and CycloneDX files are supplemental repository
inventories. They are not resolved PHP/TYPO3 dependency graphs and are not
archive-only file inventories. Separately, the security workflow resolves
and audits actual production graphs for Core 13 and 14, retaining per-graph
locks, audit output and CycloneDX SBOMs. See
:ref:`dependency-report-current`.

A successful provenance check does not prove TER, Packagist or hosted
manual publication. Registry publication for 0.1.1 is recorded separately.
The Intercept manual deployments remain Awaiting Approval; the extension
already includes EN/DE sources. No human security review, operator pilot,
Best Practices level or Baseline level is implied by artifact signatures.
