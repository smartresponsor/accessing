# Accessing 0.4.1 Security Bugfix Hardening Milestone

Status: planned
Priority: release blocker
Source: `Accessing.md`, audit dated 2026-07-10

## Objective

Remove dangerous, unverifiable, or non-observable behavior from recovery, administration, destructive demo tooling, authentication auditing, and verification delivery before Accessing advances beyond RC.

## Release rule

P0 patches block release. P1 patches are required to close this milestone. P2 items may move to a follow-up hardening milestone only after active-path safety is proven.

## P0 — immediate blockers

### P0-1 — Replace obfuscated recovery dispatch

- Replace `base64_decode()`, `str_rot13()`, and dynamic property/method dispatch with a direct typed `AccessRecoveryServiceInterface::resetPassword()` call.
- Read `password` directly from the payload and remove `applyAccessEngine()`.
- Preserve the current JSON contract and fail explicitly when the collaborator is unavailable.
- Add success, invalid-code, missing-collaborator, and validation-failure tests.
- Add a guard preventing unexplained obfuscation in Accessing security flows.

Acceptance: PHPStan resolves the call statically and no obfuscated recovery dispatch remains.

### P0-2 — Stop implicit admin password reset

- Remove the hardcoded `admin` password.
- Set credentials only when creating a new administrator.
- Require a secure explicit secret source or hidden interactive input.
- Add explicit `--reset-password` rotation with confirmation.
- Audit administrator creation and intentional reset without storing secrets.
- Test create, ensure-existing, reset, missing-secret, and non-interactive cases.

Acceptance: repeated `accessing:admin:ensure` never changes existing credentials and no default password exists.

### P0-3 — Guard destructive demo reset

- Refuse execution outside `dev` and `test`.
- Require `--force` and interactive confirmation.
- Reject accidental non-interactive invocation.
- Display environment and sanitized database target before confirmation.
- Log attempted, rejected, and completed resets.
- Test production refusal, missing force, declined confirmation, and allowed dev/test execution.

Acceptance: production cannot reach `SchemaTool::dropDatabase()` and accidental invocation exits non-zero without mutation.

## P1 — required bug fixes

### P1-1 — Record locked-account sign-in attempts

- Emit a dedicated typed security event before the locked-user return.
- Distinguish it from normal invalid-credential failures.
- Include only normalized/redacted investigation metadata.
- Test event type, severity, and secret exclusion.

### P1-2 — Handle mail and SMS delivery failure consistently

- Define challenge persistence-versus-delivery lifecycle semantics.
- Catch transport failures at the application boundary.
- Invalidate, remove, or mark delivery-failed challenges so no active orphan remains.
- Add structured logs without raw codes or tokens.
- Return a stable user-safe response.
- Test mail failure, SMS failure, retry behavior, and orphan prevention.

### P1-3 — Consolidate security-event recording

- Retire the free-form `AccessSecurityEventRecorderInterface` path.
- Route registration through `AccessSecurityEventServiceInterface`.
- Use one enum-backed event taxonomy.
- Move request metadata creation into an explicit context factory.
- Normalize persisted `user.registered` values if they exist.
- Update reports, projections, fixtures, filters, and tests.

Acceptance: one security-event write abstraction remains and all event types are consistently queryable.

### P1-4 — Add focused operational logging and redaction

- Add a dedicated Accessing logger/channel to authentication, recovery, verification, 2FA, session, and administrative command boundaries.
- Log failures and degraded states, not secret-bearing payloads.
- Centralize redaction for email, phone, IP, reset codes, TOTP data, recovery codes, tokens, and session identifiers.
- Document retention and PII policy for operational logs and `AccessSecurityEventEntity.context`.

### P1-5 — Harden rate-limit identity derivation

- Centralize rate-limit key construction.
- Honor trusted-proxy configuration.
- Avoid the shared `unknown` client-IP bucket.
- Use a bounded fallback without retaining raw sensitive data.
- Test direct, proxied, missing-IP, and multi-attacker cases.

## P2 — architectural cleanup after active-path safety

### P2-1 — Retire legacy entity-level 2FA fields

Verify production usage, migrate active values to `AccessSecondFactorEntity`, then remove `totpSecret`, `secondFactorEnabled`, fallback behavior, and obsolete schema columns.

### P2-2 — Resolve dormant configuration integration

Move the excluded configuration integration behind an explicit optional boundary or documented feature flag. Missing `config/component/services.yaml` must fail clearly instead of silently registering zero services.

### P2-3 — Remove or relocate unsupported CRUD scaffolding

Verify reachability of the eleven throw-only CRUD services. Remove them from Accessing when CRUD responsibility belongs to Cruding, retaining only real Accessing business-flow services and supported contracts.

### P2-4 — Remove duplicate hashing and repository aliases

Introduce purpose-separated keys for challenge and recovery-code hashing, document rotation behavior, and consolidate `findOneByEmail()` / `findOneByEmailAddress()` behind one canonical repository method.

## Patch order

1. P0-1 typed recovery dispatch.
2. P0-2 admin credential safety.
3. P0-3 destructive reset guard.
4. P1-1 locked-account audit gap.
5. P1-2 delivery consistency.
6. P1-3 event taxonomy consolidation.
