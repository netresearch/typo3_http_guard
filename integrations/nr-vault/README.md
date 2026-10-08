# Explicit Vault adapter

This patch applies to `netresearch/t3x-nr-vault` at commit `5a070c396a614e5b05f63d79fa564c3748cf21eb`. It adds an optional resource/token adapter and preserves the default Vault factory and calling interfaces. No commit to the Vault repository, merge or publication was performed.

From a checkout of that exact baseline, run `git apply --check /absolute/path/to/vault-adapter.patch`, then `git apply /absolute/path/to/vault-adapter.patch`. `source-overlay/` contains the exact changed and added source bytes for review; `source-manifest.json` records every file hash and the patch hash.

Install the delivered `packages/http-guard` as a Composer path package using an explicit development version. The adapter is opt-in: configure named resource and token factories as shown in `source-overlay/Documentation/Developer/HttpGuard.rst`; do not replace unrelated global consumers automatically. Enforced OAuth resource clients require a separately enforced token binding.

For the source-based test fixture, copy the shared library's `src/`, `data/`, `tests/` and manifest into Vault's ignored `.Build/http-guard-library/`. Run `Build/Scripts/runGuardAdapterTests.sh` for the owned synthetic public/private fixture through the repository's required container wrapper. Run the existing full Unit, Fuzz and Functional suites and strict static/architecture checks as well. The test script requires Docker, Bash, OpenSSL and ripgrep on the host; it retains the isolated fixture networks and the wrapper removes its job container.

Exact final runs, test IDs, source/runtime hashes and dependency findings are recorded in [EVIDENCE.md](EVIDENCE.md). The wrapper prepares the shared target counters idempotently and uses separate owned `.150` NICs for its resource/token traffic.

The recorded local tests establish synthetic integration behavior. Operator production pilot and independent human release acceptance remain external gates. Full Vault dependency audits also retain a pre-existing `enshrined/svg-sanitize` finding; this adapter adds no required dependency to Vault.
