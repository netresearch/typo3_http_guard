# Explicit Vault adapter

This optional patch applies to `netresearch/t3x-nr-vault` at commit `5a070c396a614e5b05f63d79fa564c3748cf21eb`. It adds separate resource and OAuth token bindings and preserves ordinary Vault defaults and calling interfaces. No commit, merge or publication was performed.

From a checkout of that exact baseline, run `git apply --check /absolute/path/to/vault-adapter.patch`, then `git apply /absolute/path/to/vault-adapter.patch`. `source-overlay/` contains all 25 changed or added files. [source-manifest.json](source-manifest.json) records their hashes and the patch hash; [patch-application-check.json](patch-application-check.json) records successful application to a clean baseline checkout and all 25 matching hashes.

Install the single delivered TYPO3 extension `netresearch/nr-http-guard`; its kernel is already included. The adapter remains opt-in. Configure named resource and token factories as shown in [the Vault integration manual](source-overlay/Documentation/Developer/HttpGuard.rst). Enforced OAuth resource clients require a separately enforced token binding. For custom legacy resolvers or native handlers in observe/disabled, explicitly compose the same original factory as documented there.

For source-based tests, copy the extension's `Classes/HttpGuard/`, `Resources/Private/HttpGuard/data/` and `Tests/HttpGuard/Integration/` into the same relative paths below Vault's ignored `.Build/nr-http-guard-extension/`. Retain a digest manifest. `Build/Scripts/runGuardAdapterTests.sh` uses the repository's required container runner and owned synthetic public/private fixtures. Docker, Bash, OpenSSL and ripgrep are required. The fixture networks persist; the wrapper removes its job container.

[Current and historical evidence](EVIDENCE.md) distinguishes the new combined-extension adapter runs from earlier full-suite runs. Production adapter PHP bytes are unchanged by packaging; only the suggestion, documentation and source-based test paths changed. The two current adapter suites passed all 19 cases without skips.

Operator production pilot and independent human release acceptance remain external gates. The existing full Vault development lock retains pre-existing SVG dependency advisories. The adapter adds a Composer suggestion and no required dependency.
