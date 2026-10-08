# AP-01/G0 actual integration proof

G0 passed and root confirmed the gate on 2026-10-08. This is evidence for the selected integration and transport path. It does not claim the production policy, complete original security corpus, dependency security gate or operator acceptance is complete.

## Recorded matrix

| TYPO3 Core | Guzzle | Promises | PSR-7 | Normal | Object registry | Duplicate registration |
|---|---|---|---|---|---|---|
| 13.4.35 | 7.15.5 | 2.5.3 | 2.13.1 | PASS, 35 checks | PASS | PASS |
| 13.4.35 | 8.2.0 | 3.0.2 | 3.1.0 | PASS, 35 checks | PASS | PASS |
| 14.3.7 | 7.15.5 | 2.5.3 | 2.13.1 | PASS, 35 checks | PASS | PASS |
| 14.3.7 | 8.2.0 | 3.0.2 | 3.1.0 | PASS, 35 checks | PASS | PASS |

The 12 actual runs produced 148 passing assertions. Each normal run performed ten owned native attempts, released ten, reached four overlapping attempts and ended with zero open native resources. Final records are `evidence/core{13,14}g{7,8}-{normal,object,duplicate}-final.json`; matching `.stderr` files are empty. `evidence/G0-summary.json` summarizes the recorded results; `evidence/source-and-lock-hashes.txt` binds final source and locks.

Runtime: PHP 8.5.11, libcurl 8.5.0, Ubuntu 24.04.5 LTS on Linux 6.18.33.2-microsoft-standard-WSL2, Docker 29.8.2. The runtime fixtures use genuine Composer-installed cms-core/backend/frontend/fluid packages, genuine extension discovery and dependency injection. The runner invokes `SystemEnvironmentBuilder::run` and actual `Bootstrap::init($loader)`, obtains the real container service `TYPO3\CMS\Core\Http\RequestFactory`, and sends requests through it. There is no substituted Core factory or simulated bootstrap.

The locked Core commits are 13.4.35 `71da7c83e245d73688a540ee3c5a5f29c04a0a5e` and 14.3.7 `18071e452396230c34e23554298591ada6dc4c4a`. Guzzle commits are 7.15.5 `ee80339fd9177ba44c49cdb653ff02a4d1106b9a` and 8.2.0 `93939470950a9b11e2e84204166ef5e048c55fe4`. All transitives are preserved in each fixture `composer.lock`.

## What the assertions establish

The documented `BootCompletedEvent` listener registers the boundary as the first custom middleware and terminal as the last. Normal bootstrap dispatches it once. A global HandlerStack object or pre-existing duplicate guard causes actual bootstrap to fail before a target request. Request-time checks likewise reject an entry after terminal or an added duplicate guard before a request reaches either instrumented server.

Actual request trace is boundary → project A → project B → terminal. Actual response trace is terminal → project B → project A → boundary, with native header/stat callbacks preceding the response middleware. Core's context allowlist rejects a denied host before the boundary. A project middleware host rewrite is rejected by terminal before server contact. Stream fallback, foreign raw cURL options, nonzero delay and an unknown option are rejected before server contact.

A same-origin redirect enters both guards for each hop, creates a new isolated multi and sends the second hop to its own pin. A project response middleware can change an initial 200 into a forbidden-origin 302; the outer custom boundary rejects that final response before Core follows it. This proves response authority checks run at the required position.

Four async requests share `guard.test:8080` but select pins A/B/A/B. They have four distinct live CurlMultiHandler objects. Each server records exactly its two intended slow paths; A never records `/slow-b`, B never records `/slow-a`. Actual primary IPs match the respective pins. Per-attempt options include a single generated CURLOPT_RESOLVE pin, FRESH_CONNECT and FORBID_REUSE, with explicit direct proxy and `transport_sharing=none`.

The separate wire targets are containers `http-guard-g0-wire-a` (`203.0.114.100:8080`) and `http-guard-g0-wire-b` (`203.0.114.101:8080`) on internal network `http-guard-g0-probe` (`203.0.114.0/24`). These are isolated synthetic addresses in global-unicast form; the probe does not add a public-profile exception for loopback. The network is internal; targets are local test servers. The Python image is pinned to `python@sha256:f85c5697265c178cc6887276c55fe16cf3d14ca35c3df6a5eab3b360534a55d2`. Independent application request counters prove routing here; this probe has no TCP accept or TLS counters and does not claim those production tests.

