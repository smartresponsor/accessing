# Accessing Objecting Adoption Milestone

## Implemented

`AccessEntity` is the primary Accessing aggregate and now delegates generic identity and audit ownership to Objecting.

- retained consumer-owned integer primary key;
- added Objecting `object_uuid` and `object_slug`;
- replaced local `createdAt`, `updatedAt`, and `touch()` with Objecting audit;
- retained `getRegisteredAt()` and `getUpdatedAt()` as compatibility aliases over Objecting audit;
- retained security-domain fields such as lock state, failed sign-in count, verification timestamps, and last sign-in timestamp.

## Decision M1 — account lock versus Objecting state

`locked` and `lockedUntil` remain Accessing security-domain fields.

They must not be replaced mechanically by `object_active`, `object_enabled`, or `object_status`, because temporary authentication lockout is not the same as generic object lifecycle state.

Optional future projection: administrative account suppression may use Objecting `object_enabled`, while authentication lockout remains separate.

## Decision M2 — session creation timestamp

`AccessSessionEntity.createdAt` currently represents both persistence creation and session issuance through `getIssuedAt()`.

Options:

1. keep `issuedAt` as a domain event timestamp and add Objecting audit separately;
2. use Objecting `object_created_at` as issuance time and retain only the `getIssuedAt()` alias.

No destructive cleanup is applied until session persistence and token semantics are reviewed.

## Decision M3 — event records and identity breadth

Security events, verification challenges, passkey challenges, recovery codes, reset requests, and sessions require Objecting identity only when independently addressed across component boundaries.

Append-only aggregate-local records may retain only their consumer primary key. Generic audit fields, when needed, must still come from Objecting rather than local `createdAt` or `updatedAt` duplicates.

## Migration requirement

