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
