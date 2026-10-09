.. _decisions:

=======================
Architecture decisions
=======================

The original plan separated a framework-independent HTTP Guard core and
the TYPO3 adapter into two installable packages. The explicit requirement
for classic TYPO3 and TER installation led to the following revised
packaging decision. The original ADRs in the additional evidence package
remain unchanged.

.. toctree::
    :maxdepth: 1

    SingleExtension
