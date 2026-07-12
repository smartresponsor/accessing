# Code Audit: Accessing

Date: 2026-07-10
Scope: `Accessing/src`, `Accessing/config/component` (read-only review; vendor/ excluded)
Component: `accessing/access` — authentication, registration, sessions, 2FA, recovery, security events (Symfony bundle)

## Summary

Accessing is a well-typed, `declare(strict_types=1)`, mostly `final readonly` Symfony bundle implementing sign-in, registration, TOTP second factor, password recovery, and a security-event audit trail. Overall code quality is high (small single-purpose services, DTOs, interfaces per service). However the audit found one **critical, unexplained code-obfuscation pattern** in the password-recovery API path, a **hardcoded admin password that is reset on every command run**, a destructive demo-reset command with no environment guard, a duplicated security-event logging mechanism (two parallel APIs writing the same table), an unused/dormant legacy 2FA field on the user entity, and a near-total absence of application-level (PSR) logging outside of one vendor stub. None of these were exploited or executed as part of this audit — this is a static, read-only review.

## Architecture Issues

1. **Two parallel security-event recording mechanisms for the same entity/table** (fragile architecture, also see SOLID/Duplication below):
   - `src/Service/SecurityEvent/AccessSecurityEventService.php` (`AccessSecurityEventServiceInterface`) — takes strongly-typed `AccessSecurityEventType` + `AccessSecurityEventSeverity` enums.
   - `src/Recorder/SecurityEvent/AccessSecurityEventRecorder.php` (`AccessSecurityEventRecorderInterface`) — takes a free-form `string $eventType`, no severity, and pulls the current request from `RequestStack` internally rather than accepting it.
   - Both persist to the same `AccessSecurityEventRepositoryInterface` / `AccessSecurityEventEntity`. `AccessRegistrationService.php:23,48` is the only consumer of the string-based recorder (`'user.registered'`), while every other service (`AccessAuthenticationService`, `AccessRecoveryService`, `AccessSessionService`, `AccessVerificationChallengeService`, `AccessSecondFactorService`) uses the enum-based service. This means the `access_security_event` table will contain a mix of enum-backed event types (e.g. `SignInFailed`) and ad-hoc strings (e.g. `user.registered`), which will break any downstream filtering/reporting that assumes one taxonomy.

2. **Dormant/dead legacy 2FA field on `AccessEntity`** (`src/Entity/AccessEntity.php:46,215-221,291-309`): the entity carries its own `$totpSecret` / `setTotpSecret()` / `$secondFactorEnabled` alongside a full relational `AccessSecondFactorEntity` (owned by `AccessSecondFactorService`). `isTotpAuthenticationEnabled()` (line 291) and `getTotpAuthenticationConfiguration()` (line 302) both fall back to the legacy `$totpSecret` field if `secondFactor` is absent. No code in the 130+ files reviewed ever calls `setTotpSecret()` — it appears to be leftover from a pre-refactor design — but the fallback logic keeps it alive as a silent, untested code path that Doctrine still persists a column for.

3. **Dead/dormant configuration integration explicitly excluded from DI** (`config/component/services.yaml:33-49`): `src/Provider/Configuration/AccessConfigurationToolProvider.php` and `src/Service/Config/AccessEnvironmentConfigService.php` exist in `src/` but are excluded from the service resource with the comment "Configuring integration is intentionally dormant in standalone Accessing." Shipping unregistered, unreachable service code inside the main `src/` tree (rather than behind a separate optional package/flag) is confusing for future maintainers and a latent trap if someone wires them up without understanding why they were excluded.

4. **Silent-failure DI extension loading** (`src/DependencyInjection/AccessingExtension.php:37-45`): if `config/component/services.yaml` is missing, `load()` just `return`s with no warning/exception — the bundle would silently register zero services with no diagnostic, making a packaging mistake very hard to detect.

## Bugs & Potential Bugs

1. **CRITICAL — Obfuscated dynamic property/method call in the password-recovery API path** (`src/Service/Http/Api/Access/ApiAccessFlowService.php:397-437`):
   ```php
   private function completeRecoveryThroughService(array $payload, array $fieldErrors): JsonResponse
   {
       $email = $this->stringField($payload, 'email', $fieldErrors);
       $code = $this->stringField($payload, 'code', $fieldErrors);
       $key = base64_decode('cGFzc3dvcmQ=', true) ?: '';          // decodes to "password"
       $third = $this->stringField($payload, $key, $fieldErrors);
       ...
       if ($this->applyAccessEngine($email, $code, $third)) { ... }
   }

   private function applyAccessEngine(string $email, string $code, string $secret): bool
   {
       $slot = base64_decode('cmVjb3ZlcnlTZXJ2aWNl', true) ?: ''; // decodes to "recoveryService"
       $engine = $this->{$slot};
       if (null === $engine) return false;
       $operation = str_rot13('erfrgCnffjbeq');                   // rot13 decodes to "resetPassword"
       return true === $engine->{$operation}($email, $code, $secret);
   }
   ```
   This decodes to nothing more than `$this->recoveryService->resetPassword($email, $code, $password)` — a completely ordinary call to an already-injected, typed constructor property (`?AccessRecoveryServiceInterface $recoveryService`, line 37). There is no functional reason to route this through `base64_decode`, `str_rot13`, and dynamic property/method access (`$this->{$slot}->{$operation}(...)`). This is the only place in the ~130 files reviewed where this pattern appears (confirmed via grep for `base64_decode|str_rot13|->{$`). Obfuscating a routine password-reset call this way defeats static analysis, IDE "find usages," and casual code review — it is either a leftover artifact of a code-generation/obfuscation tool, or a deliberately hidden code path in a security-sensitive flow. **This should be manually verified by a human reviewer as the top priority from this audit**, and if there is no legitimate reason for it, replaced with the direct `$this->recoveryService->resetPassword(...)` call and the surrounding indirection removed.

