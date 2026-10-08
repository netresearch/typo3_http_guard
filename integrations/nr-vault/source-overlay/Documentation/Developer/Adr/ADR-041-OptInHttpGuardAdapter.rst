.. SPDX-License-Identifier: GPL-2.0-or-later
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

.. include:: /Includes.rst.txt

.. _adr-041-opt-in-http-guard-adapter:

==================================
ADR-041: Opt-in HTTP Guard adapter
==================================

.. _adr-041-status:

Status
======

Accepted (implementation; production release acceptance remains external).

.. _adr-041-context:

Context
=======

Vault already owns authentication, OAuth token caching, auditing, cancellation
and streaming. Its default secure factory owns an allowlist, SSRF middleware and
DNS pinning. The shared HTTP Guard library adds policy-bound terminal transports
with explicit internal endpoint profiles. Opting a resource client into that
transport must not grant its OAuth token leg the resource's authority or silently
replace defaults for unrelated Vault consumers.

.. _adr-041-decision:

Decision
========

Add a trailing optional adapter to :php:`SecureHttpClientFactory`. Its default
is null and service discovery excludes the optional concrete adapter classes.
The adapter is an internal integration seam and the shared library remains a
Composer suggestion. Applications select named resource and token factories.

Enforce mode constructs both the client and its ticker from one guarded binding.
Policy and capability failures propagate. Resource and token preflights precede
secret reads, while the terminal handler independently checks the final request.
An enforced OAuth resource client rejects a token factory that is absent or
not enforced. Arbitrary supplied clients, token managers and transports cannot
bypass this binding. Trusted immutable clones share a mutable internal token
cache holder and retain existing caller-facing APIs.

Observe and disabled modes retain the complete legacy transport and ticker.
Observe adds diagnostics only; disabled delegates unchanged. An absent token
factory in those modes selects a fresh legacy factory and is explicitly outside
HTTP Guard coverage. Endpoint profiles never widen legacy controls.

.. _adr-041-consequences:

Consequences
============

The default Vault client and its public calling interfaces keep their behavior.
The API snapshot adds the internal adapter seam and the types referenced by it;
existing frozen signatures are unchanged. Integrators must bind each protected
resource and token endpoint deliberately and inventory audit webhook factories
separately. Tests use the real shared transport and owned synthetic wire servers,
with source digests and dependency versions recorded. Operator pilot evidence
and independent human acceptance remain required for production release.

Configuration and mode details: :ref:`developer-http-guard`.
