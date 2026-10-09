.. _api:

=============
Using clients
=============

The public interfaces use the :php:`Netresearch\HttpGuard` namespace and
are provided through TYPO3 services. Applications should inject bound
PSR-18 clients or Public Fetch. The internal Guzzle client and its
progress and cancellation driver belong together; they do not provide
an additional public credential API.

.. _api-core:

Ordinary Core requests
======================

The registered Core path uses the public default policy. Existing
supported Core middleware and context restrictions remain effective
in addition. An ordinary call can look like this:

.. code-block:: php
    :caption: Public retrieval with TYPO3 RequestFactory

    $response = $requestFactory->request(
        'https://www.example.org/document.json',
        'GET',
        ['timeout' => 10, 'http_errors' => false],
    );

:php:`$requestFactory` is an injected
:php:`TYPO3\CMS\Core\Http\RequestFactory`. For public content from
untrusted URLs, :ref:`api-public-fetch` provides a narrower API.
Configuring an endpoint profile does not grant internal access to
RequestFactory requests.

.. _api-endpoint:

Bound integration clients
=========================

.. php:class:: Netresearch\HttpGuard\EndpointClientFactoryInterface

    .. php:method:: forEndpoint(string $configuredEndpointId)
        :returntype: Psr\Http\Client\ClientInterface

        Creates a client bound to a configured endpoint profile.
        Unknown, foreign or expired bindings are rejected.

The profile ID belongs in trusted service code or dependency injection
configuration. It is not taken from the current user request. The
following example uses the profile from :ref:`configuration-example`:

.. literalinclude:: _ErpClient.php
    :language: php
    :caption: Classes/Service/ErpClient.php in a sitepackage

The client checks the origin, method and all resolved addresses on
every send. PSR-18 :php:`sendRequest()` does not follow redirects and
returns HTTP error responses such as 404 or 500 as response objects.
Network errors and policy denials remain exceptions. Never bypass a
policy error with an unprotected replacement client.

.. _api-public-fetch:

Public Fetch
============

.. php:class:: Netresearch\HttpGuard\PublicFetchClientInterface

    .. php:method:: fetch(Psr\Http\Message\UriInterface $uri, string $method = 'GET')
        :returntype: Psr\Http\Message\ResponseInterface

        Accepts only GET or HEAD and creates its own public request
        without inherited credentials or a body.

Public Fetch does not inherit authentication, cookies, client certificates,
SSL keys, custom headers, bodies or query defaults from another client.
Its fixed header list consists of :literal:`Accept`,
:literal:`Accept-Encoding` and :literal:`User-Agent`. The query of an
accepted target URI remains part of that URI and is not logged.
Permitted public redirects can reach another origin without forwarding
the secrets of an original integration request.

.. literalinclude:: _PublicDocument.php
    :language: php
    :caption: Validate the raw URL before constructing a PSR-7 object

No endpoint ID is accepted for a user-driven fetch. Private targets
remain blocked even when the project has an internal endpoint profile
with the same hostname.

.. _api-raw-uri:

Raw URLs and PSR-7
=================

:php:`TargetNormalizer::assertRawUri(string $uri)` checks forbidden syntax
while the original string is still available. Reject fragments,
including an empty :literal:`#`, backslashes, control characters and
invalid raw forms before constructing a URI object.

A PSR-7 URI object may already have discarded an empty fragment marker
or encoded input. A later PSR-18 client cannot reconstruct that
information. The TYPO3 RequestFactory adapter therefore checks raw
strings before Guzzle. Custom PSR-18 applications validate the original
URL themselves, as shown in the Public Fetch example. The same boundary
applies to raw Vault resource URLs before constructing a
RequestInterface object.

.. _api-errors:

Handling policy errors
======================

:php:`PolicyException` implements
:php:`OutboundPolicyExceptionInterface`. Its :php:`reasonCode()` method
returns a stable code from :ref:`operations-reasons`. Exception messages
contain no URLs, credentials or request bodies. Applications can use
the code for an understandable error message; do not include secrets
or complete requests in supplementary error logs.

.. _api-sdk-options:

SDK options and streaming
=========================

Ordinary request bodies, headers and supported authentication remain
available in the controlled RequestFactory and SDK path.
:literal:`timeout` and :literal:`connect_timeout` accept finite,
non-negative numbers, including zero. :literal:`verify` accepts a
boolean or a readable CA bundle, subject to the TLS policy. Readable
client certificates and SSL keys enable mTLS. :literal:`sink` can
write to a file path, resource or PSR-7 stream without creating an
additional response copy inside the guard.

Supported callbacks such as :literal:`on_headers`, :literal:`on_stats`
and :literal:`progress` remain available; exceptions terminate the
associated transfer. HTTP 1.0, 1.1 and 2 are qualified; HTTP/3 is not.
The IP-family option can select only :literal:`v4` or :literal:`v6`
and does not expand address permissions.

Raw :literal:`curl` or cURL-multi options, :literal:`stream_context`,
Unix sockets, proxy routes, custom handlers, transport sharing,
delayed transport sends, debug dumps, unknown options and
:literal:`stream=true` are rejected in the protected path. A response
sink file does not provide an alternative stream HTTP handler.
Guzzle-internal Digest and NTLM control options, inspected at source
level for Guzzle 7, are distinguished from arbitrary raw cURL options.

Cancellation and incremental streaming require an adapter that uses
the associated internal progress driver. The nr-vault adapter does
this for its existing APIs. Returning an arbitrary Guzzle client
alone does not guarantee that lifecycle binding.

.. _api-vault:

Optional nr-vault migration
==========================

The extension does not read Vault secrets or automatically integrate
Vault. The separate adapter patch targets the recorded nr-vault
source revision and must be integrated deliberately. Resource and
OAuth token origins require their own bound clients and, where
needed, their own profiles. A private token origin does not inherit
a public resource permission, or vice versa.

Secret retrieval, auditing, masking, size limits and Vault's existing
cancellation and streaming semantics remain the adapter's responsibility.
The migration and patch instructions are in the optional source
package under :file:`integrations/nr-vault/`. They are not required
for ordinary TYPO3 RequestFactory protection.
