# Final semantic-dependency SAST and fixture checks

The actual Opengrep 1.19.0 binary, verified against the shared workflow's SHA-256, ran with the exact current Security workflow configuration. The final run exits 0 with **zero findings**, executing 357 rules on 496 files. No vendor path was scanned. Raw output and both complete executions remain in `work/review-loop/semantic-static/`.

The first execution reported two command-injection findings at controlled Unit-test subprocess launches. Independent review confirmed argument arrays headed by `PHP_BINARY`, fixed repository code/fixtures and constant provider values. Two exact-rule call-site comments were applied through guarded AST editing. Semantic token vectors remained identical; 48 focused tests / 121 assertions passed. The full same-configuration rerun then produced no findings. No rule or test-directory exclusion was added.

The scanner reports **58 parsing diagnostics: 57 PartialParsing and one Syntax error**. Modern PHP constructs receive partial analysis; the syntax diagnostic concerns the existing Vault adapter patch. These limitations remain recorded in `parser-limitations.json`. Zero findings does not establish exhaustive PHP analysis.

All 496 scanned paths were bound at the start. The literal inventories contain 694 files before and 711 after, with 19 execution-evidence changes/additions and three documentation prose changes. Operative code, PHP examples, scripts, workflows, manifests and assets remained unchanged. The exact inventories and classified delta are preserved; their full byte equality is explicitly false.

The final synthetic TLS guard passes on the exact unchanged guard, keys, certificates and Security workflow. Its effective-exclusion controls pass 19 negative cases and two positive cases, with fixed failure output and no value disclosure. The public research-header guard also passes. These results are local evidence; fresh remote required checks remain the merge condition.
