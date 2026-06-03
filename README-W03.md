# Accessing W03 - container autowire cleanup

Fixes the next Symfony DI compilation layer after W02.

Changed:
- `config/services.yaml`
- `config/component/services.yaml`
- `docs/deploy/w03-container-autowire-cleanup.md`

Purpose:
- keep `App\\Accessing\\` namespace untouched;
- keep standalone Accessing deploy check independent from dormant Configuring integration;
- stop Symfony autowiring from treating DTO/value/contract/form/message/event classes as services;
- wire `AccessingSecurityNotificationService::$accessingMailerSender` explicitly instead of relying on a component-level `_defaults` scalar bind that Symfony reported as unused.
