# Accessing 0.5.0 Market-Gap Closure Milestone

Status: M0 and M1 complete; M2 next
Priority: growth track after 0.4.1 RC blockers
Boundary owner: Accessing

## Boundary

Accessing owns registration, authentication, credentials, second factor, verification, recovery, throttling, sessions, and security events. Authorization, roles, permissions, voters, and policy catalogs remain exclusively in Rolling.

Enterprise SAML and SCIM are separately gated future scope and are not part of this milestone.

## Execution order

### M0 — finish 0.4.1 security hardening

- Validate and complete `accessing-0.4.1-security-bugfix-hardening.md`.
- Preserve the current dirty tree; no reset, checkout, clean, overwrite, or revert.
- Close typed recovery dispatch, administrator credential safety, and destructive reset guards.
- Run lint, CS, PHPStan, unit, integration, and functional gates.
- Record pre-existing gate debt separately from regressions introduced by this milestone.

Exit: all release-blocking security paths are deterministic, tested, and observable.

### M1 — compromised-password detection

- Add a privacy-preserving provider contract that transmits only a SHA-1 prefix and evaluates suffix counts locally.
- Enforce the check before registration, password change, API recovery reset, and token-based reset.
- Use stable outcomes: `safe`, `compromised`, and `unavailable`.
- Reject compromised passwords with `password_compromised`.
- Reject provider outages with `password_safety_unavailable`; never silently downgrade security.
- Never log, persist, or transmit the plaintext password or complete hash.
- Add provider, credential-service, API, and UI-flow tests.

Exit: every product password mutation uses one Symfony-oriented credential service and one stable failure taxonomy.

### M2 — Passkeys/WebAuthn

