# Access Flow Builder Evacuation

W4.4 removes the remaining user-flow `Builder` classes after their tested form handling was extracted into grammar-aligned services.

Canonical runtime owners now live in:

- `src/Service/Http/Access/AccessSecurityFlowService.php`
- `src/Service/Http/Access/AccessResetPasswordFlowService.php`
- `src/Service/Http/Access/AccessSurfaceFlowService.php`

The removed builder classes were transitional delegates only:

- `src/Builder/AccessSecuritySurfaceBuilder.php`
- `src/Builder/AccessResetPasswordSurfaceBuilder.php`
- `src/Builder/AccessSurfaceBuilder.php`

`AccessOperatorSurfaceBuilder` is intentionally not removed in this pass because it owns the non-user-flow operator security event surface, not the Access flow form handling.

Controllers in the frontend/Cruding layer should bind directly to the grammar-aligned services under `Service/Http/Access`.
