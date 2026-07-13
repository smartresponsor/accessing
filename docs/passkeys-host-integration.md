# Passkeys Host Integration

Accessing validates WebAuthn ceremonies against one explicit relying-party ID and one HTTPS origin.

## Required configuration

```text
ACCESSING_PASSKEY_RP_ID=auth.example.com
ACCESSING_PASSKEY_ORIGIN=https://auth.example.com
```

The RP ID is a domain, not a URL. The origin includes scheme and host and must not contain a path. Production hosts must not rely on request-derived fallback values.

## Reverse proxy and mobile-edge

- Configure RP values from the public WebAuthn domain, never an internal service or container hostname.
- Preserve HTTPS at the public boundary.
- Mobile-edge may proxy ceremony payloads, but it does not determine the RP ID or origin.
- The browser or native platform must receive options generated for the same RP ID used by its domain association.

## Schema

Host applications must generate and review Doctrine migrations for:

- `access_passkey_credential`;
- `access_passkey_challenge`;
- the canonical serialized credential-record column.

Run Doctrine schema validation after migration application. Do not hand-edit entities to match stale host migrations; entities remain authoritative.

## Native association

- Android requires `/.well-known/assetlinks.json` with the exact application ID and accepted signing-certificate SHA-256 fingerprints.
- iOS requires an Apple app-site-association file containing the exact Team ID and bundle identifier, plus a `webcredentials:<RP_ID>` entitlement.
- Association documents must be public over HTTPS without authentication or redirects.

## Release acceptance

