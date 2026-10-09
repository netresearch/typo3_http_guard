.. _operations-reasons:

=======================
Understanding denials
=======================

Reason codes are fixed technical values. This mapping guides
investigation; it does not permit an automatic replacement transport.

.. list-table:: PolicyException codes
    :header-rows: 1
    :widths: 25 38 37

    * - Code
      - Trigger
      - Next check
    * - :literal:`invalid_target`
      - Invalid URI, forbidden raw syntax, userinfo or fragment.
      - Inspect the original string before PSR-7 construction.
    * - :literal:`scheme_forbidden`
      - Scheme other than HTTP or HTTPS.
      - Correct the actual application path.
    * - :literal:`authority_mismatch`
      - Host header does not match the canonical URI authority.
      - Remove host or port overrides from the calling code.
    * - :literal:`address_forbidden`
      - Forbidden address class, operator restriction or candidate outside
        a bound network.
      - Check the complete IP list and exact profile.
    * - :literal:`resolution_unverified`
      - DNS incomplete, invalid, empty, unreachable or unverifiable;
        no static entry during offline diagnostics.
      - Check DNS server configuration and static host data.
    * - :literal:`resolution_limit`
      - Address or CNAME limit exceeded, or a cycle.
      - Fix the DNS chain; do not truncate candidates.
    * - :literal:`endpoint_mismatch`
      - Origin or method differs from the bound profile.
      - Compare service binding, target port and method.
    * - :literal:`grant_invalid`
      - Unknown, foreign or expired binding.
      - Check profile validity and recreate the client correctly.
    * - :literal:`transport_unsupported`
      - Missing cURL or cURL-multi, unknown SDK tuple or unqualified
        transport capability.
      - Check :literal:`doctor`, exact versions and PHP functions.
    * - :literal:`proxy_unsupported`
      - Explicit proxy option or actual proxy process variable.
      - Review the deployment environment and proxy needs with the operator.
    * - :literal:`option_forbidden`
      - Unknown or uncontrolled SDK or transport option.
      - Check RequestFactory defaults and request options.
    * - :literal:`redirect_forbidden`
      - Forbidden redirect origin, downgrade or invalid redirect option.
      - Inspect the actual Location and profile rules.
    * - :literal:`configuration_invalid`
      - Schema error, inconsistent registry or middleware position.
      - Check :literal:`config-check`, :literal:`doctor` and service wiring;
        correct the invalid deployment configuration.

Internal DNS errors may be summarized as
:literal:`resolution_unverified` at the resolver boundary. A reason
code is not a complete network diagnostic report and deliberately
contains no secrets.
