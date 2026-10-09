# nr-http-guard

This unreleased TYPO3 extension registers the shared HTTP Guard in the real Core outbound Guzzle middleware stack. New installations use `enforce` with no internal endpoint grants. The currently verified candidate combinations are TYPO3 13.4.35 or 14.3.7 with Guzzle 7.15.5 or 8.2.0, PHP 8.5.11 and libcurl 8.5.0. Composer constrains the two exact Core patches; a wider compatibility statement needs new runtime evidence. The legacy `ext_emconf.php` interval is metadata for extension discovery and cannot express this disjoint Composer constraint.

## Installation and configuration

Install both local packages through Composer, mapping their path repository versions to `dev-main`. Deploy policy only under `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']`. The schema, endpoint ownership fields, absolute denied CIDRs, resolver bounds, redirects and telemetry are validated by `Netresearch\HttpGuard\GuardConfig`; unknown fields and wrong types fail closed. The original specification is authoritative for the full schema.

The `BootCompletedEvent` listener registers `nr/http-guard-boundary` first and `nr/http-guard-terminal` last in `HTTP.handler`. Keep other outbound middleware between them. A HandlerStack object, an existing duplicate guard, a later entry after terminal or replaced guard identity is an explicit configuration error. Clear both TYPO3's Core system cache and dependency injection cache when deploying package or service wiring changes; stale compiled service definitions are not evidence of the new wiring. Policy changes activate through deployment, cache rebuild and controlled restart of long-lived workers. Existing clients retain their immutable policy snapshot until replaced.

A narrow service decorator intercepts the actual Core `RequestFactory::request()` raw string before Guzzle constructs its URI. It rejects forbidden raw syntax in `enforce`, including an empty fragment marker or backslash that a URI constructor would erase or encode. In `observe`, it records a fixed `would_deny` reason and delegates unchanged. The chosen decorator matches the exact parent signature and Core 14's readonly class. The original Core factory still performs the request. Conflicting factory replacement or changed runtime signatures fail closed. The DI, `GeneralUtility` and PSR-17 factory references resolve to the same decorator; relative PSR-17 request construction retains Core behavior.

Ordinary Core `RequestFactory` requests use the Public profile. Declaring an endpoint or a static hostname alone never grants those requests permission to reach private addresses. For trusted service wiring, inject `Netresearch\HttpGuard\EndpointClientFactoryInterface` and bind the configured endpoint ID during composition, or inject the resulting PSR-18 client into the integration. Endpoint IDs must not come from a user-controlled selector. PSR-18 `sendRequest()` retains its no-follow behavior.

`Netresearch\HttpGuard\PublicFetchClientInterface` exposes only GET/HEAD and creates a separate request with its known header set. It drops inherited auth, cookies, mTLS, bodies and sensitive default headers. The fixed Core context for both explicit factories is `nr_http_guard`, injected into `CoreStackProvider` in `Configuration/Services.yaml`; operators can bind a different fixed context through trusted service configuration. Core `HTTP.allowed_hosts[context]` remains cumulative with the new policy. Each client is built from the actual Core-created configuration and a clone of its stack; all existing default and project middleware remain in their original positions.

Default Core redirects are normalized before client construction to the smaller of Core's default five and the operator policy limit. Explicit differently configured limits are retained for validation. An explicit request limit above policy fails before sending. `observe` and `disabled` retain the original Core leaf and options, including legacy transport behavior, and are visibly unprotected.

## Operator commands

```text
vendor/bin/typo3 http-guard:doctor
vendor/bin/typo3 http-guard:config-check
vendor/bin/typo3 http-guard:policy-check https://example.org --no-dns
vendor/bin/typo3 http-guard:policy-check https://erp.example.org --endpoint=erp-orders
vendor/bin/typo3 http-guard:legacy-report
```

Doctor, config-check and legacy-report send no target HTTP. Policy-check can resolve DNS, and `--no-dns` restricts it to numeric targets and configured static hosts. It never sends HTTP or returns a reusable send capability. Output contains fixed metadata and reason codes; URL userinfo, credentials and raw configuration are not printed. Proxy variable names/presence are reported without values, and an incoming `$_SERVER['HTTP_PROXY']` value is distinguished from trusted process environment. Doctor uses the shared runtime capability check, including curl-multi functions and the exact dependency families. Doctor and config-check warn about endpoint IDs whose `reviewAfter` date has passed without automatically denying those profiles. System DNS configuration is read only when a DNS query is actually needed.

