# Accessing Qodana Wave 21 Report

Source: Qodana SARIF `v5bn4_1rXE7_aaa712d9-e005-4782-b498-e2b510c4e655_qodana.sarif.json`.

## Input summary

- Total SARIF results: 56
- Main groups:
  - PhpMissingParentCallCommonInspection: 31
  - PhpUnhandledExceptionInspection: 9
  - PhpFunctionNamingConventionInspection: 6
  - PhpUnnecessaryCurlyVarSyntaxInspection: 6
  - PhpConstantNamingConventionInspection: 2
  - PhpTooManyParametersInspection: 1
  - PhpUnusedLocalVariableInspection: 1

## Wave 21 applied scope

Targeted fixes only. No broad formatting pass and no mass rename sweep.

- Renamed two non-canonical constants in `AccessingAccountAuthenticationService`.
- Renamed docker runner local functions to snake_case.
- Removed unnecessary curly interpolation in docker runner strings.
- Normalized one runner diagnostic variable.
- Added safe parent calls to Symfony form type overrides.
- Added targeted `@noinspection PhpMissingParentCallCommonInspection` only where the parent call would be wrong or non-semantic: Symfony commands and Doctrine migrations.

## Deferred

- `PhpUnhandledExceptionInspection` items are deferred to a second wave because they require domain-level exception policy decisions.
- `PhpTooManyParametersInspection` in `AccessingResetPasswordController` is deferred because reducing parameters would change controller/service seams and should be done as a separate architectural micro-refactor.