- Add WebAuthn credential persistence and migrations under `App\Accessing\`.
- Implement registration options, attestation verification, authentication options, assertion verification, credential naming, revocation, and sign-counter handling.
- Integrate passkeys with registration, sign-in, second factor, recovery, API, Twig UI, host configuration, and security events.
- Add browser capability fallbacks, focused unit/integration/functional tests, and host installation documentation.

Exit: a host application can enable, enroll, authenticate, recover, and revoke passkeys without custom Accessing patches.

### M3 — social login

- Implement Google first, then GitHub and Apple.
- Persist provider subject identifiers separately from email addresses.
- Require verified provider email where applicable.
- Protect account linking with an authenticated local session or fresh credential/passkey proof.
- Reject silent linking by matching email alone.
- Keep provider identity in Accessing; do not assign roles or authorization policies.
- Add API/UI callbacks, state/nonce/PKCE validation, tests, and host integration docs.

Exit: social sign-in and deliberate account linking are safe and authorization-neutral.

### M4 — adaptive authentication and Administering export

- Add explicit signals for device continuity, IP/network change, velocity, repeated failures, and recovery-sensitive actions.
- Produce deterministic risk decisions that request stronger authentication; do not make authorization decisions.
- Define a versioned Accessing-to-Administering security-event export DTO/contract with redaction, idempotency, ordering, and failure semantics.
- Keep Accessing as source of truth for authentication events and Administering as an optional downstream governance consumer.

Exit: hosts can apply step-up authentication and export security events without coupling Accessing to authorization or governance internals.

## Safeguards

- Namespace is only `App\Accessing\`.
- No `Domain` directory and no Ports/Adapters naming or layout.
- No generic CRUD controllers.
- No roles, permissions, voters, or authorization catalogs in Accessing.
- `src/Catalog` may be removed only when empty and untracked; it must never become an authorization catalog.
- No commits or pushes during milestone execution.

## Gate matrix

- PHP syntax and Composer validation.
- PHP-CS-Fixer check limited to intentional changes before broader repository cleanup.
- PHPStan at repository level.
- Unit tests for provider semantics and all failure outcomes.
- Integration tests for registration and recovery mutation paths.
- Functional/API tests for stable error codes and UI feedback.
- Dependency audit and migration validation before M2/M3 release.

## Repository assessment — 2026-07-11

This assessment is based on the current dirty working tree and does not assume that uncommitted changes are released. The current tree remains authoritative and must not be reset, reverted, cleaned, or overwritten.

### Boundary conformance

Implemented inside the documented Accessing boundary:

- registration and credential creation;
- password sign-in and lock/rate-limit handling;
- email and phone verification challenge flows;
- TOTP enrollment, verification, recovery codes, and second-factor rate limiting;
- password recovery and password mutation;
- access sessions and security-event recording;
- API, Twig/service surfaces, CLI commands, fixtures, and focused tests for these flows.

Confirmed boundary separation:

- no role, permission, voter, policy-engine, or authorization catalogue belongs in Accessing;
- authorization remains a Rolling responsibility;
- organization/tenant administration, billing identity, legal consent storage, KYC/AML, and notification-centre ownership remain outside this component;
- `src/Catalog` must not be repurposed into a role or permission catalogue.

### Current implementation status

#### M0 — security hardening: complete in the current working tree

Present in the current tree:

- direct typed password-recovery dispatch replaces the previously obfuscated dynamic call;
- administrator password handling is configuration-backed rather than a hardcoded `admin` reset path;
- destructive demo-reset safeguards and related tests are present;
- second-factor verification consumes a dedicated limiter and records limiter exhaustion;
- verification challenge attempts are persisted through an ORM-mapped `attempt_count` field and expose an explicit attempt limit;
- registration uses the enum-backed security-event service and the canonical `user_registered` taxonomy;
- empty TOTP secrets fail closed with `LogicException` instead of falling back to a shared default secret;
- email/phone delivery failures terminalize challenges and expose a stable `AccessNotificationDeliveryException` without leaking provider errors;
- sign-up, recovery, forgotten-password, second-factor, and verification-resend limiters are wired through Symfony DI.

M0 release verification completed:

- Composer strict validation and lock consistency pass;
- dependency audit reports no known vulnerability advisories;
- lint, PHP-CS-Fixer, PHPStan, unit, integration, and functional contours pass;
- the host-owned schema contract documents `access_verification_challenge.attempt_count` as non-null integer with an existing-row default of `0`;
- production reset tokens and verification/recovery codes are excluded from flash output;
- all active limiter surfaces are exercised through their API or HTML entry paths.

#### M1 — compromised-password defence: complete in the current working tree

Present in the current tree:

- `AccessCompromisedPasswordProviderInterface` defines the provider boundary;
- `AccessSymfonyCompromisedPasswordProvider` uses Symfony `NotCompromisedPassword` and maps results to `safe`, `compromised`, or `unavailable`;
- provider exceptions fail closed as `unavailable`;
- `AccessCredentialService` enforces the check before credential creation and password changes;
- registration and recovery API flows expose stable `password_compromised` and `password_safety_unavailable` outcomes;
- focused unit tests cover compromised, unavailable, and safe outcomes.

M1 acceptance verification completed:

- every password mutation path delegates to `AccessCredentialService`, including registration, authenticated change, token reset, typed recovery, API recovery, admin command, admin fixture, demo fixture, and session/recovery fixture;
- API surfaces expose stable `password_compromised` and `password_safety_unavailable` codes;
- HTML registration, password-change, token-reset, and recovery-reset surfaces return controlled feedback rather than server errors;
- the provider maps only `safe`, `compromised`, and `unavailable`, fails closed, and does not propagate provider exception details;
- plaintext passwords, complete hashes, provider payloads, and reset tokens are not logged or persisted by Accessing;
- privacy and outbound-service availability requirements are documented for host applications;
- dependency audit and the complete local pipeline pass.

#### M2 — Passkeys/WebAuthn: persistence foundation implemented; ceremonies pending

The current tree now contains a provider-neutral passkey credential entity, Doctrine repository, lifecycle service, relying-party configuration validation, sign-counter and revocation invariants, typed security-event taxonomy, focused unit coverage, and a host-owned schema contract. Registration/authentication ceremony verification, API/Twig surfaces, browser integration, and third-party WebAuthn library selection remain pending.

Required delivery slices:

1. **Implemented foundation:** credential persistence metadata, repository contract, credential naming, revocation, duplicate-credential protection, sign-counter handling, RP origin validation, event taxonomy, unit tests, and host schema documentation;
2. registration-option and attestation verification services;
3. authentication-option and assertion verification services;
4. challenge persistence, expiry, one-time consumption, origin/RP binding, and replay controls;
5. integration with sign-in, second factor, recovery, API, Twig UI, and security events;
6. host configuration documentation, WebAuthn library selection, and browser-capability fallback;
7. integration, functional, and browser tests.

#### M3 — social identity: not implemented

No Google/GitHub/Apple OAuth client, provider-subject persistence, callback/state/nonce/PKCE flow, or deliberate account-linking surface was found.

The first releasable slice is Google only. GitHub and Apple follow after the linking contract is proven. Email equality must never be sufficient for silent linking; linking requires an authenticated local session or fresh credential/passkey proof.

#### M4 — adaptive authentication and security-event export: not implemented

The current tree records security events and applies deterministic limiters, but it has no explicit device continuity model, network-change signal, velocity analysis, risk decision object, step-up decision service, or versioned Accessing-to-Administering export contract.

Accessing must remain the source of truth for authentication events. Administering may consume a redacted, versioned export, but must not become a synchronous dependency of authentication.

### Revised release sequencing

1. **0.4.1** — complete M0 gates, persistence verification, secret policy, and release-blocking hardening.
2. **0.5.0** — complete and release M1 compromised-password defence across every password mutation path.
3. **0.6.0** — deliver M2 passkeys/WebAuthn as an independently operable host feature.
4. **0.7.0** — deliver M3 Google social sign-in and safe linking; add GitHub/Apple only after the provider-neutral contract is stable.
5. **0.8.0** — deliver M4 deterministic adaptive authentication and optional Administering export.
6. **Later evaluation** — push-based second factor may be assessed only after passkeys, social identity, and adaptive step-up are stable; enterprise SAML/SCIM remains separately gated scope.

### Cross-cutting risks

- The working tree contains extensive uncommitted security changes; release conclusions must be based on gate results, not file presence.
- WebAuthn and social identity introduce new persistence, replay, origin, redirect, nonce, and account-linking risks that require threat-modelled acceptance tests.
- Compromised-password checking is an external availability dependency and intentionally fails closed; hosts need explicit operational documentation.
- Authentication event export must be asynchronous or failure-isolated so governance outages cannot block sign-in or recovery.
- Generic CRUD surfaces over access accounts remain a boundary and security risk and must not expand as part of market-gap work.

### Definition of milestone completion

This milestone is complete only when:

- M0 and M1 pass lint, CS, PHPStan, unit, integration, functional, migration, and dependency-audit gates;
- M2, M3, and M4 each ship with persistence, API/UI integration, stable failure semantics, security events, host documentation, and focused acceptance tests;
- no authorization concepts migrate from Rolling into Accessing;
- every externally visible capability is installable by a host without repository-specific patches;
- the milestone document and market analysis are updated to reflect released code rather than planned or dirty-tree-only implementation.
