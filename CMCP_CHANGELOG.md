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
- `schema:parity`: PASS against the real local PostgreSQL service resolved through `www/app/tools/resolve-database-url.php`.
- The runner checks server readiness through the existing `postgres` database, then recreates the dedicated `accessing_test` database, executes the full migration chain, validates Doctrine mapping/schema parity, and confirms migrations are up to date.
- Canon030 execution proof: PASS — one baseline migration executed with 57 SQL queries; final schema validation is in sync and no migrations remain pending.
- `pipeline:local:full`: PASS after host-PostgreSQL parity hardening; lint, PHP-CS-Fixer, PHPStan, and the full Accessing PHPUnit suite all exit successfully.

### RC diagnostic note
- The generic RC validator previously misclassified repeated text `No syntax errors detected` as `false_green_suspected`; direct lint/pipeline execution is exit 0 and contains no PHP syntax failure. This remains tooling classifier noise, not an Accessing defect.
- Docker is not part of the Accessing RC database contour on this host. PostgreSQL credentials are resolved from the existing host application environment and are never copied into Accessing or persisted in the journal.

## 2026-09-16 — Migration registration regression repair

### Reconnaissance and canon mapping
- Re-read Accessing `AGENTS.md`, `README.md`, Composer manifest, architecture/product/bounding manifests, Doctrine migration configuration and current migration, plus the required Objecting, Cruding, Viewing, Interfacing, Gating, and Canonization root contracts.
- Consulted authoritative Canonization rules Canon000, Canon001, Canon002, Canon003, Canon007, Canon008, Canon018, Canon019, Canon020, Canon021, Canon022, Canon023, Canon024, Canon025, Canon026, Canon029, Canon030, Canon032, Canon033, Canon034, Canon036, Canon038, Canon039, Canon041, Canon043, Canon044, and Canon045 as applicable to the current standalone component.
- Canon030 mapping: the complete migration chain must be registered and executable from an empty database before schema parity can be claimed.
- RC-critical workstream: repair migration namespace registration and verify the existing PostgreSQL parity contour. Growth remains adaptive-risk authentication, richer device/session self-service, and security-event analytics; those are not required for this correctness repair.

### Factual baseline and material repair
- `composer validate --strict`: PASS.
- Full PHPUnit suite: PASS, 176 tests / 1980 assertions; 75 non-failing PHPUnit notices remain.
- Initial `schema:parity` failed before migration execution because `config/packages/doctrine_migrations.yaml` registered `DoctrineMigrations`, while the actual baseline migration is `App\\Accessing\\Migrations\\Version20260912003630`.
- Updated the Doctrine migrations path namespace to `App\\Accessing\\Migrations`; no migration SQL or Entity metadata was changed.
- Re-run executed the baseline migration (57 SQL queries) and Doctrine reported both mapping correctness and database-schema synchronization.

### Worktree boundary and residual evidence
- Pre-existing `.gating/` deletions/copy remain untouched.
- Parallel work appeared during this run in `src/Command/AccessDiagnosticsCommand.php` and `tests/Unit/AccessRcCoverageExpansionTest.php`; those files are not attributed to this repair and were not edited here.
- The Console MCP Composer-script wrapper returned no final exit code after the last `doctrine:migrations:up-to-date` phase even though migration execution and schema validation were successful; final currentness should therefore be re-confirmed before integration rather than inferred.

## 2026-09-16 — RC coverage hardening continuation

### Reconnaissance and baseline
- Re-read Accessing root manifests, Composer/dev-prod package contracts, current tests/scripts, prior CMCP journal, and the mandatory Objecting, Cruding, Viewing, Interfacing, Gating, and Canonization contract contour.
- Consulted textual Canonization rules Canon003, Canon007, Canon018, Canon022, Canon023, Canon024, Canon030, Canon033, Canon038, Canon040, Canon043, and Canon045.
- Canon040 is the active RC-critical gap: methods >= 80%, lines >= 80%, branches >= 70%.
- Fresh pre-change coverage baseline was 49.71% methods, 52.96% lines, and 54.25% branches while Composer validation, PHPStan, and the 176-test suite were green.
- The `.gating/` replacement/deletion state originated in parallel work; this reconciliation pass validated and integrated that migration through the vendor-based Gating execution path.

### Material implementation
- Added behavior-focused tests for CLI diagnostics/cleanup, current context, lifecycle policy, page-view creation, Twig response wiring, and Accessing-owned HTTP service boundaries.
- The diagnostics regression test exposed a production defect: `AccessDiagnosticsCommand` supplied Symfony `definitionList()` with numeric pairs, causing values to disappear from CLI output. The command now supplies associative label/value definitions.
- Added coverage for registration, sign-in, recovery/reset, email/phone verification presentation, session/operator/security reads, owner-resolution negative paths, and sign-out behavior without widening production responsibility.

### Current verification evidence
- PHPUnit: PASS, 183 tests / 2064 assertions; existing non-failing PHPUnit notices remain.
- Coverage after two waves: 61.24% methods (425/694), 59.17% lines (2209/3733), 59.16% branches (1308/2211).
- Signed local commit `982f251` contains only the diagnostics fix and two new test files; the later authorized reconciliation integrates the validated Gating migration separately.

### Remaining RC work
- Canon040 remains warning-level debt after this reconciliation: lines 79.4%, methods 75.8%, branches 74.7%; future coverage work should prioritize full method path coverage rather than additional shallow execution.
- Canon042 behavioral/UI inventory evidence remains warning-level debt and should be generated only from an explicit repository-owned surface inventory.
- Repository integration itself is unblocked because executable Gating reports zero failed rules and the hard test/static/schema-validation gates are green.

### Final concurrent-worktree reconciliation
- Reconciled the parallel Gating migration: Accessing now executes `gating/gate` from Composer with repository-owned `config/access_gating_profile.yaml` and `config/access_gating_rules.yaml`; the repository-local `.gating/` runtime snapshot is ignored, while the two previously tracked legacy consumer files are retained unchanged for safe Git integration and are not used by the active gate command.
- Fixed Doctrine/Objecting repository field paths and canonical Access-prefixed component/runtime YAML paths already introduced by the concurrent work, and retained the PostgreSQL runner exit-code repair.
- Repaired one concurrent coverage regression that referenced nonexistent `AccessCurrentContext::roles()`; the canonical method is `bootstrapRoles()`.
- Added submitted registration, sign-in, second-factor, and recovery behavior coverage and narrowed union response types so the expanded test corpus remains PHPStan-clean.
- Final PHPUnit regression evidence: PASS, 233 tests / 2439 assertions; existing non-failing PHPUnit notices remain.
- PHP-CS-Fixer check: PASS; PHPStan: PASS, 0 errors; Composer validate with lock check: PASS.
- Doctrine schema validation after clean migration: mapping PASS and database schema in sync. The outer schema-parity wrapper still exceeded the Console MCP call window after migration, so its final wrapper exit is not claimed.
- Vendor-based Gating: PASS, 36 rules, 0 failed, 2 warnings, 3 skipped. Canon040 evidence is 79.4% lines (2963/3733), 75.8% methods (526/694), 74.7% branches (1742/2333); Canon042 reports the expected missing behavioral/UI inventory warning.
- Canon040 method/line thresholds remain measurable coverage debt, but they are warning-level in the executable Gating policy and no longer block repository integration or publication.

### Growth workstream kept out of RC
