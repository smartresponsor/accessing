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

## Host schema delivery contract

Accessing remains entity-first and does not ship component-owned Doctrine migration classes. Host applications that persist Accessing entities are responsible for producing and applying schema migrations from the installed Accessing metadata before deploying a release that changes persistence.

For the current hardening line, existing hosts must add the following column before running code that persists verification attempts:

- table: `access_verification_challenge`
- column: `attempt_count`
- type: integer
- nullability: not null
- default for existing rows: `0`

A host release is not schema-ready until its migration pipeline verifies that the database schema matches the installed Accessing Doctrine metadata. Fresh test and development databases may continue to be created from metadata through `SchemaTool`; that is not a substitute for a production host migration.

## Passkey schema contract

The M2 persistence foundation adds `access_passkey_credential`. Host applications must generate and apply a migration from the installed Doctrine metadata before enabling passkeys. The table includes:

- a required foreign key to `access` with cascade delete;
- globally unique `credential_id` material;
- `user_handle` and credential `public_key` material stored as transport-safe strings;
- JSON `transports`;
- monotonic integer `sign_count`;
- human-readable credential `name`;
- `created_at`, nullable `last_used_at`, and nullable `revoked_at` lifecycle timestamps.

The M2 ceremony-state slice also adds `access_passkey_challenge` with a unique SHA-256 `challenge_hash`, nullable user ownership for authentication ceremonies, enum-backed purpose, relying-party ID, origin, creation/expiry timestamps, and nullable consumption timestamp. Hosts must index expiry and review uniqueness/index-length support on the selected database platform.

The component still does not ship migration classes. Production hosts must review generated column sizes and index support for their selected database platform before deployment.

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