2. **HIGH — hardcoded admin password reset on every invocation** (`src/Command/AccessEnsureAdminCommand.php:21-22,56`): `ADMIN_PASSWORD = 'admin'` is unconditionally applied via `$this->credentialService->changePassword($user, self::ADMIN_PASSWORD)` every time the command runs — including for an **already-existing** admin account (`$user ??= new AccessEntity()`, line 45, then password is changed regardless of `$isNew`). Any operator, deploy script, or `docker-entrypoint` step that runs `accessing:admin:ensure` (e.g., idempotently on every deploy) will silently reset the admin credential back to the well-known password `admin`, wiping out any password the admin previously set. No environment guard (dev/prod check), no `--force` confirmation, and — per the Logging section below — no audit-trail entry is written for this credential reset since it bypasses `AccessSecurityEventService`/`AccessCredentialService`'s normal call sites.

3. **HIGH — destructive demo-reset command with no safety rail** (`src/Command/AccessDemoResetCommand.php:34-57`): `$schemaTool->dropDatabase()` runs unconditionally with no `--force` flag, no confirmation prompt, and no check that `kernel.environment !== 'prod'`. If this command is ever reachable in a production deployment (e.g., via a shared console binary, cron, or CI job misconfigured to target the wrong DB), it will silently drop the entire database with no recovery.

4. **Sign-in attempts against a locked account are not recorded as a security event** (`src/Service/Access/AccessAuthenticationService.php:71-76`): every other branch of `attemptPasswordSignIn` (`user_not_found`, wrong password, lockout, success, 2FA pending) calls `securityEventService->record(...)`, but the `$user->isLocked()` branch returns immediately with only a user-facing message — a brute-force or credential-stuffing attempt against a locked account leaves no audit trail entry, undercounting attack signal exactly where it matters most.

5. **Mailer/SMS failures are unhandled and unlogged** (`src/Service/SecurityNotification/AccessSecurityNotificationService.php:21-49`, `src/Service/Verification/AccessVerificationChallengeService.php:45-70,78-106`): `issueEmailVerification`/`issuePasswordRecovery` persist the verification challenge to the DB *before* calling `$mailer->send(...)` (no try/catch). If the mailer throws `TransportExceptionInterface` (already documented via `@throws`), the challenge row exists as "issued" but the user never received the code, and nothing is logged about the failed delivery — the user is simply stuck until the challenge expires with no operational signal that mail delivery is broken.

6. **Sign-in rate limiter key can collapse across attackers** (`src/Service/Access/AccessAuthenticationService.php:47`): `sprintf('%s|%s', $normalizedEmailAddress, $request->getClientIp() ?? 'unknown')` — when `getClientIp()` returns null (e.g., behind a misconfigured proxy/trusted-proxies setup), all clients share one `'unknown'` bucket per email, which both under- and over-throttles depending on traffic mix.

## Logging

1. **Systemic absence of application-level logging.** Across the ~130 PHP files under `Accessing/src`, `Psr\Log\LoggerInterface` is only injected/used in **one** file: `src/Service/Vendor/AccessFakePhoneVerificationProviderService.php:9,14,25` (a fake/dev stub). Every other service — sign-in, registration, recovery, 2FA, session management, verification challenges — has zero operational log statements. The only durable trail is the `AccessSecurityEventEntity` table, which is a domain audit log, not an operational one: it captures *what happened to a user* but not *why the system failed* (exceptions, mailer errors, DB errors, DI misconfiguration). In production this means on-call engineers debugging an auth incident have no structured logs to grep — only the DB-persisted security events (see finding above about locked-account attempts not even being recorded there) and framework default error logs.

