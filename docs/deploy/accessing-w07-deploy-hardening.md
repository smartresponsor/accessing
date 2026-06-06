# Accessing W07 deploy hardening

## Scope

W07 focuses on deploy blockers and canon drift found while installing Accessing into the host application.

## Fixes

- Removed residual hybrid class names such as `AccessDoctrineAccessing*`, `AccessBootstrapAccessing*`, and `AccessNullAccessing*`.
- Removed the empty `src/Security` capability layer after moving the authentication entry point to `src/Authenticator` in an earlier wave.
- Removed the global `$accessingMailerSender` default bind from root services and kept it as explicit wiring for the notification service.
- Completed `config/component/services.yaml` aliases so host applications loading the bundle receive the same interface-to-implementation wiring as the standalone component.
- Added missing dormant configuration form/value classes and renamed the form to Symfony-style `AccessEnvironmentConfigType` rather than `*FormType`.
- Replaced obsolete direct-route functional tests with a producer boundary test that asserts Accessing does not register standalone producer routes.

## Canon status

- Namespace remains `App\Accessing`.
- Business/runtime class prefix is `Access`.
- Package-level exceptions remain `AccessingBundle` and `AccessingExtension`.
- Producer routes remain host-owned by Cruding/View/Interfacing.
