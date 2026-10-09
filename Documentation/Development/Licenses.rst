.. _licenses:

==================
Lizenzen und Daten
==================

.. _licenses-extension:

Extension und Sicherheitskern
============================

Die eine TYPO3-Extension :literal:`netresearch/nr-http-guard` wird unter
**GPL-2.0-or-later** geliefert. Der vollständige GPLv2-Text befindet sich in
:file:`LICENSE.txt` auf der Extension-Ebene.

Der enthaltene Sicherheitskern unter :file:`Classes/HttpGuard/` wurde unter
MIT erstellt. Seine Lizenz- und Copyright-Hinweise bleiben erhalten;
der vollständige Text liegt unter :file:`LICENSE-HttpGuard.txt`. Die
Zusammenführung erzeugt kein zweites veröffentlichtes Paket und behauptet
keine Umlizenzierung des Kerns. :file:`LICENSES.md` beschreibt die
Zuordnung im Paket.

Drittanbieter-Laufzeitbibliotheken werden nicht nochmals in einem eigenen
Vendor-Verzeichnis gebündelt. Die unterstützte TYPO3-Installation stellt
diese Komponenten bereit; ihre jeweiligen Lizenzhinweise gelten weiter.
Der optionale Patch für nr-vault gehört zum bestehenden GPL-Projekt nr-vault.

.. _licenses-sources:

Adressdaten und Quellen
======================

Die mitgelieferten Daten unter
:file:`Resources/Private/HttpGuard/data/security-corpus/` enthalten die
abgeleiteten Regeln, den binären Testcorpus und eine Quellenmanifestdatei.
Sie trennt Abrufdatum, Registerstand, Hash des heruntergeladenen Originals
und Hash der abgeleiteten Regeln. Die primären Register sind die
`IANA IPv4 Special-Purpose Address Registry
<https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml>`_
und die
`IANA IPv6 Special-Purpose Address Registry
<https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml>`_.

Zusätzliche Metadaten-Sperren sind in denselben Quellenmetadaten begründet.
Die jeweilige Quelllizenz und Rechte an aufgezeichnetem Drittmaterial
bleiben bestehen. Originale Spezifikationen und ADRs werden im optionalen
Nachweispaket unverändert aufbewahrt; dieses Handbuch übernimmt keine
nachträgliche Änderung ihrer ursprünglichen Aussagen.

Adressdaten werden mit der Extension versioniert. Ein Regelupdate benötigt
eine neue Freigabe mit Corpus-, CIDR- und tatsächlichen Wire-Prüfungen sowie
einem Austausch der betroffenen Policy-Snapshots.
