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

## 2026-09-14 — RC dependency and runtime integration pass

### Reconnaissance
- Re-read Accessing root manifests and Composer/runtime surfaces.
- Re-read Objecting, Cruding, Viewing, Interfacing, Gating, and Canonization contract sources relevant to Accessing.
- Consulted Canon003, Canon007, Canon018, Canon022, Canon023, Canon024, Canon030, Canon033, Canon038, Canon043, and Canon045.
- Market/peer baseline reviewed against Symfony Security, ZITADEL, and Ory: MFA/passkeys, throttling, recovery, session/audit lifecycle are RC-relevant; federation/SCIM/enterprise IdP breadth remains growth scope.

### Target-to-canon mapping
- Canon022: Accessing is standalone (`bin/console` + `config/bundles.php`) and must directly require Cruding, Collectioning, Tabling, Viewing, Interfacing, Objecting, and EasyAdmin.
- Canon023: all local first-party path repositories must use `symlink: true`.
- Canon043: local first-party dependencies use exact `dev-master`; development stability is `dev`; each path repository pins `options.versions[package] = dev-master`.
- Canon045: root development Composer must expose transitive local repository closure, including Collectioning and Tabling required through Cruding/Tabling.
- Canon024/033: production remains path-independent and retains the same Accessing package identity.
- Canon030: mapping/schema validation is green, but the existing two-process in-memory SQLite parity script cannot establish migration currentness because each command receives a fresh database; full PostgreSQL migration-chain proof remains environment-dependent.

### Material implementation
- Added direct `collectioning/collection` and `tabling/table` runtime dependencies.
- Added Collectioning/Tabling local path repositories and canonical `dev-master` path-version pins for all first-party local repositories.
- Set development `minimum-stability` to `dev` while preserving `prefer-stable: true`.
- Added canonical `phpstan` Composer script alias used by repository tooling.
- Added packaged production VCS resolution for Collectioning and Tabling without introducing production path/symlink repositories.
- Registered `CollectioningBundle` and `TablingBundle` in standalone Accessing runtime after schema validation exposed the missing Tabling service registration.
- Updated `composer.lock` through Composer; installed the resolved dependency graph locally.

### Verification
- `composer validate --strict --check-lock`: PASS.
- PHP lint: PASS.
- PHP-CS-Fixer dry run: PASS, 254 files, 0 fixable.
- PHPStan: PASS, 252 files, 0 errors.
- PHPUnit: PASS, 176 tests / 1980 assertions; 75 PHPUnit notices remain non-failing test debt.
- Doctrine mapping/schema validation: PASS.
- `schema:parity`: mapping/schema phase PASS; migration-currentness phase BLOCKED by the existing fresh in-memory SQLite-per-process harness rather than a mapping drift finding.

### Worktree boundary
- Pre-existing test changes and the anomalous `.gating/` replacement/copy remain untouched and are not part of this pass.
- Files intentionally changed by this pass: `composer.json`, `composer.lock`, `composer.prod.json`, `config/bundles.php`, `CMCP_CHANGELOG.md`.

## 2026-09-14 — Canon030 parity harness hardening

### Material implementation
- Replaced the invalid two-process in-memory SQLite `schema:parity` script with an explicit PostgreSQL parity mode in the existing bounded PostgreSQL runner.
- The parity mode targets the dedicated `accessing_test` database and executes: database drop/create, full migration chain, Doctrine schema validation, then migration-currentness verification.
- The default PostgreSQL PHPUnit mode is unchanged.

### Verification
- Runner PHP syntax: PASS.
- `composer validate --strict --check-lock`: PASS.
- PHP-CS-Fixer dry run: PASS, 254 files, 0 fixable.
- PHPStan: PASS, 252 files, 0 errors.
- PHPUnit: PASS, 176 tests / 1980 assertions; 75 non-failing PHPUnit notices remain.
- `schema:parity`: correctly BLOCKED before mutation because PostgreSQL at `127.0.0.1:54329` is unavailable; Docker Desktop Linux engine is not running.
- The previous SQLite false-negative/false-positive topology is removed: Canon030 evidence now requires the canonical PostgreSQL engine and clean migration-chain execution.

### RC diagnostic note
- The generic RC validator misclassified repeated text `No syntax errors detected` as `false_green_suspected`; direct lint execution is exit 0 and the output contains no PHP syntax error. This is tooling classifier noise, not an Accessing lint failure.
- Remaining runtime blocker is external infrastructure only: start Docker Desktop/PostgreSQL and rerun `composer schema:parity` for the final migration-chain proof.
