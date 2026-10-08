# Vault adapter verification

The patch targets `netresearch/t3x-nr-vault` commit `5a070c396a614e5b05f63d79fa564c3748cf21eb`. `source-manifest.json` records the 25 delivered source files and patch SHA-256. No commit to the Vault repository, merge or publication was performed. Resource and token opt-in are explicit; ordinary factory defaults remain legacy.

All following final runs used the repository's required `Build/Scripts/runTests.sh` container runner. Functional runs used SQLite, actual TYPO3 extension bootstrap and the exact copied shared library source. No adapter case was skipped. Each tuple's `evidence/vault-guzzleN-final-tested-source-manifest.json`, `-final-frozen-library-manifest.json` and `-final-runtime.json` bind the execution to its source and runtime. G7's runtime manifest is deliberately pinned separately from the delivered Vault Composer manifest.

| Final check | Guzzle 7.15.5 / Promises 2.5.3 / PSR-7 2.13.1 | Guzzle 8.2.0 / Promises 3.0.2 / PSR-7 3.1.0 |
|---|---:|---:|
| Full Unit, including additive API snapshot | 3978 tests / 14868 assertions | 3978 / 14868 |
| Full Functional | 496 tests / 2653 assertions | 496 / 2654 |
| Adapter Functional cases alone | 19 tests / 249 assertions | 19 / 250 |
| Strict PHPStan, level 10 plus repository extensions/architecture | 0 errors | 0 errors |

Both tuples used PHP 8.5.10, TYPO3 14.3.7 and PHPUnit 13.4.1, with the frozen PHP85 image reference in the runtime JSON. Logs are `evidence/vault-guzzleN-final-unit-frozen.log`, `-final-functional-frozen.log`, `-final-adapter-cases.log` and `-final-phpstan-frozen-clean.log`. Strict analysis used the existing `Build/phpstan.no-plugins.neon` configuration because these source fixture installations originally ran without Composer plugins; that configuration explicitly loads the strict repository extensions and architecture rules.

The extra G8 assertion comes from inspecting the actual middleware stack while finding Vault's terminal driver. It is not a different security result. `vault-final-rector-check.log`, `vault-final-cgl-check.log` (zero fixable files among 595) and `vault-final-repo-checks.log` establish Rector, formatting, repository architecture/test rules and CLI documentation checks. The fixer retains its existing deprecated `@PHP82Migration` rule-set notice. Documentation validation reported 78 RST files and zero validation warnings; all 78 pages rendered. The render log retains the pre-existing Sitemap duplicate-parent warnings.

The earlier unchanged fuzz regression suite passed 1612 tests / 7146 assertions. `evidence/vault-earlier-fuzz-baseline.log` is labelled as an earlier source run and is not attributed to the later raw-URI seam.

## Original required cases

The named methods below are in `source-overlay/Tests/Functional/Http/GuardAdapterTest.php` and ran in both final adapter tuples. Full source/functional logs cover all 19 methods, not only the highlighted examples.

| Original case | Executed Vault evidence |
|---|---|
| T059: streaming before completion, same credential path | `enforcedStreamingCloseStopsTheMatchingTransfer` reads a partial chunk then closes before all 30 delayed chunks; `protectedOAuthTokenBindingWorksWithCancellationAndStreamingSends` exercises the guarded token and resource legs with authorization on the resource. |
| T060: pre-send and active cancellation | `preCancelledProtectedSendReadsNoSecretAndMakesNoContact`; `enforcedActiveCancellationClosesTheMatchingWireTransfer`; `cancellationDuringProtectedTokenTransferClosesTokenSocketBeforeResourceContact`. Audit counts and early socket closure are asserted. |
| T061: close, partial read, failure and callback cleanup | `enforcedStreamingCloseStopsTheMatchingTransfer`; `truncatedProtectedStreamThrowsAndLeavesNoBackgroundTransfer`. Existing streaming/cancellable Unit tests exercise throwing caller signals and are included in the full Unit run; actual guarded `on_headers` callback exceptions and lease cleanup are also exercised by the shared transport suite in the standalone matrix. Vault does not expose arbitrary Guzzle callback options. |
| T070: deliberate complete adapter integration | `enforcedResourceUsesItsPinnedProfileAndAuditsOneWireContact`; `oauthUsesSeparateTokenOriginAndKeepsCacheAcrossTimeoutClone`; both guarded cancellation/streaming cases; `expiryAfterPreflightIsRecheckedBeforeWireContact`. Enforce capability failures propagate; Unit tests reject arbitrary client/token manager bypasses. |
| T071: private token/public resource and inverse | `publicResourcePrivateTokenAndInverseStaySeparatelyBound` uses real owned Docker NICs `203.0.115.150` and `10.23.4.150`, exercises both directions, token caching across a timeout clone and refusal when the resource binding is offered for the token origin. |
| T072: authentication and redacted audit | `allAuthenticationPlacementsRetainWireSemanticsAndRedactedAudit` exercises bearer, basic, header, API key, query and body placements. OAuth token/resource tests add the separate OAuth path. Audit records exclude synthetic credential values. |
| T039 / T073 / T076: no grant from a flat Vault allowlist; shared corpus | `sharedPrivateUnboundCorpusCannotBecomeAGrantThroughLegacyAllowedHosts` consumes `EP-PRIVATE-UNBOUND` from the actual shared corpus by ID and hash. The host is explicitly in legacy `allowed_hosts`, the endpoint is configured but unbound, and the real protected Vault client rejects it before secrets or native handles. |

`observeKeepsLegacyAllowlistAndSsrfPinWhileReportingWouldDeny`, `disabledStillRefusesLegacyPrivateHostAndAddsNoDiagnostics` and `nonEnforceModesPreserveTheirLegacyStreamingTickerAndCancellation` demonstrate the composed original factory and injected resolver, including the original stream ticker. `observeOauthWithoutATokenBindingKeepsOriginalTokenChecks` covers the documented unprotected legacy token fallback in observe mode. `rawOAuthUriRefusalPrecedesPsrParsingAndSecretsAcrossModes` proves empty fragments and raw backslashes are rejected before PSR parsing or secret retrieval in enforce, while observe reports `would_deny` and disabled delegates without diagnostics.

## Shared private-endpoint proof

`EP-PRIVATE-UNBOUND` uses corpus SHA-256 `4a61da4e50ee6efd5be31c5da97eb1f1eba3ed4bf6fc04376711d19417994825`. Both `evidence/vault-guzzleN-shared-unbound.json` records show `address_forbidden`, the actual Vault inner client's terminal driver's created/released/active/peak/native counters all zero, and private target TCP and HTTP deltas both zero. The Vault secret mock expects `retrieve()` never. Admin counter reads are separate from counted target contacts.

## Limits and dependency finding

These are local synthetic execution results and an independent agent source review. They are not an operator production pilot or independent human release acceptance. Those external gates remain open.

The full existing Vault development dependency lock contains three pre-existing `enshrined/svg-sanitize <=0.22.0` advisories, recorded in `evidence/vault-guzzle7-composer-audit.json`: PKSA-8j6w-3kr7-s9xk, PKSA-6j95-1jtb-x6wc and PKSA-cbx2-m9db-bmzd. One unrelated development package is abandoned. The adapter adds only a Composer suggestion, not a required dependency. The separate minimal shared-library tuple audits are recorded with the PHP matrix and must not be confused with this Vault dependency audit.
