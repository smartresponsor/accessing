# Accessing Bounding Manifest

The component boundary is the account access lifecycle.

Inside boundary:
- account registration and activation
- credential ownership and maintenance
- email and phone verification
- second-factor enrollment and challenge
- password reset and recovery codes
- login attempts, cooldowns, locks, unlocks
- account session and security event tracking
- UI, CLI, fixtures, tests, and reports necessary to operate these flows

Outside boundary unless explicitly needed:
- generic user profile management
- organization or tenant administration
- rich authorization policy engine
- billing identity and payment instruments
- legal consent archive
- deep KYC or AML identity proofing
- unrelated notification center responsibilities

Vocabulary canon:
Use Account as the core domain term.
Do not drift into User, Identity, Principal, Profile, Auth, Authorization, or Access as the main entity unless there is a very explicit and local reason.

Architectural canon inside the boundary:
- one Symfony root code tree: App\ => src/
- no /Domain/
- no repository-named or component-named wrapper folders in src/
- service implementations only in src/Service/...
- service interfaces only in mirrored src/ServiceInterface/...
- DTO where it improves flow clarity
- ValueObject where business meaning or invariants justify it
- Symfony Validator at the correct level
