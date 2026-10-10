.. _decisions:

===========================
Architekturentscheidungen
===========================

Die ursprüngliche Planung trennte einen frameworkunabhängigen
HTTP-Guard-Kern und den TYPO3-Adapter in zwei installierbare Pakete.
Die ausdrücklich gewünschte klassische TYPO3-/TER-Installation führt zu
der folgenden aktualisierten Verpackungsentscheidung. Die `ursprünglichen
vorgeschlagenen ADRs
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/README.md>`_
bleiben in der Git-Historie unverändert; sie bilden kein zweites
aktuelles Handbuch.

.. toctree::
    :maxdepth: 1

    SingleExtension
