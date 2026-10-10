# Contributing

HTTP Guard is one TYPO3 extension with an embedded security kernel. Use an
isolated branch and pull request. Resolve review findings, repeat independent
review and merge only after the applicable protected checks pass. User-approved
alpha work does not require an additional human acceptance gate. An agent
review is recorded as agent review; it is never evidence of human security
review or an executed operator pilot.

## Commits and review

Use Conventional Commits and cryptographically signed commits. Include a
matching `Signed-off-by:` trailer under the
[Developer Certificate of Origin](https://developercertificate.org/), for example
with `git commit -S -s`. Describe the problem, resulting behavior, meaningful
validation and remaining limitations. Keep captured originals and historical
reports unchanged.

The [Netresearch code of conduct](https://github.com/netresearch/.github/blob/main/CODE_OF_CONDUCT.md)
applies. Use [GitHub issues](https://github.com/netresearch/typo3_http_guard/issues)
for bugs and feature requests. Report vulnerabilities privately through
[SECURITY.md](SECURITY.md). Ownership routing is defined in
[CODEOWNERS](.github/CODEOWNERS); the current verified maintainer is
[@CybotTM](https://github.com/CybotTM). AI contributors also read
[AGENTS.md](AGENTS.md) and the applicable scoped instructions.

## Development setup

Use PHP and dependencies within [the supported ranges](Documentation/Installation/Index.rst),
Composer, Git, Bash and Python 3 with virtual-environment support. Run `composer install` in the development
checkout. Development tools are installed in `.Build/vendor/`; they are excluded
from the extension package. The library root does not track `composer.lock`.
Disposable qualification fixtures keep their own reproducible graphs.

Python build controls and coverage normalization also require the development
dependencies in `Build/requirements.txt`. Install them in an isolated environment:

```bash
python3 -m venv .Build/python
.Build/python/bin/python -m pip install --requirement Build/requirements.txt
source .Build/python/bin/activate
python3 -m unittest discover -s Tests/Build -p 'test_*.py'
```

These Python dependencies are excluded from the TER package. CI creates its own
temporary environment before running the same build controls.

Normal development installs activate CaptainHook. The commit-message hook
checks Conventional Commits and DCO; the pre-commit hook checks staged blobs for
secrets before deterministic local quality checks. Missing required tools or a
failed check produces a failure. Each isolated checkout needs its own dependency
installation and hook setup. CI fixture installs may explicitly disable scripts
or plugins for their disposable purpose; that is not a passing local-hook test.

```bash
composer check:harness
composer check:secrets
composer check:local
```

The staged secret checker reads changed Git index blobs, including renames and
unusual filenames. It uses the installed typed token detectors and a PEM private
key check. It does not guarantee detection of every secret format. Exact
synthetic fixture exceptions are path- and hash-bound; an altered or copied key
is rejected. CI also runs the broader history scanner.

## Local verification

| Command | Scope |
| --- | --- |
| `composer ci:test:php:cgl` | PHP style, dry run |
| `composer ci:test:php:rector` | PHP modernization, dry run |
| `composer ci:test:php:phpstan` | Curated kernel and actual Core static profiles |
| `composer ci:test:php:unit` | Offline adapter and kernel Unit tests |
| `composer ci:test:php:functional` | Controlled native transport tests |
| `composer ci:test:php:architecture` | Declared architecture boundaries |
| `composer ci:test:php:mutation` | Scoped Infection mutation analysis |
| `composer ci:test:php:fuzz` | Deterministic input/property checks |
| `composer ci:test:php:performance` | Recorded normalization/classification/policy benchmark |

See [Development](Documentation/Development/Index.rst),
[genuine Core fixtures](Build/Fixtures/README.md) and
[source-bound verification](Documentation/Development/Verification.rst) for inputs and actual reproduction.
A command's existence is not an execution result. Keep tests, coverage,
architecture controls, targeted security mutants, Infection scores and
performance measurements separate.

Native tests require dedicated Docker targets and must run serially when
sharing TCP/HTTP counters. They must not contact production endpoints. Offline
Unit tests need no database. Render the English and German manuals with the
[documented renderer](Documentation/Development/Index.rst). Build the extension
ZIP twice and compare the archives. Development tools, agent instructions,
evidence and synthetic private keys must stay outside the installable package.

## Security-sensitive changes

Preserve endpoint binding, immutable policy snapshots, fail-closed capability
checks, controlled per-attempt transports and denial before native send. A lease
must allocate at most one native handle, including hidden SDK retries and
reentrant body preparation. Do not weaken controls or add transport fallbacks.

Compatible patches and minors within the semantic ranges require no extension
release. Changes to minima, supported majors, transport behavior, address data
or public APIs require source comparison and the applicable policy, wire, Core
and mutation tests. A successful Composer solver alone does not prove native
behavior. Retain exact source/version bindings for new results.

Renovate owns Composer and Actions updates. GitHub vulnerability alerts remain
enabled; a separate Dependabot version-update configuration is not used.
Historical evidence and frozen fixture locks are excluded from update discovery.
Core/SDK and vulnerability updates require dashboard approval and qualification;
their automated merge is disabled. Fixed fixtures retain reproducible snapshots.
Four floating Core 13/14 and Guzzle 7/8 native rows run on pull requests and weekly
schedules, separately from historical measurements.

## Publication and production use

Contributions do not authorize a new release or deployment. Published alpha
artifacts and their verification are documented in
[Release provenance](Documentation/Development/ReleaseProvenance.rst).
Signature/provenance validation, registry publication and hosted documentation
are distinct outcomes. Independent human review and a representative operator
pilot are recommended when assessing production use; neither has been recorded
as completed, and neither adds a new approval condition to authorized alpha work.
