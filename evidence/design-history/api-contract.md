# HTTP Guard interface contract

Status: coordination freeze proposal; root confirmed G0 PASS before production implementation. Normative requirements remain the original 45 HG requirements and T001–T084; this document fixes only cross-module signatures and ownership. It does not satisfy any later gate.

Library source root: `outputs/http-guard-implementation/packages/http-guard/src`, PSR-4 `Netresearch\\HttpGuard\\`. Basic policy classes below use this flat namespace and one matching filename. Root owns `Transport/` and `Client/` with matching subnamespaces. TYPO3 and Vault keep their own package namespaces. PHP ^8.2. Production adapters are guarded to the demonstrated Guzzle 7.15.5/8.2.0 combinations, Promises 2.5.3/3.0.2 and PSR-7 2.13.1/3.1.0; broadening support requires new evidence.

## Application APIs (original section 04.3)

```php
OutboundPolicyEvaluatorInterface::evaluate(RequestInterface $request, RequestPolicyContext $context): PolicyDecision;
EndpointClientFactoryInterface::forEndpoint(string $configuredEndpointId): Psr\Http\Client\ClientInterface;
PublicFetchClientInterface::fetch(UriInterface $uri, string $method = 'GET'): ResponseInterface;
OutboundPolicyExceptionInterface::reasonCode(): string;
```

`PolicyException` extends RuntimeException and implements the policy exception interface plus PSR-18 ClientExceptionInterface. Fixed reason-only messages; no raw request/previous transport exception in normal telemetry. Diagnostic decisions are never transport tokens. Endpoint IDs are trusted DI composition, never user selectors. No application API exports a lease, driver, grant or credential-bearing Guzzle client. (HG-006, HG-017/018, HG-031/032/035/037.)

## Policy ownership: local_discovery

All these files are flat `src/<Name>.php`; policy tests live in `tests/Unit/Policy/`. Interfaces and final readonly DTOs are owned here; root consumes them without editing their definitions.

```php
ClockInterface::now(): DateTimeImmutable;
ClockInterface::monotonic(): float; // seconds, for elapsed TTL/budget measurements
GuardConfig::fromArray(array $data): GuardConfig;
TargetNormalizer::normalize(RequestInterface $request): Target;
AddressClassifier::classify(string $ip): AddressClassification;
AddressClassifier::contains(string $canonicalCidr, string $canonicalIp): bool;
DnsQueryInterface::query(string $absoluteFqdn, int $qtype): DnsAnswer;
ResolverInterface::resolve(string $canonicalHost): Resolution;
PolicyRegistry::__construct(GuardConfig $config, ClockInterface $clock);
PolicyRegistry::configuration(): GuardConfig;
PolicyRegistry::newContext(?string $endpointId = null): RequestPolicyContext;
PolicyRegistry::validateContext(RequestPolicyContext $context): ?EndpointProfile;
PolicyEngine::__construct(TargetNormalizer $normalizer, AddressClassifier $classifier,
    ResolverInterface $resolver, PolicyRegistry $registry, ClockInterface $clock,
    DecisionReporterInterface $reporter);
PolicyEngine::evaluate(RequestInterface $request, RequestPolicyContext $context): PolicyDecision;
PolicyEngine::hostAllowed(string $host, RequestPolicyContext $context): bool;
PolicyEngine::plan(RequestInterface $request, RequestPolicyContext $context): ConnectionPlan;
PolicyEngine::assertCurrent(ConnectionPlan $plan, RequestPolicyContext $context): void;
DecisionReporterInterface::report(DecisionEvent $event): void;
```

`SystemClock`, `StaticThenDnsResolver`, `WireDnsQuery`, `DecisionReporter` and a no-op reporter are concrete implementations. `StaticThenDnsResolver(GuardConfig $config, DnsQueryInterface $query, ClockInterface $clock)` is the resolver construction seam. `WireDnsQuery` uses validated numeric OS-configured nameservers; a constructor-injected numeric test server/port is only a test seam, never a production allow-all policy. `DecisionReporter` receives logger/HMAC key/clock through constructor injection, never reads framework globals.

