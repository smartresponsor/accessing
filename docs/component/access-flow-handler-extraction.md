# Access flow handler extraction

This pass moves already implemented Access form-processing logic from surface builders into the grammar-aligned HTTP service tree.

## Canonical physical tree

- `src/Service/Http/Access/AccessSecurityFlowService.php`
- `src/Service/Http/Access/AccessResetPasswordFlowService.php`
- `src/Service/Http/Access/AccessSurfaceFlowService.php`
- `src/Form/Access/*Type.php`

## Preserved behavior

The following flows keep their existing business services, forms, redirects, flashes, and rendering contracts:

- registration
- sign in
- sign in submit
- second-factor sign-in challenge
- sign out
- switch user
- password recovery request
- password recovery reset
- reset-password bundle request/check/reset
- email verification
- phone verification request/confirm
- second-factor enrollment/disable
- session management
- current password change
- security events

## Transitional builder role

`src/Builder/*SurfaceBuilder.php` remains as a thin transitional caller so the current Cruding/front-controller boundary is not broken. It no longer owns the form-processing logic.

Future controller output should target the service classes under `src/Service/Http/Access/` directly.
