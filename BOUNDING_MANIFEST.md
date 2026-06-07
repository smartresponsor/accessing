# Accessing Bounding Manifest

The component boundary is the access lifecycle.

Inside boundary:
- access registration and activation
- credential ownership and maintenance
- email and phone verification
- second-factor enrollment and challenge
- password reset and recovery codes
- login attempts, cooldowns, locks, unlocks
- access session and security event tracking
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
Use Access as the route/resource root. Use User only where Symfony security vocabulary requires it, such as UserInterface or getUser().
Do not reintroduce AccessUser*, UserSession, UserContext, or UserAdministration wrapper names.

Architectural canon inside the boundary:
- one Symfony root code tree: App\Accessing\ => src/
- no /Domain/
- no repository-named or component-named wrapper folders in src/
- service implementations only in src/Service/...
- service interfaces only in mirrored src/ServiceInterface/...
- DTO where it improves flow clarity
- ValueObject where business meaning or invariants justify it
- Symfony Validator at the correct level
