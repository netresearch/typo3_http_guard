# Vault adapter verification

The optional adapter targets `netresearch/t3x-nr-vault` commit `5a070c396a614e5b05f63d79fa564c3748cf21eb` and the kernel included in the single `netresearch/nr-http-guard` TYPO3 extension. [source-manifest.json](source-manifest.json) binds the 25 delivered files and current patch; [patch-application-check.json](patch-application-check.json) proves clean baseline application with all 25 hashes matching. No commit, merge or publication was performed.

## Current combined-extension runs

All current PHP checks used Vault's required `Build/Scripts/runTests.sh` container runner. Adapter Functional runs used SQLite, genuine TYPO3 bootstrap and the actual copied combined-extension kernel. Each runtime record additionally identifies the real reflected kernel source under `.Build/nr-http-guard-extension/Classes/HttpGuard/`. No adapter case was skipped.

| Current check | Exact G7 7.15.5 / 2.5.3 / 2.13.1 | Exact G8 8.2.0 / 3.0.2 / 3.1.0 |
|---|---:|---:|
| Scoped Adapter Functional | 19 tests / 249 assertions | 19 / 250 |
| Guard Unit | — | 9 / 50 |
| Additive public API snapshot | — | 1 / 247 |
| Strict PHPStan level 10 with repository extensions/architecture | — | 0 errors |
| Rector dry run and CGL | — | clean; 0 fixable / 595 files |
| Repository architecture/test/documentation rules | — | pass |

Both tuples used PHP8.5.10, TYPO3 14.3.7, PHPUnit13.4.1, Alpine Linux3.24.2, libcurl8.22.0 and OpenSSL3.5.8. Files in [evidence/single-extension/](evidence/single-extension/) include `guzzleN.runtime.json`, `guzzleN.tested-source-manifest.json`, `guzzleN.kernel-manifest.json` and `vault-single-extension-gN-adapter-frozen.log`. Runtime Composer manifests/locks remain separately pinned, while every other delivered Vault file matches the tested native copy. Kernel manifests match the current combined-extension delivery, including its root production manifest.

Strict PHPStan uses the existing `Build/phpstan.no-plugins.neon` configuration, which explicitly includes the strict extensions and architecture rules. Current Rector, CGL, PHPStan, API snapshot, Guard Unit and repository checks have individually named logs in that folder. The current RST render completed all78 pages. It retains existing Sitemap duplicate-parent and json5 highlighting warnings; the CGL log retains the existing deprecated rule-set notice.

The extra G8 assertion inspects its real middleware stack to locate the terminal driver. It is not a different security result. All guarded streaming, cancellation, raw OAuth URI, separate token/resource and shared-corpus cases execute in both current tuples.

## Historical full-suite baseline

The earlier two-package candidate and its complete evidence are preserved under [evidence/historical-two-package/integrations/nr-vault/](../../evidence/historical-two-package/integrations/nr-vault/). Those exact historical runs passed full Unit3978/14868 on both majors, full Functional496/2653 (G7) and496/2654 (G8), and earlier Fuzz1612/7146. They are not labelled as executions of the current packaging bytes.

[packaging-source-delta.json](evidence/single-extension/packaging-source-delta.json) compares the old and current patch manifests. Only six files changed: the optional Composer suggestion, two documentation files, the source bootstrap, the adapter test corpus/skip paths and the additive test runner's source paths. All production adapter PHP, existing factory/OAuth/client changes, service wiring and snapshot bytes match the historically fully tested candidate. The targeted current runs above cover the changed paths and current kernel tuple; the enormous unchanged Vault suites were not repeated for this packaging-only move.

## Original required cases

The named methods below are in `source-overlay/Tests/Functional/Http/GuardAdapterTest.php` and ran in both current combined-extension adapter tuples. The current scoped functional logs cover all 19 methods, not only the highlighted examples.

