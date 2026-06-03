# W03 Container Autowire Cleanup

This wave keeps `App\Accessing\` as the component namespace and narrows Symfony autowiring to actual service classes.

## Fixes

- Removes the unused component-level `_defaults.bind` entry for `string $accessingMailerSender`.
- Wires `AccessingSecurityNotificationService::$accessingMailerSender` explicitly.
- Excludes DTO, value, contract, event, message, and form folders from service autowiring.
- Keeps dormant Configuring integration folders excluded in standalone Accessing.

## Reason

Symfony must not treat DTO/value objects with private/static constructors as services. The component container also must not fail because a scalar bind in `_defaults` is not consumed by services loaded from the component extension.
