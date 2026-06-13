# Accessing relationship/lifecycle hardening wave 2

Status: applied as a conservative hardening pass.

## Scope

- Adds `AccessLifecyclePolicy`.
- Keeps lifecycle validation string-based to avoid schema drift.
- Does not touch `*EnGb*` / translation normalization.
- Does not touch Attachment/Attaching mechanics.

## Lifecycle decision

Security identity/account lifecycle. Security facts such as lockedUntil, emailVerifiedAt and failedLoginCount remain domain lifecycle fields.

## Transition map

- `registered` -> `verified`, `disabled`
- `verified` -> `active`, `disabled`
- `active` -> `locked`, `disabled`, `deleted`
- `locked` -> `active`, `disabled`, `deleted`
- `disabled` -> `active`, `deleted`
- `deleted` -> `terminal`