Exit codes are 0 for success/allow, 2 for policy denial or an unprotected doctor mode, 3 for configuration/capability errors and 4 for an unverifiable resolution. Exactly these four primary CLI commands can finish startup after a caught configuration/capability PolicyException: the listener records a fixed reason and installs a first denial middleware, so no HTTP fallback becomes available. Ordinary startup still throws. Lazy service closures keep configuration errors inside this diagnostic path.

Legacy report counts flat Vault entries and nested Core context lists separately, lists the endpoint fields still needed and creates zero grants. It prints no legacy host values and changes no policy.

## Verification

The production integration was executed in four genuine Composer-installed TYPO3 fixtures. Each cell passed 35 wire assertions using actual `Bootstrap::init`, actual Core `RequestFactory` and independently instrumented public/private servers. Additional real runs proved original-leaf behavior for both unprotected modes, 152 actual TYPO3 CLI invocations with zero new target TCP accepts and HTTP requests, and four ordinary startup denials for conflicting factory replacements. The complete production matrix contains 168 process runs and 140 wire assertions. It consumes the shared security corpus case `EP-PRIVATE-UNBOUND` by case ID and content hash and proves zero native handler construction for that denied target. An actual flat legacy Vault allowlist entry is also present without granting Public access to its private host. Unit tests passed 19 tests and 73 assertions on PHP 8.5.11 / PHPUnit 13.4.1. See [integration evidence](../../evidence/typo3-integration/README.md) and [fixture instructions](Build/Fixtures/README.md) for source/lock hashes and exact runtime records; counts do not stand in for the full original T001–T084 security corpus.

```sh
Build/Scripts/runTests.sh -s unit -p 8.5
HTTP_GUARD_FIXTURE=/absolute/path/to/real/fixture Build/Scripts/runTests.sh -s integration -p 8.5
```

Test-only environment seams `HTTP_GUARD_PHPUNIT` and `HTTP_GUARD_TEST_AUTOLOAD` can point to a prepared dependency runtime. They do not alter application configuration or transport policy. Native Linux extraction avoids mounted-Windows dependency installation delays. The integration fixture uses isolated synthetic public address `203.0.114.102:8080` and private endpoint `10.23.5.12:8080`; its config grants only the explicit endpoint client access to the private target.

## Coverage and acceptance limits

The extension covers the documented Core outbound path after registration. Early bootstrap HTTP, independent raw Guzzle clients, a request-level handler bypass before middleware entry and Vault's separate factory require their own explicit integration. This extension never claims automatic Vault coverage. Incoming PSR-15 requests, arbitrary socket APIs and malicious PHP control are outside this protection boundary. A PSR-7 URI object or PSR-18 request cannot reveal raw syntax already discarded by its creator; validate raw URL strings before constructing those objects. The Core string entry point and diagnostic string entry point perform that validation themselves.

The shared library owns strict normalization, complete resolution, address policy, grants, isolated transport, retry, cancellation and telemetry. Its separate wire/security/mutation tests and the dependency security review remain separate acceptance evidence. Three upstream SVG-sanitize advisory ignores were used only in disposable testing fixtures to run the specified Core releases; no such ignore is present in this production package manifest. A passed integration matrix does not approve those upstream advisories for deployment. Human security review and an actual operator pilot remain external release gates; this package has not been published or deployed.

## Source references

Registration uses the documented [TYPO3 BootCompletedEvent](https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/ApiOverview/Events/Events/Core/Core/BootCompletedEvent.html) and [outbound HTTP middleware extension point](https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/ExtensionArchitecture/HowTo/RestRequests/Index.html). The raw string interception uses [Symfony service decoration](https://symfony.com/doc/7.4/service_container/decoration.html) with the real original Core service. Diagnostic startup relies on the [Symfony 7.4 service closure mechanism](https://symfony.com/doc/7.4/service_container/autowiring.html#injecting-service-closures-in-configuration-files), validated against the installed source. The exact locked Core and Guzzle source references are recorded with the runtime evidence.
