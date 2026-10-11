.. _release-provenance:

===============================
Release-Provenance und Integrität
===============================

.. _release-provenance-existing:

Veröffentlichtes und geprüftes Alpha-Release
==========================================

`v0.1.1
<https://github.com/netresearch/typo3_http_guard/releases/tag/v0.1.1>`_
gehört zum signierten Tag und Quellcommit
:literal:`3997089a259b6b919eed4e6c3bf7f6715b948ad1`. Die zehn Assets bestehen
aus zwei Quellarchiven, zwei SBOMs, :file:`checksums.txt` und den fünf
passenden Sigstore-Bundles. TER und Packagist führen Version 0.1.1.

Der ursprüngliche Publisher erzeugt Release und Registry-Pakete, endet aber
mit einer fehlerhaften Verifikationsanweisung. Der korrigierte
`Verifikationslauf 37964411091
<https://github.com/netresearch/typo3_http_guard/actions/runs/37964411091>`_
besteht gegen den ursprünglichen Publisher. Seine Veröffentlichungsjobs
werden übersprungen; kein vorhandenes Asset wird ersetzt. Daraus wird
kein erfolgreicher Abschluss des ursprünglichen Gesamtworkflows abgeleitet.

Der Lauf prüft fünf Keyless-Blob-Signaturen, die vier Ziele des signierten
Checksum-Manifests und die Provenance beider Archive. Die regulären Dateien
werden nach Entfernung des bekannten :file:`nr-http-guard/`-Präfixes mit
dem kanonischen Quellmanifest verglichen. Der dokumentierte Release-Nachweis
enthält 131 übereinstimmende Dateien in ZIP, TAR und TER-Paket.

.. _release-provenance-commands:

Vorhandenes Release prüfen
=========================

Die Signatur wird geprüft, bevor dem Checksum-Manifest vertraut wird.
Cosign und GitHub CLI sind externe Prüfwerkzeuge. Die folgenden Befehle
installieren und starten keine Extension-Dateien.

.. code-block:: bash
    :caption: Vorhandene v0.1.1-Assets herunterladen

    gh release download v0.1.1 \
        --repo netresearch/typo3_http_guard \
        --dir http-guard-0.1.1
    cd http-guard-0.1.1

.. code-block:: bash
    :caption: ZIP-Signatur und signiertes Checksum-Manifest prüfen

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

Für TAR und jede SBOM wird die Blob-Prüfung mit ihrem eigenen Dateinamen
und passenden Bundle wiederholt. Eine passende Checksum allein bestätigt
nicht die Identität des Signierenden.

Der Workflow bindet die Archiv-Attestations zusätzlich an Quelldigest,
Tag, Zertifikatsidentität und den tatsächlich aufgelösten Digest des
ursprünglichen wiederverwendeten Signierworkflows. Selbst gehostete Runner
werden abgelehnt. Vertrauenswürdiger Verifier-Code liest die alte
Releasequelle nur als Paketdaten. Die genaue Prüfung steht in
`release.yml
<https://github.com/netresearch/typo3_http_guard/blob/main/.github/workflows/release.yml>`_.

.. _release-provenance-scope:

Umfang von Provenance und SBOM
=============================

Das signierte SLSA-v1-Statement bindet **die beiden Quellarchive** an Quelle,
Tag und Builder. Es attestiert nicht die SBOMs. Eine projektgesteuerte
Ausführung mit signierter Provenance belegt für sich keinen isolierten
SLSA-Level-3-Build und keine entsprechende Zertifizierung.

Die Release-SBOMs in SPDX und CycloneDX sind ergänzende Repositoryinventare.
Sie sind weder aufgelöste PHP-/TYPO3-Abhängigkeitsgraphen noch reine
Archivdateiinventare. Die separaten Security-Jobs erstellen je Core-Major
einen tatsächlichen Produktionsgraphen mit Lock, Audit und CycloneDX-SBOM;
siehe :ref:`dependency-report-current`.

Eine gültige Signatur belegt weder TER-/Packagist-Veröffentlichung noch
ein freigegebenes Onlinehandbuch. Die Registries sind für 0.1.1 separat
bestätigt. Die Intercept-Handbücher warten auf externe Freigabe; ihre
EN-/DE-Quellen liegen bereits im Paket. Menschliches Security-Review,
Betreiberpilot und OpenSSF-Stufen werden nicht aus Signaturen abgeleitet.
