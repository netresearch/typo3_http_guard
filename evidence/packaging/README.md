# Single-extension packaging verification

This evidence concerns the combined `netresearch/nr-http-guard` extension, version 0.1.0, alpha. The kernel is embedded under `Classes/HttpGuard`, with its existing public namespace `Netresearch\HttpGuard`. The TYPO3 integration keeps `Netresearch\NrHttpGuard`. Production uses the two native PSR-4 mappings in the extension metadata. No custom production autoloader, private SDK vendor directory or separately installed HTTP Guard package is required.

The four Composer cells bootstrap the actual TYPO3 Core 13.4.35/14.3.7 with the previously qualified Guzzle 7.15.5 and 8.2.0 tuples. `composer-matrix/production-matrix-summary.json` records 168 process outcomes, including 140 wire assertions, observe/disabled behavior and 156 offline CLI/startup records. `composer-locks` records the combined extension's exact fixture manifests and locks. The archived earlier two-package evidence is historical, not proof of this packaging.

The classic cells use the actual official TYPO3 distributions, downloaded from `https://get.typo3.org/13.4.35/tar.gz` and `https://get.typo3.org/14.3.7/tar.gz`. Their SHA256 hashes were checked against the official [13.4.35 release notes](https://get.typo3.org/release-notes/13.4.35) and [14.3.7 release notes](https://get.typo3.org/release-notes/14.3.7). Both contain Guzzle 7.15.3, Promises 2.5.2 and PSR-7 2.13.0. The initial unqualified tuple failed closed with `transport_unsupported`, as recorded in `core13-initial-fail-closed.json` and `core14-initial-fail-closed.json`. The exact tuple was added after the source comparison, including the full SDK comparison in `sdk-full-source-comparison.{json,diff}`, then exercised through the actual native Core client. Arbitrary patch combinations remain denied.

The native preparation creates a disposable SQLite database with the actual Core schema services, then invokes the official `extension:activate nr_http_guard` command. This reaches Extension Manager's `InstallUtility` and `PackageActivationService`. `core*-extension-manager-activate.*` records that command. The extension is extracted physically under `typo3conf/ext/nr_http_guard`; it is not a symlink to the development checkout. Before bootstrap neither extension namespace exists in the official Core vendor loader. After activation, Core's generated `typo3conf/autoload/autoload_psr4.php` contains both mappings. Reflection proves both integration and kernel classes load from the ZIP extraction. `core*-native-{prepare,active,wire}.stdout` records these checks.

`classic-matrix/production-matrix-summary.json` records 84 additional process outcomes: 70 enforce wire assertions, four observe/disabled runs and 78 offline CLI/startup records. The CLI diagnostics expose the actual Core version through TYPO3's `Typo3Version`; the classic distribution does not provide an installed split `typo3/cms-core` Composer package. The guards still require exact Core versions and verified RequestFactory ABI.

`unified-unit-core14g8.log` records the unified Unit suite with 96 tests and 1173 assertions. Tests use their explicit bootstrap; production classic loading is proven independently. `frozen-packaging-source-hashes-before.json` and its final counterpart record the source used by the final integration run. The final ZIP import record `final-zip-import.json` verifies SHA256 `f5e83e6fb4dcc49a6c3b20e12d7d167b08cd0bb0d4f76204f9fb77b495d02853`, all 106 members against the origin source, and both physical native extractions. Both final native map/source probes pass 14 checks and both final native wire runs pass 35 assertions. These 70 final artifact assertions are separate from the 252-process qualification matrix because the first native qualification used a preliminary archive while the manual was being completed. Text-only archive updates do not change the qualified kernel behavior.

Core 14's classic PackageManager recognizes the already installed Guzzle, Promises, PSR and Symfony dependencies through `InstalledVersions`. Therefore `extra.typo3/cms.Package.providesPackages` is an empty object: the extension bundles its own code, not copies of external SDK packages. The extension also provides `extra.typo3/cms.version=0.1.0`; `ext_emconf.php` retains alpha metadata and both namespace mappings for Core 13/TER compatibility.

To reproduce, start the dedicated wire fixtures, build the extension ZIP, then use a new native Linux directory:

```sh
Build/Fixtures/prepare-wire.sh
python3 Build/Scripts/build-extension.py --output /absolute/nr_http_guard_0.1.0.zip
python3 Build/Fixtures/prepare-classic.py --runtime /absolute/new/classic --archive /absolute/nr_http_guard_0.1.0.zip
```

For each `classic13` and `classic14` site, run `Tests/Integration/classic-package-bootstrap.php SITE prepare`, then set `TYPO3_PATH_ROOT=SITE` and `TYPO3_PATH_APP=SITE` for `php SITE/typo3/sysext/core/bin/typo3 extension:activate nr_http_guard --no-interaction`. Next run the native probe without `prepare`, followed by:

```sh
python3 Build/Fixtures/run-matrix.py --runtime /absolute/new/classic --classic --evidence /absolute/new/classic-evidence
```

Use PHP with curl/curl-multi, PDO SQLite and the usual TYPO3 requirements, Python 3.12 or later, and Docker for the synthetic wire servers. Neither native preparation nor activation invokes Composer, TER upload or a deployment to an existing site. PackageStates, database and caches belong only to the disposable fixture. The Linux native source links point to the verified official Core distribution; the extension is a real ZIP extraction. Existing fixture site directories are refused.

`packaging-summary.json` records the consolidated result: 252 matrix process runs (168 Composer plus 84 classic), 210 matrix wire assertions (140 plus 70), and 234 offline records with unchanged target TCP/HTTP counts. Final ZIP import checks are recorded separately and do not increase the matrix count.

This is automated agent verification. It does not replace the independent human security review G6 or the operator pilot required before production rollout.
