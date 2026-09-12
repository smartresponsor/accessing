# CMCP Execution Journal

Task: `engine-20260710212647-accessing-b5dcd8`
Component: `Accessing`
Branch: `refactor/accessing-canonical-structure`

## Iteration 1 — Reconnaissance and baseline

### Read
- Accessing `AGENTS.md`, `README.md`, Composer manifest, root architecture/product/bounding manifests, audit/status/market material.
- Objecting, Cruding, Viewing, and Interfacing `AGENTS.md`, `README.md`, and Composer manifests.
- Canonization `AGENTS.md`, `README.md`, `MANIFEST.json`, and normative Canon003, Canon007, Canon018, Canon038 rule documents.
- Gating `AGENTS.md`, `README.md`, and Composer manifest.

### Current repository state
- The starting worktree is intentionally dirty and contains a large in-progress canonicalization refactor (roughly 245 status entries before this journal was added).
- Existing changes include DTO/config naming migration, generic CRUD scaffold removal, dependency/config updates, tests, templates, and documentation. This run treats that current worktree as the factual baseline and will not roll it back.
- Composer already declares Objecting, Cruding, Viewing, and Interfacing with local path repositories and symlink development wiring.

### Target-to-canon mapping
- Canon003: DTO transport objects must use `src/DTO/` and `*DTO`; current baseline already contains a Dto -> DTO migration that must be verified to completion.
- Canon007: path/namespace/type/import/config identity must be literal after renames; stale references must be found and removed.
- Canon018: `accessing/access` requires `App\\Accessing\\ => src/` and `Access*` subject vocabulary; Composer mapping currently matches.
- Canon038: Access-owned YAML under `config/**` must use `access_*.yaml`; framework/vendor bootstrap names may remain conventional. The current config rename wave must be verified against this rule.
- Cruding boundary: Accessing must retain zero generic CRUD controllers/routes and no throw-only generic CRUD scaffold.
- Objecting boundary: system fields must use Objecting field packs where semantically applicable; no duplicate local system-field implementation should remain.

### Selected RC-critical workstream
Verify and finish the existing canonicalization refactor, resolve concrete gate/test/static-analysis failures, remove stale rename references, and validate packaging/runtime configuration without expanding the Accessing responsibility boundary.

## Iteration 2 — Material implementation

- Fixed PHPStan failures in `AccessArchitectureConventionTest` without weakening assertions.
- Made Canon003 directory-casing validation Windows-safe while preserving literal `DTO` enforcement.
- Corrected Canon037 validation to forbid Git tracking of generated `config/reference.php` rather than forbidding a local generated file.
- Staged removal of the tracked generated reference artifact.
- Added standalone Accessing wiring for the public Interfacing renderer contract without registering the full Interfacing bundle/compiler passes.
- Added the fail-closed marketplace fixture password parameter contract required for container compilation.

## Iteration 3 — Verification and fix

- Fixed stale Objecting metadata in `AccessExternalIdentityEntity`: the unique constraint now targets current unprefixed Objecting source columns `provider` and `external_id`.
- Full Accessing PHPUnit suite passed: 121 tests, 1308 assertions.
- PHPStan passed with zero errors.
- PHP-CS-Fixer check passed after line-ending normalization of the changed architecture test.
- Composer validation passed with strict lock checking.
- Doctrine mapping validation passed and a clean disposable SQLite test schema matched current metadata exactly.

## Iteration 4 — Debt closure and integration

- Re-read current Canon027, Canon028, and Canon030 from Canonization.
- Confirmed standalone Doctrine roles: `data` uses PostgreSQL and `infra` uses file-backed SQLite.
- Determined the two pre-existing migrations were not a complete Canon030 chain: one assumed pre-existing tables; the other encoded obsolete Objecting column names and a MySQL branch.
- Preserved both legacy migration files under `var/retired-migrations/` rather than deleting them.
- Generated `migrations/Version20260912003630.php` directly from current Doctrine metadata using the PostgreSQL platform; it contains the full current schema, indexes, foreign keys, and reverse drop SQL.
- New baseline migration passes PHP syntax validation.

## Iteration 5 — RC closure

### Evidence
- `composer validate --strict --check-lock`: PASS.
- PHPStan: PASS.
- Targeted architecture/security suite: PASS, 12 tests / 806 assertions.
- Full Accessing PHPUnit suite: PASS, 121 tests / 1308 assertions.
- Doctrine mapping: PASS.
- Clean disposable SQLite metadata schema validation: PASS.
- PostgreSQL baseline DDL was generated offline from the same current Doctrine metadata and materialized as a migration.

### Environment-dependent residual
- Local Docker Desktop/PostgreSQL daemon is unavailable, so the final `empty PostgreSQL -> migrate -> schema validate -> empty diff` execution proof cannot run on this machine in the current session.
- This is an environment blocker, not a remaining known code/schema-model defect. The baseline migration is generated from the current metadata rather than handwritten drift.

### RC state before Git integration
- Main code/test/static-analysis gates are green.
- No sibling repository was modified.
- Final Git stage/commit/push/PR decision follows this journal update and final status inspection.