## Teardown distinction and adapter requirements

Dropping the owner reference is insufficient when exceptions or promise closures retain the CurlMultiHandler. The initial G7 failure is preserved in `evidence/core14g7-first.json`: its native `_mh` still existed. G8 retained objects were already closed, as preserved in `evidence/core14g8-lifetime-diagnosis.json`.

The final path creates a private `CurlFactory(0)` for each attempt. Settlement, failure and cancellation invoke one idempotent owner release: G8 `CurlMultiHandler::close()`, G7 public `CurlMultiHandler::__destruct()`. The locked G7 implementation closes and unsets `_mh`; an eventual normal destructor call is idempotent. Production needs a tested major/version adapter for this distinction. A general assumption that an arbitrary G7 handler offers `close()` would be incorrect.

Every teardown assertion reports active handle count, factory idle handle count and native multi resource state using Reflection read-only inspection. Two PHP object references can remain after exception-related cases, while all native resources are closed: G7 `_mh` is unset/null and both handle counts zero; G8 `closed=true`, `multiHandle=null` and both counts zero. Final reports retain those live-reference counts and do not label them destroyed. Explicit closure succeeds after ordinary sync requests, redirects, response mutation denial, parallel settlement, cancellation and a caller header callback failure.

## Callback and stack interfaces useful for production

Middleware factories use `__invoke(callable $next): callable`, returning a handler with `(Psr\Http\Message\RequestInterface $request, array $options): GuzzleHttp\Promise\PromiseInterface`. The terminal owns its native leaf. It must never authorize an arbitrary caller-supplied replacement leaf.

G7 `on_headers` receives `(ResponseInterface)`; G8 receives `(ResponseInterface, RequestInterface)`. The adapter forwards all actual native arguments. The final callback assertions verify argument count, response type/status, and G8 request host. `on_stats` receives `GuzzleHttp\TransferStats`; the caller receives the original context with the actual primary IP. `progress` receives four integer counters; source inspection establishes the signature, but G0 does not claim its cancellation or return-value semantics are fully tested. Those remain production transport tests.

The internal fixed-context stack provider can obtain the actual Core-created client configuration, clone its HandlerStack and replace only the two named guard entries using public `before` and `remove` methods. Both majors expose those APIs and `Client::getConfig`; mutations invalidate the resolved stack cache. Keep Core's context allowlist and all preceding default middleware, other project middleware and G8 PSR factory defaults. Validate the global middleware array before cloning: public named insertion methods alone do not prove identity, uniqueness or boundary/terminal placement. No public caller leaf or context string is required.

## Reproduction

Source/config lives in this directory. Native executable fixture dependencies are disposable under `../native-runtime/probe`; the final manifests and locks in `core13g7`, `core13g8`, `core14g7`, `core14g8` match what was installed. Use a native Linux filesystem for extracted dependencies to avoid mounted-Windows extraction delays.

```sh
bash work/probe/tools/prepare-runtime.sh /tmp/http-guard-g0-runtime
bash work/probe/tools/prepare-wire.sh
python3 work/probe/tools/run-matrix.py --runtime /tmp/http-guard-g0-runtime --evidence work/probe/replay-evidence
```

The path repository in the recorded fixture manifests points to this workspace's `work/probe/extension`. A relocated checkout must update that path and intentionally regenerate its fixture lock; the recorded lock hashes then differ and must be reported. The configs are existing AST-written PHP files, linked into each native fixture.

The wire containers and network remain running at root's request for later local tests. The prepare script does not replace existing named resources; inspect them before interpreting a replay. To clean up after root no longer needs them, remove those two containers then the named network.

## Deliberate limits

These disposable fixtures contain scoped Composer advisory ignores for three inherited enshrined/svg-sanitize ~0.22 advisory IDs. No SVG processing occurs. The original failed install logs and final native logs are retained per fixture. These exceptions prove an executable integration fixture only; they must never be copied into production package policy or used as a passed dependency security gate.

The probe hardcodes controlled synthetic routing after origin/options checks. It implements no full DNS resolver, endpoint grant registry, address classification, TLS/mTLS security acceptance, streaming contract, audited telemetry or complete T001–T084 security policy. Root's separate production implementation must supply and test those. Calls before `BootCompletedEvent` registration and G7 request-level handler override paths lie outside global middleware coverage and require explicit documented handling.

Official source links and further transport/version pitfalls are in `source-research.md`.
