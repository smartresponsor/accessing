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
- Core entity: Account

Preferred supporting entities:
- Credential
- VerificationChallenge
- SecondFactor
- RecoveryCode
- SecurityEvent
- AccountSession
