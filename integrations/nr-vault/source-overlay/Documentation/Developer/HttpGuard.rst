.. SPDX-License-Identifier: GPL-2.0-or-later
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

.. include:: /Includes.rst.txt

.. _developer-http-guard:

========================
Opt-in HTTP Guard adapter
========================

The optional ``netresearch/http-guard`` library supplies policy-bound
transports for explicitly configured Vault clients. Installing the library or
the TYPO3 HTTP Guard extension does not select this adapter. The default
:php:`SecureHttpClientFactory` continues to use Vault's existing host allowlist,
SSRF middleware and DNS pinning.

.. _developer-http-guard-bindings:

Resource and token bindings
===========================

Create a named resource factory and a separate OAuth token factory. Each adapter
binds one configured endpoint profile; a null endpoint ID selects the public
profile. A resource grant never authorizes its token endpoint. In enforce mode,
an OAuth resource client requires a separately enforced token factory, and
rejects an absent or unprotected token factory before reading credentials.

The following example uses the fixed-context factory provided by
``netresearch/nr-http-guard``. Define ``erp-resource`` and ``oauth-token`` in its
HTTP Guard configuration with their exact origins, methods, CIDRs, owner,
purpose and, where required, expiry. The fixed Core context and its AllowedHosts
rules apply as well.

.. code-block:: yaml
   :caption: EXT:my_extension/Configuration/Services.yaml

   services:
     _defaults:
       autowire: true
       autoconfigure: true
       public: false

     app.vault.token_adapter:
       class: Netresearch\NrVault\Http\Guard\VaultGuardAdapter
       arguments:
         $clientFactory: '@nr_http_guard.context_factory'
         $engine: '@Netresearch\HttpGuard\PolicyEngine'
         $resourceEndpointId: 'oauth-token'

     app.vault.token_factory:
       class: Netresearch\NrVault\Http\SecureHttpClientFactory
       arguments:
         $guardAdapter: '@app.vault.token_adapter'

     app.vault.resource_adapter:
       class: Netresearch\NrVault\Http\Guard\VaultGuardAdapter
       arguments:
         $clientFactory: '@nr_http_guard.context_factory'
         $engine: '@Netresearch\HttpGuard\PolicyEngine'
         $resourceEndpointId: 'erp-resource'
         $oauthTokenFactory: '@app.vault.token_factory'

     app.vault.resource_factory:
       class: Netresearch\NrVault\Http\SecureHttpClientFactory
       arguments:
         $guardAdapter: '@app.vault.resource_adapter'

     app.vault.client_factory:
       class: Netresearch\NrVault\Http\VaultHttpClientFactory
       arguments:
         $secureHttpClientFactory: '@app.vault.resource_factory'

Inject ``app.vault.client_factory`` into the consuming service and call
``create($vaultService)``. The returned client supports the existing
``withAuthentication()``, ``withOAuth()``, ``withTimeout()`` and ``withReason()``
methods. Trusted clones retain the token cache. The adapter does not accept an
arbitrary inner client, OAuth manager or transport in enforce mode.

Keep bindings local to each integration. Replacing Vault's global default
factory could also change transports used by audit webhooks and other consumers;
inventory and bind those independently.

.. _developer-http-guard-modes:

Modes and failures
==================

In ``enforce``, a full request preflight precedes secret retrieval and the
terminal transport checks the final request again immediately before contact.
Raw OAuth endpoint strings are checked before PSR parsing, so a discarded
fragment or encoded backslash cannot bypass the preflight. The guarded client's
driver supplies cancellation and streaming. Unsupported
transport capabilities or a refused policy throw; there is no legacy fallback.
Vault's timeouts, cancellation signals, authentication placements and audit
semantics continue to apply.

In ``observe``, the adapter retains the actual legacy handler stack, host
allowlist, SSRF middleware, DNS pin, ticker and budgets. HTTP Guard evaluates a
diagnostic request without rewriting or authorizing the legacy request. In
``disabled``, no HTTP Guard policy evaluation occurs and the same legacy controls
remain in place. A grant cannot widen the legacy allowlist in either mode.

If no explicit OAuth token factory is supplied in observe or disabled mode, the
adapter uses a fresh legacy factory for the token leg. That leg retains Vault's
old checks but has no HTTP Guard policy coverage. Configure the separate token
adapter explicitly when token diagnostics or enforcement are required.

For a custom legacy DNS resolver or handler, explicitly pass the same configured
original ``SecureHttpClientFactory`` as the adapter's ``$legacyFactory``. The
resolver supplied to the outer opted-in factory is not propagated through the
adapter. The default adapter creates a fresh original factory with Vault's
normal default resolver. For example, extend the bindings above with:

.. code-block:: yaml

   app.vault.original_factory:
     class: Netresearch\NrVault\Http\SecureHttpClientFactory
     arguments:
       $dnsResolver: '@app.integration_dns_resolver'

   app.vault.resource_adapter:
     class: Netresearch\NrVault\Http\Guard\VaultGuardAdapter
     arguments:
       $clientFactory: '@nr_http_guard.context_factory'
       $engine: '@Netresearch\HttpGuard\PolicyEngine'
       $resourceEndpointId: 'erp-resource'
       $oauthTokenFactory: '@app.vault.token_factory'
       $legacyFactory: '@app.vault.original_factory'

The original factory must have no adapter. Use equivalent explicit composition
for the token adapter when it needs custom legacy behavior. The observe and
disabled wire regressions exercise this composition with an injected resolver.

Policy failures expose a reason code; audit rows and fixture counters do not
contain credential values. Vault secrets are not passed into endpoint profiles
or HTTP Guard telemetry.

.. _developer-http-guard-testing:

Validation and compatibility
============================

The adapter regression suite is
:file:`Tests/Functional/Http/GuardAdapterTest.php`, run through the repository's
container test wrapper. It tests real wire contacts, separate token and resource
bindings, pre-secret refusals, authentication placements, audit redaction, expiry,
token caching, all three modes, streaming and cancellation.

For source-based integration validation, copy the exact shared library source
and data and :file:`tests/Integration/` to :file:`.Build/http-guard-library/` and retain a file digest manifest.
The test bootstrap loads that optional source only when present. A normal Vault
checkout without the optional library may skip these adapter tests; a run used
as HTTP Guard acceptance evidence must have the library and network fixture
installed and must contain no skipped adapter cases. Run the existing unit,
fuzz, functional, architecture and static suites as well.

:file:`Build/Scripts/runGuardAdapterTests.sh` wraps the required functional
runner and attaches only its owned job to two isolated synthetic fixture
networks. It idempotently prepares the shared target counters and tests the public-resource/private-token pair and its inverse at
``203.0.115.150`` and ``10.23.4.150``. These are Docker NICs within the fixture,
not external services. The script requires the optional library source and integration-fixture copy,
Docker, Bash, OpenSSL and ripgrep. It also consumes the shared
``EP-PRIVATE-UNBOUND`` corpus case by ID and SHA-256 and verifies that a legacy
allowlist entry cannot authorize an unbound private endpoint, before secrets,
native handles, TCP contact or HTTP contact.

Supported PHP, Guzzle and cURL versions are those accepted by the shared
library's runtime checks and the recorded execution matrix. Local synthetic
tests do not replace an operator's production pilot or independent release
acceptance. See :ref:`adr-041-opt-in-http-guard-adapter`.
