# AP-01 source findings (2026-10-08)

Source inspection is not runtime gate completion. Runtime evidence is stored separately.

## Checked snapshots

- TYPO3 14.3 branch: `cfa1b1cbf3022c19ba231731da397f5847f3c29a`, matching original specification S01/S13.
- TYPO3 13.4 branch: `de903a2e44a0b97177a67ec76d032398ca8ef735`.
- Guzzle 8.2.0 release: `93939470950a9b11e2e84204166ef5e048c55fe4`, matching original specification S12/S14.
- Guzzle 7.15.5 release: `ee80339fd9177ba44c49cdb653ff02a4d1106b9a`.
- Runtime fixtures intentionally use Composer published TYPO3 13.4.35 / 14.3.7, separately locked with Guzzle 7.15.5 / 8.2.0.

## Integration

The default `HandlerStack::create()` layers http_errors, allow_redirects, cookies and prepare_body. TYPO3 then appends its context `typo3_allowed_hosts` and configured HTTP.handler array entries. Array order is request order; response execution is reversed. A terminal custom middleware can replace the chosen leaf without replacing those preceding layers. The first custom boundary sees final responses after all enclosed project response middleware, before Guzzle redirect processing.

Sources:
- https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/Classes/Http/Client/GuzzleClientFactory.php
- https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/HandlerStack.php
- https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/ExtensionArchitecture/HowTo/RestRequests/Index.html

`BootCompletedEvent` is a documented real hook in both versions. Bootstrap dispatches it after extension configuration loading. Unlike config/system/additional.php (loaded before extension localconf), it can register the two guard positions after ordinary extension configuration. Other event listeners or later configuration can still mutate the array; per-request diagnostics must reject invalid state. Calls before guard registration are outside this integration gate and require explicit coverage documentation.

Sources:
- https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/Events/Events/Core/Core/BootCompletedEvent.html
- https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/ApiOverview/Events/Events/Core/Core/BootCompletedEvent.html
- https://github.com/TYPO3/typo3/blob/de903a2e44a0b97177a67ec76d032398ca8ef735/typo3/sysext/core/Classes/Core/Bootstrap.php
- https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/Classes/Configuration/ConfigurationManager.php

## Transport and option adapter pitfalls

- `Utils::chooseHandler()` can use `StreamHandler` for stream=true despite cURL being installed; the terminal adapter must own its cURL leaf.
- Guzzle8.2 CurlFactory allows internal CURLOPT_RESOLVE, FRESH_CONNECT, FORBID_REUSE. Guzzle-managed URL, PORT, PROXY, FOLLOWLOCATION and other keys are conflicts. Generate only version-supported internal options.
- A new CurlMultiHandler with transport_sharing=none per attempt avoids independent transfer shared DNS/multi connection caches. Guzzle8 provides explicit close(); Guzzle7 uses cancellation and handler lifetime/destruction, so cleanup must be proven separately.
- Middleware options include client configuration (including its handler), redirect internals and Guzzle8 PSR factory defaults. Do not deny legitimate internally produced entries by blindly rejecting all handler/underscore keys.
- Guzzle8 rejects caller request-level handler overrides before middleware; Guzzle7 can bypass middleware through that option. Coverage must be accurately stated.
- Guzzle7 builds raw cURL auth keys for digest/NTLM before middleware. Inventory them and preserve supported authentication semantics without permitting foreign route options. Guzzle8 authentication handling differs.
- RedirectMiddleware captures its request options outside the custom boundary. Reject a too-large follow limit; a later inner clamp would not change the outer closure.
- PSR-18 sendRequest() disables following redirects and HTTP error exceptions.
- CurlFactory can retry failed body rewind internally. Production must suppress or route hidden replays back through fresh authorization; external _curl_retries remains forbidden.
- `CURLOPT_RESOLVE` is a host+port DNS pin; multiple addresses require libcurl >=7.59.0 and IPv6 addresses are bracketed. A pin does not constrain proxy-side resolution, foreign handlers, or previously pooled connections.
- An empty CURLOPT_PROXY disables environment inheritance, but recognized operator proxy requirements must be rejected before constructing this explicit-direct path.
- on_stats sees primary_ip after transfer and is diagnostic, not preventive.

Sources:
- https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/Utils.php
- https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/Handler/CurlFactory.php
- https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/Handler/CurlMultiHandler.php
- https://github.com/guzzle/guzzle/blob/ee80339fd9177ba44c49cdb653ff02a4d1106b9a/src/Handler/CurlMultiHandler.php
- https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/Client.php
- https://github.com/guzzle/guzzle/blob/ee80339fd9177ba44c49cdb653ff02a4d1106b9a/src/Client.php
- https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/RedirectMiddleware.php
- https://curl.se/libcurl/c/CURLOPT_RESOLVE.html
- https://curl.se/libcurl/c/CURLOPT_CONNECT_TO.html
- https://curl.se/libcurl/c/CURLOPT_PROXY.html

## Composer preparation limitation

All four initial installs were blocked by enshrined/svg-sanitize ~0.22 advisories PKSA-8j6w-3kr7-s9xk, PKSA-6j95-1jtb-x6wc and PKSA-cbx2-m9db-bmzd inherited from the specified TYPO3 releases. The disposable fixtures list these exact advisory IDs as scoped ignores. They are never used for SVG processing; this is not a safe production dependency claim and these ignores must not be copied into deliverables.
