# Combined-extension mutation witnesses

All **18** targeted mutants were killed on the current combined-extension source: six deliberate defects for each exact G7-latest, G8 and classic Core/TER G7 tuple. No production source was mutated. Disposable copies retained `Classes/HttpGuard`, `Resources/Private/HttpGuard/data`, `Tests/HttpGuard` and the root configuration.

The deliberate defects removed address policy, removed connection pinning, enabled an unchecked fallback, permitted proxy passthrough, permitted PHP-stream passthrough, or removed endpoint origin/method binding. A mutant is accepted as killed only when its test exits1 **and** the witness records a real extra target HTTP contact **and** native construction or a bypassed leaf call. Mere expected exceptions do not count.

`summary.json` records the major, exact fixture variant (`7`, `8`, `7ter`), outcome, leaf/native counters, and target TCP/HTTP before/after counters. AST payload/results and each test log are included. `production-source-hashes.json` binds these runs to the combined kernel files. The vendor tuples are the frozen audited fixtures in `../../dependencies/combined-kernel/`; the runner accepts `--g7-vendor`, `--g8-vendor` and `--classic7-vendor` explicitly.

Earlier12 witnesses belong to the historical two-package candidate and remain unchanged. Mutation selection is targeted evidence, not a claim that all possible implementation defects have been enumerated.
