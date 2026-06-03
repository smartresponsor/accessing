# Accessing W05 Source Tree Canonization

This wave normalizes source-tree placement and Symfony class naming without changing the business flow.

## Decisions

- `App\Accessing\...` remains the correct component namespace.
- Context values are not DTOs: `AccessingCurrentAccountContext` now lives in `src/Context`.
- Symfony form classes use the Symfony-style `*Type` suffix, not `*FormType`.
- `AccessingAuthenticationEntryPoint` is a Symfony security entry point and now lives under `src/Authenticator` instead of a generic `src/Security` bucket.
- Builders remain under `src/Builder` from W04.
- Provider/Recorder/Validator/Bridge/Catalog/Factory/Responder classes are placed in type-identifiable folders instead of broad `src/Service` buckets.

## Canonical moves

- `src/Dto/AccountContext` -> `src/Context`
- `src/Service/AccountContext` -> `src/Provider/AccountContext`
- `src/Security` entry point -> `src/Authenticator`
- `src/Service/Admin/*Provider.php` -> `src/Provider/Admin`
- `src/Service/Admin/*Recorder.php` -> `src/Recorder/Admin`
- `src/Service/Admin/*Validator.php` -> `src/Validator/Admin`
- `src/Service/Admin/*Bridge.php` -> `src/Bridge/Admin`
- `src/Service/Admin/*Catalog.php` -> `src/Catalog/Admin`
- `src/Service/Rendering/PageViewFactory.php` -> `src/Factory/Rendering/PageViewFactory.php`
- `src/Service/Rendering/TwigPageResponder.php` -> `src/Responder/Rendering/TwigPageResponder.php`
- `src/Service/SecurityEvent/AccessingSecurityEventRecorder.php` -> `src/Recorder/SecurityEvent/AccessingSecurityEventRecorder.php`
- `src/Service/Accessing*SurfaceContractFactory.php` -> `src/Factory/Surface`

## Verification

- PHP syntax lint passes for all PHP files under `src`, `config`, `tests`, and `migrations`.
- No `src/Controller/*SurfaceBuilder.php` files remain.
- No listed non-canonical old paths remain.
