# Security policy

## Supported scope

HTTP Guard is currently an unpublished alpha. There are no supported stable releases yet. The qualified PHP, TYPO3 and complete SDK tuples are listed in [the installation manual](Documentation/Installation/Index.rst). An independently changed SDK component is not a supported tuple.

The automated tests and agent reviews do not replace the independent human security review and operator pilot required before production use. See [the verification report](Documentation/Development/Verification.rst) and [the operations manual](Documentation/Operations/Index.rst).

## Private reporting

Please report vulnerabilities through [GitHub private vulnerability reporting](https://github.com/netresearch/typo3_http_guard/security/advisories/new). Do not include exploit details, private endpoints, credentials or customer data in a public issue. Include the exact extension commit, PHP/Core/SDK tuple, policy mode, minimal configuration with secrets removed, and a reproducible request or test.

The [Netresearch security policy](https://github.com/netresearch/.github/blob/main/SECURITY.md) governs acknowledgment, coordinated disclosure and dependency findings. It states acknowledgment within two business days and a fix or mitigation target of ten business days depending on severity. No stable-version maintenance promise is implied for this alpha.

## Dependency and verification policy

Security checks must distinguish the production dependency graph from disposable historical test fixtures. Archived fixture locks retain their original findings and narrowly documented advisory exceptions. Those exceptions do not authorize a production installation or suppress the audit of a fresh dependency resolution.

Known exploitable dependency findings block release. An unreachable advisory service is an inconclusive audit, not a clean result. Any exception must document the advisory, affected scope, exploitability, owner and review date; do not replace an exact security qualification with a blanket ignore.

Use the complete test matrix when changing URI normalization, address rules, DNS, middleware ordering, invocation lifetime, SDK tuples or the controlled transport. Tests generate synthetic certificate keys solely for isolated local fixtures. These are not production credentials. Do not commit generated credentials, private endpoints, runtime caches or fresh test secrets.

Release provenance, signed tags, SBOM review, registry publication and production approval remain separate release gates. The repository workflows do not publish to TER, Packagist or a production instance.