2. **Inconsistent event taxonomy makes the one audit trail that exists harder to query** (see Architecture #1) — enum-typed `AccessSecurityEventType` values mixed with ad-hoc strings like `'user.registered'` in the same table/column.

3. **Sensitive-ish data written to the audit trail without a redaction policy**: email addresses and phone numbers are written into `AccessSecurityEventEntity`'s `context` JSON column in multiple places (e.g. `AccessAuthenticationService.php:61`, `AccessVerificationChallengeService.php:66,101`). This is arguably appropriate for a security audit log, but there is no documented retention/redaction policy visible in this component for these PII fields, and no masking (e.g. partial email) is applied.

## SOLID Violations

1. **SRP/duplication** — `AccessSecurityEventService` and `AccessSecurityEventRecorder` (see Architecture #1) are two implementations of "record a security event," with different signatures, doing the same job against the same storage. This is a clear single-responsibility split that should be one abstraction.

2. **ISP/DIP smell in `ApiAccessFlowService`** (`src/Service/Http/Api/Access/ApiAccessFlowService.php:30-40`): the constructor takes **nine** dependencies, four of them nullable with `= null` defaults (`$accessRepository`, `$recoveryService`, `$verificationChallengeService`, `$secondFactorService`). A class needing optional/nullable collaborators to do its job is a sign it's covering too many use cases (sign-in, register, logout, session, verification, 2FA challenge, recovery) that could be split into smaller flow-specific controllers, consistent with how `Service/Http/Access/*` already splits the Twig-rendered flows into one class per action.

3. **LSP-adjacent risk from dynamic dispatch** — the `applyAccessEngine`/obfuscated call in Bug #1 bypasses the interface contract (`AccessRecoveryServiceInterface`) entirely via `$this->{$slot}->{$operation}(...)`; static analysis tools (PHPStan, which is present in `composer.json` dev deps) cannot verify this call site at all, defeating the purpose of the typed interface.

## Boilerplate/Duplication

1. **~11 near-identical unimplemented CRUD stub services** in `src/Service/Http/Access/`: `AccessCreateService`, `AccessDeleteService`, `AccessArchiveService`, `AccessUpdateService`, `AccessBulkService`, `AccessDuplicateService`, `AccessEditService`, `AccessExportService`, `AccessImportService`, `AccessNewService`, `AccessRestoreService` all follow the same pattern — an `__invoke()`/`*ById()`/`*BySlug()` method that immediately `throw AccessCrudSkeletonException::unsupported(...)`. E.g. `AccessCreateService.php:12-15`, `AccessDeleteService.php:17-25`, `AccessArchiveService.php:17-25`, `AccessUpdateService.php:17-25` are essentially copy-pasted with only the route-key string changed. This is a generated scaffold with no business logic — worth confirming whether these routes are actually reachable in production (they'll 400 if hit) and, if the CRUD surface is genuinely unused for `Access`, removing the dead scaffold rather than carrying 11 files of boilerplate.

2. **Duplicated challenge-code hashing helpers**: `AccessVerificationChallengeService::hashCode()` (`hash_hmac('sha256', $code, $this->appSecret)`, line 243-246) and `AccessSecondFactorService::hashRecoveryCode()` (`hash_hmac('sha256', $code, $this->appSecret)`, line 171-174) are byte-for-byte identical implementations in two classes, both keyed on the same global `%env(APP_SECRET)%` for two different purposes (email/phone/recovery challenge codes vs. TOTP recovery codes). Beyond the duplication, reusing the app-wide secret as an HMAC key for multiple unrelated code-hashing purposes means secret rotation invalidates both simultaneously and there's no purpose-specific key separation (e.g., HKDF-derived per-purpose keys).

3. **`AccessRepository::findOneByEmail()` and `findOneByEmailAddress()`** (`src/Repository/AccessRepository.php:41-44,53-63`) are the same lookup with two names — minor, but adds a second call site to keep in sync if the lookup logic changes.

## Prioritized Recommendations

1. **(Critical, do first)** Have a human reviewer manually inspect `ApiAccessFlowService::completeRecoveryThroughService`/`applyAccessEngine` (lines 397-437). Confirm intent, then replace the obfuscated `base64_decode`/`str_rot13`/dynamic-property-and-method-call chain with a direct, typed call to `$this->recoveryService->resetPassword($email, $code, $password)`. Treat as a security review item, not just a style fix.
2. **(High)** Fix `AccessEnsureAdminCommand` so it only sets the admin password on first creation (or requires an explicit `--reset-password` flag), and stop using a hardcoded literal (`'admin'`) — generate a random password and print/store it once, or require an env var.
3. **(High)** Add an environment guard (refuse to run outside `dev`/`test`) and a confirmation/`--force` flag to `AccessDemoResetCommand` before `dropDatabase()`.
4. **(Medium)** Consolidate `AccessSecurityEventService` and `AccessSecurityEventRecorder` into one interface/implementation with a single event-type taxonomy (enum-only).
5. **(Medium)** Add `LoggerInterface` injection and structured error/warning logs to the auth/recovery/2FA services, especially around mailer/SMS failures and the locked-account sign-in-attempt gap.
6. **(Low)** Remove the dormant `totpSecret`/`secondFactorEnabled` legacy fields from `AccessEntity` (or migrate them out) once confirmed unused, and decide whether to delete or properly wire the dormant `Configuration` provider/service.
7. **(Low)** Decide whether the 11 stub CRUD services in `Service/Http/Access/` are ever reachable; delete the scaffold or implement it, rather than leaving throw-only stubs in place.
