# Accessing W01 Deploy Readiness Canon Fix

This wave applies the accepted Accessing deployment-readiness canon corrections:

- Keeps the component namespace as `App\Accessing\...`.
- Keeps `src/Controller/AccessOperatorSurfaceBuilder.php` as the operator surface route owner.
- Removes the duplicate `AccessingOperatorController` wrapper.
- Keeps component-level route import empty so host applications resolve Accessing surfaces through Cruding/View/Interfacing.
- Moves surface builders from `src/Service/Surface/` to the type-oriented `src/Builder/` layer.
- Changes the operator security event route from `/operator/security-events` to `/operator/security/event`.
- Changes operator account routes to singular `/operator/account` and `/operator/account/{id}`.
- Changes Doctrine table names to use the `access_` prefix.

Removed obsolete files:

- `src/Controller/AccessingOperatorController.php` because `src/Controller/AccessOperatorSurfaceBuilder.php` owns the operator surface routes.
- `migrations/Version20260328000431.php` because it was an obsolete SQLite-style migration with unprefixed tables and conflicted with the active `access_` schema line.
