<!-- SPDX-License-Identifier: CC-BY-SA-4.0 -->
# HTTP Guard policy explorer

Standalone German/English GitHub Pages application for `netresearch/nr-http-guard`.
The language control preserves inputs and selected views. `?lang=de` and
`?lang=en` select a language explicitly; otherwise the browser language is used.

Build and test with Python 3 and Node.js 20 or newer (no package installation):

```sh
python3 Website/build.py
node --test Website/tests/*.test.mjs
```

Serve `.Build/pages/` with a static web server to preview the application.
All asset/module URLs are relative, including on a project Pages subpath.

`build.py` derives the browser corpus from the exact runtime
`Resources/Private/HttpGuard/data/security-corpus/address-rules.json` and includes
the address fixtures from `address-cases.json`. It includes allocated global
IPv6 prefixes, mapped IPv4 normalization and longest-prefix classification.
The generated module records the source hash. Tests compare every repository
address fixture with the JS classifier, in addition to request-policy scenarios.
Generated files stay in ignored `.Build/pages/` and are not committed.

The workflow builds/tests pull requests with read-only permissions and deploys
only main-branch pushes/manual runs. Deployments use the `github-pages`
environment, scoped Pages/OIDC permissions and SHA-pinned actions. GitHub Pages
must have **GitHub Actions** selected as its build source in repository settings.

## Scope and privacy

The explorer is a browser policy model. Its explanatory rule codes are not the
PHP exception reason codes. It does not execute PHP, contact entered targets,
resolve DNS, pin connections or test native/TYPO3 transport behavior. Endpoint
ownership/expiry, redirects, proxy options and transport gates are not simulated.
It conservatively rejects TRACE as well as CONNECT. The optional Core allowlist
supports exact hosts only. It cannot inspect environment-specific routing.

No analytics or browser persistence is used. Inputs remain in memory. Only the
language is reflected in the URL; input values are never put into it. Scenario
import/export and copying are explicit user actions. Network connections from
the application are prohibited by CSP; all fonts/assets are served locally.
GitHub Pages itself serves the public site under GitHub's hosting policies.

nr-vault and nr-llm remain reference implementations only. No adapters,
migrations or modifications to either project are included.

## Assets

Code is MIT-licensed. Logo and branding: Netresearch DTT GmbH, CC-BY-SA-4.0,
subject to the original trademark rules. Raleway and Open Sans use the SIL Open
Font License, reproduced in `assets/OFL.txt`. Source assets are self-hosted.
The Raleway Latin subset has the internal family name `HTTP Guard Display`
to respect its reserved font name; CSS retains the Raleway typography alias.
The explorer source/assets are excluded from the installable extension archive
by the repository's default `export-ignore` boundary.
