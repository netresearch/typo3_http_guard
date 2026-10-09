# Transport and API contract

The current application-facing API and its complete coverage boundaries are
part of the extension's [API manual](../Documentation/Api/Index.rst).
The operator configuration and capability constraints are part of its
[configuration manual](../Documentation/Configuration/Index.rst) and
[installation manual](../Documentation/Installation/Index.rst).

The internal safety classes remain under the existing `Netresearch\HttpGuard`
namespace and are shipped directly by the single `netresearch/nr-http-guard`
extension. Their former standalone Composer package is no longer required.
The pre-consolidation transport contract is retained in `evidence/design-history/`
to interpret the original source-bound evidence.
