# Final live Security workflow result

GitHub run [37909245786](https://github.com/netresearch/typo3_http_guard/actions/runs/37909245786), attempt 1, completed with **failure** at 2026-10-09T09:11:03Z. The PR source head is `82f1b476a42358b5ffbf88615aea6184e8864a0d`; uploaded code-scanning analyses use the generated PR merge commit `94902abb18e6503e1d74b686928d2e785833953d`.

All expected jobs started. The event preflight passed. No new startup, permissions, action-allowlist, upload or other wiring failure was observed.

- **Betterleaks 1.1.2:** eight commits scanned, exactly **one** finding, exit 2. Its rule and location match the retained historical `cr_token` cookie at `packages/http-guard/data/security-corpus/sources/alibaba-imds.headers.txt:10`. This remains **needs-review** because external validity is unverified. The cookie value is not included in these metadata files. The 375 previously verified, immutable finding fingerprints remain the only exceptions; no broader exception was added.
- **Opengrep:** 357 rules on 402 files, exactly **13** findings, exit 1. Rule, path and start-line sets exactly match the prior source-backed applicability report. There are zero new findings. These fixture/tooling findings remain unsuppressed and the job remains red.
- **Strict Composer audit:** exactly **three** advisories, all for `enshrined/svg-sanitize` **0.22.0**: `PKSA-8j6w-3kr7-s9xk`, `PKSA-6j95-1jtb-x6wc` and `PKSA-cbx2-m9db-bmzd`; exit 1, no abandoned packages. The audit ran without advisory exceptions. The SBOM, resolved manifest, lock and full audit JSON were successfully uploaded in `production-dependency-evidence`.

The deliberately unused shared Composer Audit job was skipped; the separate strict audit ran and failed as recorded above. The overall Security check remains red. This evidence establishes functioning workflow wiring and the exact remaining finding scope; it does not establish release or production approval.

Safe metadata: `security-37909245786-summary.json`, `security-37909245786-final-run.json`, `security-37909245786-final-jobs.json`, `security-37909245786-betterleaks-redacted-metadata.json`, `security-37909245786-opengrep-redacted-metadata.json`, `security-37909245786-opengrep-comparison.json`, `security-37909245786-audit-redacted-metadata.json`. Original job logs and the dependency artifact were downloaded only into this work directory; no new source scan, source edit or remote mutation was performed.
