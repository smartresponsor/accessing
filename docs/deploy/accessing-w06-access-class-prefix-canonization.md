# Accessing W06 — Access class prefix canonization

This wave normalizes component-owned runtime, business, value, form, repository, provider, recorder, validator, factory, responder, builder, interface, fixture, command, and test classes to the `Access*` class-name prefix while preserving the package namespace `App\\Accessing`.

Intentional exceptions:

- `App\\Accessing\\AccessingBundle`
- `App\\Accessing\\DependencyInjection\\AccessingExtension`
- `App\\Accessing\\Kernel`

The database/table prefix remains `access_`.
The package/component namespace remains `App\\Accessing`.