| DTO | Readonly fields needed across modules |
|---|---|
| GuardConfig | `mode:string`, `revision:string`, `data:array` containing the fully validated original schema; creation only through fromArray |
| Target | `canonicalRequest:RequestInterface`, `scheme:string`, `host:string` without IPv6 URI brackets, `port:int`, `origin:string`, `literalIp:?string` |
| AddressClassification | `canonicalIp:string`, `addressClass:string`, `public:bool`, `endpointExceptable:bool`, `hardDenied:bool` |
| DnsAnswer | `records:array`, `source:string`, `complete:bool`; record shape `{host:string,type:string,ttl:int,ip?:string,ipv6?:string,target?:string}` |
| Resolution | `addresses:list<string>`, `source:string`, `ttlSeconds:?int`, `generation:string`, `cnameChain:list<string>` |
| EndpointProfile | `id:string`, `origin:string`, `allowedCidrs:list<string>`, `methods:list<string>`, `redirects:string`, `allowLoopback:bool`, `purpose:string`, `owner:string`, `reviewAfter:?DateTimeImmutable`, `expiresAt:?DateTimeImmutable` |
| RequestPolicyContext | `mode:string`, `policyRevision:string`, `clientScope:ClientScope`, `endpointGrant:?EndpointGrant` |
| ConnectionPlan | `target:Target`, `method:string`, `addresses:list<string>`, `profileId:?string`, `policyRevision:string`, `resolverGeneration:string`, `issuedAt:DateTimeImmutable` |
| PolicyDecision | `mode:string`, `decision:string` (allow/deny/would_deny/unverifiable), `reasonCode:?string`, `profileId:?string`, `policyRevision:string` |
| DecisionEvent | original section 04.5 fixed scalar metadata only: version/time/mode/decision/reason/profile/revision/addressClass/scheme/port/resolverSource/correlationId and optional approved host hash/plain value; no Target, request, query, body, headers, certificate path or exception object |

`ClientScope` and `EndpointGrant` are opaque registry-issued final objects, not caller-constructible/cloneable/serializable. `newContext()` creates a distinct owned scope per client and optionally its exact-profile grant. `validateContext()` validates registry ownership, scope, mode/revision, grant ownership/expiry and returns the profile or null for the Public profile. Configured profiles do not extend that Public profile. A connection plan is internal, engine-issued, single-attempt and scope-bound; `assertCurrent()` checks its exact issuance/context, revision and expiry without re-resolving DNS. Root calls it after DNS and body preparation, immediately before first network execution. `hostAllowed()` evaluates only the host dimension against this bound profile; it must not invent GET, scheme or port and cannot authorize a send. (HG-007–019, HG-025–027, HG-038/043/044.)

DNS backend contract: exact absolute FQDN, supported qtypes A=1/AAAA=28/CNAME=5; validate response ID/question/rcode, compression bounds and all relevant records. UDP TC requires complete length-prefixed TCP retry. No incomplete response becomes a positive Resolution; native dns_get_record alone cannot claim completeness. Resolver validates owner names/CNAME chain, rejects conflicting aliases/cycles/limits and classifies every A/AAAA candidate before any family preference. TTL is the minimum usable chain/record TTL; TTL0 and negative answers are not cached. Static records remain fully classified. No search/NSS/mDNS/transport fallback. Mapped-v6 candidates become their embedded canonical IPv4 for both classification and CIDR matching; v1 configuration rejects mapped-v6 CIDRs rather than inventing ambiguous cross-family masks. (HG-010–016, HG-027/044; T016/T022–033.)

## Transport and client ownership: root

```php
Transport\TransferDriverInterface::tick(): void;
Client\ClientStackProviderInterface::create(Transport\BoundaryMiddleware $boundary, Transport\TerminalGuardMiddleware $terminal): ClientStackConfiguration;
Client\GuardedClientFactory::__construct(PolicyEngine $engine, GuardConfig $config,
    PolicyRegistry $registry, ?ClientStackProviderInterface $stackProvider = null);
Client\GuardedClientFactory::createTransport(array $defaultOptions = [], ?string $endpointId = null): GuardedClientBinding;
Client\GuardedClientFactory::middlewarePair(?string $endpointId = null, bool $publicFetch = false): MiddlewarePair;
Client\GuardedClientFactory::forEndpoint(string $configuredEndpointId): Psr\Http\Client\ClientInterface;
Client\GuardedClientFactory::publicClient(): Psr\Http\Client\ClientInterface;
Client\GuardedClientFactory::publicFetch(): PublicFetchClientInterface;
```

