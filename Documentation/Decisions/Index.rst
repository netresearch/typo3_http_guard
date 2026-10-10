.. _decisions:

=======================
Architecture decisions
=======================

The original plan separated a framework-independent HTTP Guard core and
the TYPO3 adapter into two installable packages. The explicit requirement
for classic TYPO3 and TER installation led to the following revised
packaging decision. The `original proposed ADRs
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/README.md>`_
remain unchanged in Git history; they are not a second current manual.

.. toctree::
    :maxdepth: 1

    SingleExtension
