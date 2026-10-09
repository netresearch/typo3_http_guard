# Contributing

HTTP Guard is one TYPO3 extension with an embedded security kernel. Use a separate branch and pull request. Changes require a human review, passing applicable checks and resolution of all review threads before merge. Do not bypass protected `main` or treat an agent review as the independent human security review.

## Commits and review

Use Conventional Commits and cryptographically signed commits. Include a matching `Signed-off-by:` trailer under the [Developer Certificate of Origin](https://developercertificate.org/), for example with `git commit -S -s`. Keep changes focused and describe the problem, resulting behavior, relevant test evidence and remaining limitations.

The [Netresearch code of conduct](https://github.com/netresearch/.github/blob/main/CODE_OF_CONDUCT.md) applies. Report security issues privately as described in [SECURITY.md](SECURITY.md).

## Local verification

Install the extension's development dependencies with Composer within the supported PHP/Core/SDK ranges. Use the commands in [Development](Documentation/Development/Index.rst), [genuine Core fixtures](Build/Fixtures/README.md) and [kernel verification](verification/README.md). `Build/Scripts/runTests.sh -s unit -p 8.5` selects Unit tests only. Kernel integration tests need the dedicated Docker targets and must run serially when sharing their TCP/HTTP counters. They must not contact production endpoints.

Render both the English manual and German localization with the documented renderer. Build the extension ZIP twice and compare the archives. Development files, evidence, synthetic certificate keys and vendor dependencies must be excluded from the installable package.

## Security-sensitive changes

Preserve explicit endpoint binding, immutable policy snapshots, fail-closed capability checks, controlled per-attempt transports and denial before a native send. Preserve semantic version ranges and the real Core/SDK API checks. A lease must allocate at most one native handle, including hidden SDK retries and reentrant preparation. Compatible dependency patches and minors within the supported ranges do not require a new extension release. Changes to minima, supported majors, transport behavior or public API require source review and the relevant qualification. A successful Composer solver alone does not prove the transport behavior.

Do not weaken tests or introduce transport fallbacks to make a check pass. Preserve historical evidence as historical; record fresh runs against the current source. Report tests, coverage, targeted security mutants and broader mutation scores as different measurements. Never represent an unmeasured case as passing.

Renovate owns Composer and GitHub Actions version updates and security update PRs. GitHub dependency alerts remain enabled; a separate Dependabot version-update configuration is not used. Frozen evidence, optional source overlays and qualification fixtures are excluded from update discovery. Core/SDK manifest update PRs require dashboard approval, applicable qualification and review; automated merging is disabled, including for vulnerability updates. Fixed fixtures preserve reproducible SDK/Core snapshots. Four floating native Core 13/14 and Guzzle 7/8 rows resolve the latest compatible graphs during pull requests and weekly scheduled runs; their results remain separate from historical fixture counts.

A release additionally requires independent human security review, the operator pilot and the documented supply-chain gates. Contributions do not authorize publication or deployment.
