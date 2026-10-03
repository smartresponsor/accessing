# AGENTS.md

Read first:
1. FULL_MANIFEST.md
2. BOUNDING_MANIFEST.md
3. ARCHITECTURE_MANIFEST.md
4. PRODUCT_MANIFEST.md
5. local MANIFEST.md files in affected folders

Execution policy:
- preserve the single Symfony root tree
- do not create Domain folders
- do not introduce alternative root namespaces
- do not place service interfaces under src/Service
- prefer substantive fixes over format churn
- build the product inside the Accessing boundary

Product center:
- Workspace: Accessing
- Canonical route/resource root: Access
- Core access subject entity: AccessEntity

Preferred supporting entities:
- Credential
- VerificationChallenge
- SecondFactor
- RecoveryCode
- SecurityEvent
- AccessSession
## Platform Canon Precedence

For work under `D:\PhpstormProjects\www`, authoritative platform rules live in the Canonization repository. Gating is the executable mirror for objectively guardable rules. This `AGENTS.md` is an agent-facing projection or local supplement and must not override or contradict Canonization.

If a local instruction conflicts with current Canonization, follow Canonization and synchronize this file. Local instructions may narrow scope or add repository-specific constraints only when they remain compatible with Canonization.