`ClientStackConfiguration` is readonly `{stack:HandlerStack, defaultOptions:array, registryAssertion:?Closure = null}`; the assertion rechecks the effective registry at request time. `GuardedClientBinding` is internal readonly `{client:GuzzleHttp\\Client, driver:Transport\\TransferDriverInterface, context:RequestPolicyContext}`. `MiddlewarePair` is internal readonly `{boundary:Transport\\BoundaryMiddleware, terminal:Transport\\TerminalGuardMiddleware, driver:Transport\\TransferDriverInterface, context:RequestPolicyContext}`; it permits TYPO3 registration before a CoreStackProvider exists. Both middleware classes expose internal `setRegistryAssertion(?Closure):void`; createTransport creates its pair, obtains the provider stack, then installs this assertion. Bindings are credential-free and kept inseparable; Vault adapts the driver internally to TransportTickerInterface. No loose old-driver/new-client combination. Explicit default options are validated and merged without weakening provider/Core defaults. The factory's config must be the registry/engine snapshot. Each createTransport call owns a fresh client scope; individual attempts still own separate CurlMultiHandlers. assertCurrent validates repeatedly without consuming a plan on its first pre-start assertion.

Root owns `BoundaryMiddleware`, `TerminalGuardMiddleware`, `RequestEnvelope`, invocation token registry, `GuardedTransferFactory`, `TransferLease`, `TransferDriver`, version/options adapter and these client classes/interfaces. Exact middleware shape: `__invoke(callable $next): callable`, returned callable `(RequestInterface $request, array $options): PromiseInterface`. Controlled leaf, lease and envelope construction stay internal; applications cannot submit plans. Enforce uses only the controlled leaf; observe/disabled delegate original request/options as specified in api-design.md.

Only internal identity-owned object options transport binding, proposed keys `nr_http_guard_context`, `nr_http_guard_envelope`, `nr_http_guard_invocation`; any grant option must match the exact context scope. Unknown options/forged or swapped bindings fail. Boundary envelope lives for an invocation; downstream retries get fresh terminal attempts. Regular redirects enter fresh invocations. Preserve final post-custom-response Location checks and outer redirect limit semantics. Factory directly generates `_curl_retries=2` in locked majors and rejects caller internal retry/routing keys. Cancellation before network execution, active-socket termination, callbacks, sink/streaming owner and teardown all require evidence. (HG-002/005/020–026/028–030/040/042; T003/038/041/048/052–061/075/083/084.)

## TYPO3 ownership: integration_research

Package namespace `Netresearch\\NrHttpGuard\\`. Own registration, Services wiring, Config loader, CoreStackProvider and CLI/diagnostics. Framework globals are read only here. Default standalone provider creates standard Guzzle defaults; TYPO3's provider has a trusted fixed Core context in its constructor, not a per-request user selector.

`CoreStackProvider::create($boundary,$terminal)` uses the actual Core GuzzleClientFactory client/config for that fixed context. Validate effective HTTP.handler array/count/order/identities; clone its actual HandlerStack. Using public before/remove APIs, replace only the two validated named own wrappers at their exact positions; retain defaults, Core AllowedHosts, every other middleware and resolved version-specific config (including Guzzle8 PSR factories). Return ClientStackConfiguration with this cloned stack and full Core defaults. No manually approximated Core stack and no caller-chosen leaf. Normal RequestFactory uses the global registered wrappers. (HG-001/002/019/034/035/038/042.)

## Vault ownership: plan_validator after root-approved freeze

Pinned source: `work/nr-vault`, commit `5a070c396a614e5b05f63d79fa564c3748cf21eb`. New files under `Classes/Http/Guard/` use `Netresearch\\NrVault\\Http\\Guard\\`. Existing public send/fluent signatures stay unchanged; existing factory method signatures stay unchanged. The factory constructor gains only a trailing optional local adapter defaulting to null; this is an additive constructor seam, not a claim that its reflected signature is identical. No global opt-in or mandatory library dependency in the legacy branch.

```php
VaultGuardAdapterInterface::create(array $platformOptions): Psr\Http\Client\ClientInterface;
VaultGuardAdapterInterface::createCancellable(array $platformOptions, float $wallClockBudgetSeconds,
    ?float $idleBudgetSeconds): ?CancellableTransport;
VaultGuardAdapterInterface::mode(): string;
VaultGuardAdapterInterface::isHostAllowed(string $host): bool;
VaultGuardAdapterInterface::assertRequestAllowed(RequestInterface $request): void;
VaultGuardAdapterInterface::tokenFactory(): ?SecureHttpClientFactory;
VaultGuardAdapter::__construct(Client\GuardedClientFactory $clientFactory,
    PolicyEngine $engine, ?string $resourceEndpointId = null,
    ?SecureHttpClientFactory $oauthTokenFactory = null,
    ?SecureHttpClientFactory $legacyFactory = null);
```

