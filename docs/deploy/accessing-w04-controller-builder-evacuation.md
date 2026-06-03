# Accessing W04 — Controller Builder Evacuation

## Canon decision

Builder classes are not canonical in `src/Controller/`.

Surface builders belong in the type-oriented builder layer:

- `src/Builder/AccessingSurfaceBuilder.php`
- `src/Builder/AccessingSecuritySurfaceBuilder.php`
- `src/Builder/AccessingResetPasswordSurfaceBuilder.php`
- `src/Builder/AccessingOperatorSurfaceBuilder.php`

The component keeps the scoped namespace `App\Accessing\...`.

## Removed controller-layer drift

Deleted the stale controller copies:

- `src/Controller/AccessingSurfaceBuilder.php`
- `src/Controller/AccessingSecuritySurfaceBuilder.php`
- `src/Controller/AccessingResetPasswordSurfaceBuilder.php`
- `src/Controller/AccessingOperatorSurfaceBuilder.php`

## Route ownership

Accessing route/surface ownership remains externalized through Cruding/View/Interfacing host flow.
`config/component/routes.yaml` must not re-import builder classes as Symfony controllers.

## Operator token canon

When operator surface tokens are represented by the host route layer, use singular no-dash tokens, for example:

- `/operator/account`
- `/operator/account/{id}`
- `/operator/security/event`

Avoid plural route tokens and dashed composite tokens such as `/operator/security-events`.
