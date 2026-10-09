# Review and remediation loop

These records supersede the earlier red assessment snapshots without rewriting
their measurements. Qualification uses TYPO3 13.4.36 and 14.3.8, SVG sanitizer
1.0.0, and the existing three exact SDK tuples.

- [Fresh runtime qualification](qualification/summary.json): four actual Composer
  paths, two genuinely inactive classic ZIP installations with native activation
  and persisted PackageStates, three full 145-test suites, and seven zero-finding
  static analyses. [Source binding](qualification/tested-current-source-binding.json)
  covers the inputs actually executed. The intermediate qualification ZIP is
  distinguished from the final distribution archive.
- [Pre-overwrite qualification guards](qualification-guards.json): new Core/SDK
  pins, widened ranges, mixed tuples, old forced Core versions and classic
  provenance drift are rejected; four actual installed graphs pass.
- [SAST remediation](sast-remediation.json) and
  [parser limits](sast-parser-limitations.json): precise source annotations and
  guarded exclusions for three unchanged synthetic keys. The scanner's partial
  PHP parsing is explicit; no exhaustive PHP security coverage is claimed.
- [Synthetic fixture integrity](synthetic-fixture-integrity.json) and
  [negative cases](synthetic-fixture-mutations.json): exact bytes, identities,
  public keys and chains are checked before the narrow exclusions are used.
- [Anonymous response-cookie remediation](cookie-remediation.md): current cookie
  values are removed; one immutable historical finding is classified narrowly.
  External validity and expiration are not asserted.
- [Renovate policy verification](renovate-policy-verification.json): 100 effective
  policy cases include built-in defaults and the security force layer; 11 path
  cases protect frozen evidence and keep current manifests discoverable. Group
  fields do not guarantee one PR for every update type. Complete qualification
  remains required for every resulting runtime update PR.

Remote checks and the final independent review must still pass for the immutable
published revision before merge. Operator acceptance and independent human
security review remain release gates; this repository is still an alpha.
