# Accessing entity-first migration retirement

This patch retires Accessing schema-first migration sources and keeps the Accessing model as Doctrine entity-first.

## Retired schema-first source

- `Accessing/migrations/**`

## Migration coverage

The retired migrations described these tables/columns:

- `access`
- `access_security_event`
- `access_verification_challenge`
- `access_recovery_code`
- `access_session`
- `access_reset_password_request`
- `access.totp_secret`
- `access.last_sign_in_at`

The current component already has entity coverage for those concepts:

- `AccessEntity`
- `AccessSecurityEventEntity`
- `AccessVerificationChallengeEntity`
- `AccessRecoveryCodeEntity`
- `AccessSessionEntity`
- `AccessResetPasswordRequestEntity`

## Entity-first additions

The patch adds missing repository contracts and concrete repositories for model parts that were entity-first but not repository-first:

- `AccessCredentialRepositoryInterface` / `AccessCredentialRepository`
- `AccessRecoveryCodeRepositoryInterface` / `AccessRecoveryCodeRepository`
- `AccessResetPasswordRequestRepositoryInterface`
- `AccessSecondFactorRepositoryInterface` / `AccessSecondFactorRepository`

Existing entities were linked to their repository classes through Doctrine attributes where they were missing.

## Objecting decision

Accessing security lifecycle fields are intentionally not blindly moved into Objecting embeddables:

- `lastSignInAt`, `lockedUntil`, `emailVerifiedAt`, `phoneVerifiedAt`, `failedLoginCount`, `totpSecret`, `revokedAt`, `confirmedAt`, `consumedAt`, and reset-token expiry fields are business/security lifecycle data.
- Generic audit/state duplication should be avoided in future additions; when Accessing needs platform-wide object identity/audit/state semantics, it should consume Objecting traits rather than create new local generic traits.

## Old monolith reconciliation

`Entity-src(6).zip` contains no old `Entity/Access` monolith. No legacy access-specific relation was available to port. Security/role concepts found in the old monolith were not pulled into Accessing here because current Accessing already owns user roles as a JSON auth concern and broader role/permission entities belong to Accessing/Administering policy work, not this migration-retirement pass.
