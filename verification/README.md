# Reproduce verification of the single TYPO3 extension

Production consists of the root package `netresearch/nr-http-guard`.
`Classes/HttpGuard/` and `Resources/Private/HttpGuard/data/` are part of this
extension. The minimal Composer projects in `dependencies/combined-kernel/`
are test fixtures for the three exact SDK combinations, rather than separately
installable HTTP Guard products.

## Kernel matrix

The actual twelve-cell matrix is recorded in
[evidence/extension-matrix/README.md](evidence/extension-matrix/README.md).
Each combination of four PHP versions and three exact SDK tuples runs 126 tests
with 2,180 assertions and no skipped cases. Reproduction requires Docker,
Composer, Bash, Python, rsync and OpenSSL. Dependencies are installed in a native
Linux directory.

```sh
bash verification/scripts/run-library-matrix.sh \
  /absolute/path/to/nr_http_guard \
  /tmp/nr-http-guard-kernel-matrix \
  /tmp/nr-http-guard-kernel-evidence
```

The script name is retained from the previous package layout. It now copies the
core from the single extension, its production manifest and the corresponding
tests into isolated test runtimes. It changes no production source. All PHP
images are recorded by digest. The loopback and IPv6 checks require the
documented native Linux networking environment.

`Tests/HttpGuard/Integration/prepare-wire.sh` operates only its own synthetic
public, private and IPv6 test destinations. Native construction, TCP accepts
and HTTP requests are counted before and after each relevant case. An error
alone does not prove that no connection occurred. Concurrent tests using the
same counters invalidate the evidence and must be avoided.

## Targeted protection faults

All 18 mutants against the combined source are recorded in
[evidence/extension-mutations/README.md](evidence/extension-mutations/README.md).
The six faults are created for all three SDK tuples exclusively in disposable
copies using the AST tool. A detected mutant must produce a failing test, an
actual additional HTTP contact, and either a native attempt or a bypassed leaf
call.

```sh
python3 verification/scripts/run-mutations.py \
  --package /absolute/path/to/nr_http_guard \
  --scratch /tmp/nr-http-guard-mutations \
  --evidence /tmp/nr-http-guard-mutation-evidence \
  --g7-vendor /tmp/nr-http-guard-kernel-matrix/guzzle7/vendor \
  --g8-vendor /tmp/nr-http-guard-kernel-matrix/guzzle8/vendor \
  --classic7-vendor /tmp/nr-http-guard-kernel-matrix/guzzle7ter/vendor \
  --editor /absolute/path/to/php-ast-edit \
  --image ghcr.io/typo3/core-testing-php85@sha256:53df750b7e68ccce8da03a03bfe7cb57d732308b932f2ea92b1fd31679626bc6
```

## Actual TYPO3 and Vault paths

`Build/Fixtures/README.md` describes the Composer and classic Core fixtures.
The classic fixtures use the official complete archives, the real Extension
Manager activation path and Core-generated class-loading information. They load
the security classes from the extracted extension; a separately installed HTTP
Guard library package is unavailable.

The optional changes under `integrations/nr-vault/` belong to the separate
nr-vault product. Its patch is applied only to the specified base commit after
a successful `git apply --check`. Its adapter smoke tests load the classes from
the combined extension; the detailed older unit, fuzz and functional evidence
retains its original source revision.

The original eight-cell matrix and twelve earlier mutants remain historical
evidence. `evidence/packaging/source-layout-map.json` in the repository explains
their old paths. The acceptance ledger distinguishes local execution, current
source binding and the outstanding human review, operator pilot and CI
performance acceptance.
