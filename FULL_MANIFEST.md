# Accessing Full Manifest

Product name: Accessing
Core domain entity: Account
Package name: smartresponsor/accessing

Accessing is a single Symfony 8 application brick responsible for the account access lifecycle across the ecosystem.
It must remain a single-root Symfony-oriented application with App\ => src/ and no alternative root namespaces.

Core product capabilities:
- account registration
- sign in and sign out
- email verification
- phone verification
- multi-factor authentication
- password recovery
- account lock, cooldown, and reactivation flows
- security audit events
- demo management UI
- CLI maintenance and diagnostics

Primary delivery principle:
Build meaningful working flows first. Avoid decorative refactors, dead abstractions, fake complexity, and format churn.

Implementation priorities:
1. working Account lifecycle
2. secure verification and recovery
3. usable demo UI and fixtures
4. broad CLI support
5. stable tests and local quality gates

Non-goals for MVP:
- full RBAC or ACL policy engine
- social profile system
- deep KYC or AML
- enterprise federation provider mode
- decorative architectural layers

Required stack direction:
- Symfony 8
- PHP 8.4 runtime
- Doctrine ORM and Migrations
- Symfony Forms, Validator, Twig, Bootstrap
- Fixtures with Faker
- Symfony Security best practices
- Panther and Playwright where they add real coverage

Naming canon:
- Workspace: Accessing
- Core entity: Account
- Supporting entities: Credential, VerificationChallenge, SecondFactor, RecoveryCode, SecurityEvent, AccountSession

Agent directive:
Each folder-level manifest is normative for its local area. Use them together with this root manifest.