VaultGuardAdapter binds resource profile only by trusted constructor composition. A separately constructed token SecureHttpClientFactory carries its own adapter/profile; neither selects a profile by URL. A configured null endpoint explicitly means Public policy. In enforce, missing or non-enforced token factory refuses OAuth before credentials; createCancellable is non-null or throws and never reaches a legacy fallback. Enforce passes validated platform defaults/timeout overrides through createTransport and keeps its matching client+driver/context, adapting the driver through GuardTransferTicker. In observe/disabled the adapter uses a separate original SecureHttpClientFactory without an adapter, clones its actual stack while retaining its exact leaf, and preserves the legacy ticker, allowlist and SSRF controls. Observe adds evaluation-only diagnostic middleware; disabled adds none. Non-enforce missing token factory uses that original legacy factory and is explicitly not protected token coverage. Nullable cancellation is allowed only for the original non-enforce curl-less fallback. Internal SecureHttpClientFactory::guardMode() reports the selection; withDiagnosticOptions() remains the sole local Guzzle construction helper and preserves the legacy stack/leaf. Keep existing wall/idle budgets and all Vault stream machinery. Preflight uses evaluation only; actual enforce terminal start always plans anew.

Actual source delegation locations:

| Source location | Required local change after freeze |
|---|---|
| SecureHttpClientFactory constructor :133, create :150, createCancellable :202, isHostAllowed :261 | Optional adapter; branch before legacy capability fallback; retain legacy buildOptions/defaults/semantics when adapter null. Add internal full-request preflight/token-factory accessors; host method remains host-only. |
| VaultHttpClient constructor :317–373 | Current code eagerly constructs OAuthTokenManager with resource innerClient/factory. Guarded branch must initialize its manager lazily from the separate configured token factory; plain resource use does not require a token factory. Legacy initialization remains unchanged. |
| VaultHttpClient withAuthentication/withOAuth/withReason :376–439, withTimeout :440–491 | Forward the same existing OAuth manager/cache once created. withTimeout rebuilds only resource client and drops stale cancellable transport; token client retains platform-default timeout. Reuse existing typed clonedFrom identity seam to distinguish trusted clones from arbitrary injected clients/managers in protected mode. |
| VaultHttpClient assertHostIsAllowed :733 and injectOAuthAuthentication :1589 | Preserve pre-secret/audit host check; add opted-in full-request preflight before credential injection; lazily obtain the correctly bound token manager. |
| OAuthTokenManager constructor :177, dispatchTokenRequest :563, dispatchCancellable :639 | Pass the separate token client and token factory; preflight POST endpoint before serializing client_secret; cancellation uses that token factory's separate client+driver. No resource grant transfer or fallback. |
| VaultHttpClientFactory :40 | Existing factory-built provenance remains; no raw inner client injected merely to opt in. |

Unverified caller-injected clients/managers/transports remain legacy-compatible by default. In explicitly protected mode, reject them unless their exact factory/clone provenance is verified; never claim guard coverage for a caller-replaced transport. Audit webhook is separately inventoried/bound. Tests own `Tests/Unit/Http/Guard/`, focused changes to HTTP suites, and `Tests/Functional/Http/` wire regressions; required Docker wrapper commands remain the baseline contract. (HG-029/030/036–038/043; T059–061/069–073/076.)

## Ownership and implementation acceptance

| Owner | Exclusive edit scope |
|---|---|
| local_discovery | Flat library policy/config/DNS/clock/exception/reporter/public-interface files listed above; tests/Unit/Policy; versioned corpus data |
| root | packages/http-guard/src/Transport, packages/http-guard/src/Client and matching tests; library package manifests/bootstrap, full evidence/gate integration |
| integration_research | Separate TYPO3 package source/config/tests/docs; does not change shared policy/transport definitions without coordination |
| plan_validator | Exact Vault checkout, adapter and localized delegation/regression changes; separate Vault evidence |

Before accepting implementations, verify fresh plans for actual retry middleware, expiry/cancellation after DNS/body preparation, envelope swaps/replay/concurrency, mutation-sensitive hidden-retry suppression, disabled/observe transparency, complete DNS and no resolver fallback, Core-context preservation, POST-only profiles, separate OAuth binding/cache/timeouts, active socket termination and callback/partial-stream cleanup. Use transport spy plus synthetic wire counters where required. A signature freeze does not replace runtime, P0, mutation, dependency or human review gates.
