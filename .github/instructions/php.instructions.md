---
applyTo: "Classes/**/*.php,Configuration/**/*.php,ext_*.php"
---

# Production PHP

Read `AGENTS.md` and the relevant entry point before editing. Preserve PHP 8.2 compatibility.
Use typed, small APIs and the existing namespaces; the embedded kernel must not read TYPO3 globals or Vault credentials.
Preserve fail-closed policy/API checks, immutable endpoint/profile snapshots, native DNS pinning and one native handle per lease.
Use `NativeOperation` for deliberate warning-producing native I/O and retain handler restoration and exception propagation.
Keep public signatures compatible. Use a structured PHP AST edit with a current source hash for source changes.
Run PHP style and the curated shared PHPStan profiles, then the relevant Unit/Core/native controls.
Changes to compatibility shapes or native transfer construction need real installed dependencies and wire witnesses.
