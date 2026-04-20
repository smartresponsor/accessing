# Accessing Architecture Manifest

Mandatory structure rules:
- single root namespace App\Accessing\ mapped to src/
- no Smartresponsor\ or any alternative root namespace in code
- no /src/Domain/
- no repository or component wrapper folders such as /src/Accessing/ or /src/Account/
- Symfony-oriented layers only

Mandatory service rule:
- src/Service/... contains implementations only
- src/ServiceInterface/... contains mirrored service contracts only
- component-specific service names must start with the component or domain term and end with Service when that increases clarity

Configuration rules:
- config files must use a consistent component prefix as the first token of the filename where custom files are introduced
- prefer Symfony best practice for DI, container wiring, aliases, binds, tags, visibility, and package configuration

Flow rules:
- form flow, CLI flow, HTTP flow, and application flow should use DTO where entity transport would be noisy or unsafe
- validation belongs in DTO, form model, ValueObject, or Entity depending on responsibility
- repositories must not carry business orchestration

Quality rules:
- real local pipeline
- useful logs and reports
- tests split by responsibility: unit, integration, functional, Panther, Playwright
- no decorative churn and no meaningless formatting-only waves