| Original case | Executed Vault evidence |
|---|---|
| T059: streaming before completion, same credential path | `enforcedStreamingCloseStopsTheMatchingTransfer` reads a partial chunk then closes before all 30 delayed chunks; `protectedOAuthTokenBindingWorksWithCancellationAndStreamingSends` exercises the guarded token and resource legs with authorization on the resource. |
| T060: pre-send and active cancellation | `preCancelledProtectedSendReadsNoSecretAndMakesNoContact`; `enforcedActiveCancellationClosesTheMatchingWireTransfer`; `cancellationDuringProtectedTokenTransferClosesTokenSocketBeforeResourceContact`. Audit counts and early socket closure are asserted. |
| T061: close, partial read, failure and callback cleanup | `enforcedStreamingCloseStopsTheMatchingTransfer`; `truncatedProtectedStreamThrowsAndLeavesNoBackgroundTransfer`. Existing streaming/cancellable Unit tests exercise throwing caller signals and are included in the full Unit run; actual guarded `on_headers` callback exceptions and lease cleanup are also exercised by the shared transport suite in the combined-extension kernel matrix. Vault does not expose arbitrary Guzzle callback options. |
| T070: deliberate complete adapter integration | `enforcedResourceUsesItsPinnedProfileAndAuditsOneWireContact`; `oauthUsesSeparateTokenOriginAndKeepsCacheAcrossTimeoutClone`; both guarded cancellation/streaming cases; `expiryAfterPreflightIsRecheckedBeforeWireContact`. Enforce capability failures propagate; Unit tests reject arbitrary client/token manager bypasses. |
| T071: private token/public resource and inverse | `publicResourcePrivateTokenAndInverseStaySeparatelyBound` uses real owned Docker NICs `203.0.115.150` and `10.23.4.150`, exercises both directions, token caching across a timeout clone and refusal when the resource binding is offered for the token origin. |
| T072: authentication and redacted audit | `allAuthenticationPlacementsRetainWireSemanticsAndRedactedAudit` exercises bearer, basic, header, API key, query and body placements. OAuth token/resource tests add the separate OAuth path. Audit records exclude synthetic credential values. |
| T039 / T073 / T076: no grant from a flat Vault allowlist; shared corpus | `sharedPrivateUnboundCorpusCannotBecomeAGrantThroughLegacyAllowedHosts` consumes `EP-PRIVATE-UNBOUND` from the actual shared corpus by ID and hash. The host is explicitly in legacy `allowed_hosts`, the endpoint is configured but unbound, and the real protected Vault client rejects it before secrets or native handles. |

`observeKeepsLegacyAllowlistAndSsrfPinWhileReportingWouldDeny`, `disabledStillRefusesLegacyPrivateHostAndAddsNoDiagnostics` and `nonEnforceModesPreserveTheirLegacyStreamingTickerAndCancellation` demonstrate the composed original factory and injected resolver, including the original stream ticker. `observeOauthWithoutATokenBindingKeepsOriginalTokenChecks` covers the documented unprotected legacy token fallback in observe mode. `rawOAuthUriRefusalPrecedesPsrParsingAndSecretsAcrossModes` proves empty fragments and raw backslashes are rejected before PSR parsing or secret retrieval in enforce, while observe reports `would_deny` and disabled delegates without diagnostics.

## Current shared private-endpoint proof

`EP-PRIVATE-UNBOUND` consumes corpus SHA-256 `4a61da4e50ee6efd5be31c5da97eb1f1eba3ed4bf6fc04376711d19417994825`. Both current [G7](evidence/single-extension/guzzle7.shared-unbound.json) and [G8](evidence/single-extension/guzzle8.shared-unbound.json) records show `address_forbidden`; actual created/released/active/peak/native counters all zero; target TCP and HTTP deltas both zero; and the secret retrieval expectation `never`. The host is explicitly in the legacy allowlist and no endpoint binding exists. Admin counter reads are separate from target contacts.

## Limits and dependency findings

These are local synthetic runs and independent agent source review. Operator production pilot, independent human release acceptance and the controlled CI benchmark remain external/open gates.

The existing full Vault development lock retains three pre-existing `enshrined/svg-sanitize <=0.22.0` advisories: PKSA-8j6w-3kr7-s9xk, PKSA-6j95-1jtb-x6wc and PKSA-cbx2-m9db-bmzd, plus one unrelated abandoned development package. The historical [full Vault audit](../../evidence/historical-two-package/integrations/nr-vault/evidence/vault-guzzle7-composer-audit.json) remains preserved. This adapter adds only a suggestion. The three minimal current kernel test-fixture audits under `verification/dependencies/combined-kernel/` are separately clean; they do not replace the full Vault audit.
