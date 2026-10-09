# Live GitHub verification

These snapshots record repository settings and actual GitHub execution on
2026-10-09. They are development evidence, excluded from the extension ZIP.
Each run record carries its own `headSha`; later commits do not inherit its
successful execution automatically.

At source revision `5f37a66c638519cc0f01d9f03d30efc6ed0f4957`:

- Verification run `37906749354` succeeds: source lint, installable package,
  and all three native transport SDK tuples. Coverage upload is intentionally
  skipped on a pull request; this does not verify the coverage service.
- Advanced CodeQL run `37906749939` succeeds for Actions and Python. CodeQL
  does not analyze this extension's PHP implementation.
- Dependency Review run `37906749776` succeeds. The independent strict
  dependency audit fails on the three documented SVG sanitizer advisories.
- CI run `37906751418` cannot start because an action used by the shared
  workflow is not allowed by the repository's existing action policy.

The action policy continues to require full SHA references and selected
actions. The reviewed `setup-php` revisions and the shared workflow's
`action-actionlint` revision are individually allowed. No third-party wildcard
or global policy exemption is added. The actionlint revision is unsigned;
its source and digest-pinned container are reviewed separately. The selected
action and permission snapshots record the resulting settings.

GitHub's Python default CodeQL setup conflicted with advanced CodeQL uploads.
The default setup is now `not-configured`; the repository's advanced workflow
uses `security-and-quality` queries for both supported languages. Successful
advanced execution is recorded separately from this setting change.

The first and second startup diagnostics are retained as historical failures.
A new push is required to verify the CI workflow after the final reviewed
action reference is allowed; a startup-failed run cannot be retried.

## Follow-up execution and failure propagation

Run `37909245811` at `82f1b476a42358b5ffbf88615aea6184e8864a0d`
starts correctly. Documentation succeeds with 16 English and 13 German pages;
workflow lint and DCO also succeed. Its PHP matrix reveals a command-status
bug: the shared workflow invokes multiline commands using `bash -c`, so a
passing final Core analysis masks six preceding Guzzle 8 kernel diagnostics.
Successful Guzzle 8 job status in this run is not a passing kernel analysis.

Both multiline caller commands now explicitly enable `set -euo pipefail`.
Running the exact corrected PHPStan command against the existing genuine
Core 14.3.7 / Guzzle 8.2.0 / PHPStan 2.3.1 fixture returns exit 1 after the
six kernel diagnostics. It stops before the final Core command can mask
them. `ci-failure-propagation.json` and the local command log preserve this
regression verification; no source diagnostic or dependency advisory is
suppressed. The corrected workflow requires its own subsequent GitHub run.
