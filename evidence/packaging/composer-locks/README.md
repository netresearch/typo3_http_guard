# Historical Composer records

These immutable records describe the original TYPO3 13.4.35 / 14.3.7 qualification, including the then-resolved SVG sanitizer 0.22.0. They are evidence of a past run, not dependency inputs for the active extension.

All eight original manifests and lock files are preserved byte-for-byte in `composer-records-13.4.35-14.3.7.zip` (SHA256 `18545fc2c1c5e3d64084005eee1de4f0463bbbe31cb2c308410ffdbe3e95b27e`). ZIP members retain each `core13g7`, `core13g8`, `core14g7` and `core14g8` directory and their original `composer.json` / `composer.lock` names. For example, the old `core13g7/composer.lock` path is now that ZIP member. The adjacent manifest lists the original repository path, member path and original SHA256.

Historical hash logs and reports continue to refer to those original paths. The archive mapping resolves them without editing past results or rewriting Git history. Keeping recorded manifests inside this explicit archive also prevents automated dependency discovery from treating these twelve historical graphs as active dependency manifests.

Active exact fixtures are in `Build/Fixtures/*/composer.json`; `Build/Fixtures/prepare.py` never installs these archived lock files.
