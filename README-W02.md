# Accessing W02 - standalone container fix

Fixes Symfony DI compilation failure caused by `_instanceof` conditionals referencing external `App\\Configuring` contracts that are not present in standalone Accessing.

Changed:
- `config/services.yaml`
  - removed `_instanceof` entries for `App\\Configuring\\...`
  - excluded `src/Provider/Configuration/` and `src/Service/Config/` from the standalone Accessing autowire resource

No namespace migration. `App\\Accessing\\` remains canonical.
