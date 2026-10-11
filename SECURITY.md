# Security policy

## Supported scope

HTTP Guard 0.1.1 is a published alpha for evaluation, available from
[TER](https://extensions.typo3.org/extension/nr_http_guard) and
[Packagist](https://packagist.org/packages/netresearch/nr-http-guard). No stable
release is advertised. Supported PHP, Core and SDK semantic ranges are listed in
[the installation manual](Documentation/Installation/Index.rst). Compatible
patches and minors can run without a new extension release when the complete
graph and actual Core/SDK API and capability checks are compatible.

The registered TYPO3 RequestFactory path and guard-created clients are protected.
Direct cURL/socket calls, unrelated SDK clients and early bootstrap paths require
their own integration. See [security boundaries](Documentation/Security/Index.rst).

User-approved alpha development and merging require independent agent review,
resolved findings and green applicable checks. No additional human approval or
operator-pilot gate applies at this stage. Neither a human security review nor a
representative operator pilot is claimed completed. They remain recommendations
for assessing production use alongside [the operations manual](Documentation/Operations/Index.rst).

## Private reporting

Report vulnerabilities through
[GitHub private vulnerability reporting](https://github.com/netresearch/typo3_http_guard/security/advisories/new).
Do not put exploit details, private endpoints, credentials or customer data in a
public issue. Include the exact source commit, PHP/Core/SDK tuple, policy mode,
a minimal redacted configuration and a reproducible request or test.

The [Netresearch security policy](https://github.com/netresearch/.github/blob/main/SECURITY.md)
defines acknowledgment and coordinated disclosure. It states acknowledgment
within two business days and a fix or mitigation target of ten business days,
depending on severity. This alpha does not advertise a stable-version support
commitment. If a real secret has been exposed, arrange rotation through its owner
rather than merely deleting the visible value.

## Dependency and verification policy

Audit fresh production graphs separately for both supported Core majors. Fail
on any vulnerability advisory or an unavailable audit service. Core 13's upstream
abandoned `doctrine/annotations` warning is reported separately under the explicit
`--abandoned=report` policy; it is not an advisory ignore. Historical fixture locks
and their recorded exceptions live in byte-preserving archives and are never
current installation inputs. See [Dependencies](Documentation/Development/Dependencies.rst).

Known exploitable findings block release. Any justified exception must identify
the exact rule/advisory, affected scope, exploitability, owner and review date.
Never use a blanket ignore or rewrite old evidence to turn a finding green.

Use the applicable complete matrix for URI/address/DNS changes, middleware order,
resource lifetime, API changes or dependency minima/majors. Fixed rows preserve
exact snapshots; floating rows resolve latest compatible graphs. Real native
contacts, no-contact witnesses and ordinary Unit/coverage reports remain distinct
measurements. Each lease admits at most one native handle independently of private
SDK retry counters.

## Secrets and findings

CaptainHook checks changed staged blobs before local quality checks. Its typed
token/PEM detectors are a local guard, not a guarantee of detecting every secret.
CI runs Betterleaks for broader history scanning and Opengrep at WARNING/ERROR;
CodeQL findings and workflow findings are triaged under the organization policy.
Modern PHP parser limitations and exact-rule exceptions must remain visible in
source-bound reports. A zero tool result is not a whole-repository security claim.

Synthetic TLS private keys are restricted to isolated test fixtures with exact
path/hash validation and no production trust. Do not commit runtime credentials,
private endpoints, caches or fresh test secrets. Downloaded research originals
retain provenance; active public response-header values are redacted as recorded
in the review-loop evidence.

## Release integrity and publication

The release workflow can publish GitHub artifacts, TER and Packagist packages and
request a TYPO3 documentation build. A successful signature check does not prove
registry or manual publication. The signed v0.1.1 archives, payload signatures,
checksums and archive provenance were independently verified by
[run 37964411091](https://github.com/netresearch/typo3_http_guard/actions/runs/37964411091).
This verification-only run did not republish or replace the release assets.

The release SBOMs are supplemental repository inventories; separate production
security jobs generate resolved dependency SBOMs. The archive provenance does not
attest the release SBOMs. No SLSA level 3 certification or OpenSSF badge level is
claimed. The hosted manual remains awaiting external TYPO3 approval; the English
and German sources are already included in the extension.
