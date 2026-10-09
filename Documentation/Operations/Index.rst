.. _operations:

==========
Operations
==========

.. _operations-diagnostics:

Diagnostic commands
===================

All commands output JSON. :literal:`config-check`, :literal:`doctor`
and :literal:`legacy-report` send neither target HTTP nor DNS requests.
:literal:`policy-check` may use DNS but sends no target HTTP. With
:literal:`--no-dns`, that command also remains offline.

.. code-block:: bash
    :caption: Composer project; replace the CLI path for classic projects

    vendor/bin/typo3 http-guard:config-check
    vendor/bin/typo3 http-guard:doctor
    vendor/bin/typo3 http-guard:legacy-report
    vendor/bin/typo3 http-guard:policy-check https://www.example.org --no-dns
    vendor/bin/typo3 http-guard:policy-check https://erp.internal.example:8443 --endpoint erp-orders

.. list-table:: Diagnostics and their meaning
    :header-rows: 1

    * - Command
      - Result
    * - :literal:`config-check`
      - Schema, mode, policy revision, profile count, review and TLS warnings.
    * - :literal:`doctor`
      - Actual versions, registry, cURL, proxy variable names and declared
        coverage. :literal:`protected` applies only in supported enforce
        mode without a proxy conflict.
    * - :literal:`legacy-report`
      - Inventory of existing HTTP contexts, host lists and options.
        Does not create profiles or automatic permissions.
    * - :literal:`policy-check <url>`
      - Current diagnostic evaluation as GET, optionally with a named
        profile. Does not grant a transferable transport permission.

.. list-table:: Exit codes
    :header-rows: 1

    * - Code
      - Meaning
    * - 0
      - Successful check or allowed policy decision.
        :literal:`config-check` alone says nothing about protected sends.
    * - 2
      - Policy denial, unverifiable observation or, for :literal:`doctor`,
        an explicitly unprotected mode.
    * - 3
      - Invalid configuration or registry, or unsupported transport or
        proxy. The policy is not silently disabled.
    * - 4
      - Resolution cannot be verified, for example a DNS host without a
        static entry when using :literal:`policy-check --no-dns`.

The actual JSON payload is authoritative. :literal:`httpSent=false`
means the diagnostic command sent no target HTTP; it does not mean
that an application will subsequently be protected automatically.

.. _operations-rollout:

Rollout in an operator project
=============================

1. Inventory outbound HTTP paths: Core RequestFactory, bound PSR-18
   clients, Vault, external SDKs, direct Guzzle or cURL calls and early
   bootstrap connections. Map each path to its actual integration.
2. Run schema, registry, legacy and transport diagnostics in staging.
   Check Core contexts and existing restrictions.
3. Explicitly select :literal:`observe` if needed. Observations support
   investigation; observed requests do not automatically become permitted
   profiles. This mode provides no enforced pin binding.
4. Justify internal integrations individually. Configure exact origins,
   methods, owners, narrow networks and suitable expiry dates. Bind
   clients to profiles in trusted service code.
5. Enable :literal:`enforce` in a controlled pilot. Test allowed and
   denied targets, redirects, cancellation, OAuth legs and log redaction
   using actual target counters.
6. Approve the policy, package revision, dependency lock and evidence
   together.

The project includes technical evidence and reproducible fixtures.
They do not replace independent human security review or a pilot
with the operator's actual endpoints.

.. _operations-changes:

Changing or revoking policy
==========================

Configuration, registry, engine and clients have an immutable snapshot.
After changes, rebuild TYPO3 system and DI caches and restart long-lived
workers in a controlled manner. Existing clients do not acquire a new
policy solely because a file changed. If an immediate operational stop
is necessary, also terminate the affected worker or network path.
A transfer already running is not retroactively revoked.

:literal:`expiresAt` is rechecked on every new attempt immediately
before starting the native connection. :literal:`reviewAfter` only
produces a warning. Clearing DNS memoization does not replace a
registry or client update.

.. _operations-logging:

Decision logs
=============

The TYPO3 integration uses the :literal:`Netresearch.HttpGuard` logging
channel. The reporter uses event version 1 with timestamp, mode,
decision, fixed reason code, profile ID, policy revision, address class,
scheme, port, resolver source and an internally generated correlation
ID. According to :ref:`configuration-logging`, the host can be omitted,
represented by an HMAC or explicitly logged in clear text.

Bodies, headers, cookies, credentials, queries, complete URLs,
certificate or key paths and request objects are not part of the event
format. The correlation ID is not taken from an arbitrary incoming
header. By default, allowed decisions are not sampled for logging.
Denial events are rate limited per reporter and time window; counters
continue to record every decision with bounded labels. Reporter
counters live in the instance and need a separate metrics integration
for centralized long-term reporting. Logger failures do not change
authorization.

In :literal:`observe` mode, :literal:`would_deny` indicates a detected
policy violation. :literal:`unverifiable` indicates missing verifiable
transport binding or resolution. These events do not prove an
enforced safe connection.

.. _operations-rollback:

Rollback
========

For a deliberate rollback, restore the previous policy, extension and
dependency lock together. Rebuild caches, restart workers and repeat
functional and audit checks. Alternatively, explicitly deploy
:literal:`observe` or :literal:`disabled`. Both reduce the additional
protection and must be identified operationally as unprotected.
:literal:`doctor` reports the deliberately unprotected mode with exit
code 2 unless another invalid capability produces exit code 3.

A high denial rate does not trigger an automatic rollback. Investigate
the cause, affected path, reason code and profile binding first.
Existing Vault controls are not removed by changing the mode.
Verify the project's actual deployment, cache and worker procedures
in the pilot.

.. toctree::
    :maxdepth: 1

    Reasons
