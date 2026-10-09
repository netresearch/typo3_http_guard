# Historical public-document response cookie

The immutable finding at commit `1b16d9acaa4ddae691e06b48a4cd6fef16ec84dc`,
`packages/http-guard/data/security-corpus/sources/alibaba-imds.headers.txt:10`,
is the `cr_token` response cookie captured while downloading the public Alibaba
Cloud metadata documentation. It is not a configured HTTP Guard credential and
is never used by this extension or its tests.

Two new HTTPS requests to that exact public documentation URL, without Cookie or
Authorization headers and without a cookie jar, both returned HTTP 200, the
documented metadata address, and cookies named `help_csrf` and `cr_token` with
the same domain/path attributes. The values were neither recorded nor reused.
[The observation](anonymous-cookie-provenance.json) records only cookie names,
attributes, request conditions, response status and document hashes.

All Set-Cookie values in the current Alibaba, Microsoft and RFC response header
captures are now `[REDACTED]`. The HTML/RFC body originals and source hashes
remain unchanged. The source index records the sanitized header hashes;
[the Alibaba remediation record](cookie-sanitization.json) and
[other header records](additional-header-sanitization.json) preserve before/after
hashes without preserving values. A CI preflight rejects any future unredacted
public-document response cookie.

The precise historical finding is accepted as retired anonymous document-session
state. This is not an assertion that the old value expired or that the provider's
external session validity was tested. No account credentials were supplied to
either the original public document capture or the repeat observations; no old
value was sent to the provider. The ignore applies only to this immutable
commit/path/rule/line fingerprint. No cookie name, file, rule, future capture or
new commit is generally excluded, and Git history is not rewritten.
