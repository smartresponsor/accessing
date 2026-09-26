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

## 2026-09-18 — Canon040 closure and RC verification

### Material coverage closure
- Reconciled the stabilized concurrent coverage-hardening wave without touching the pre-existing local `.gating/` deletions.
- Added behavior-focused unit/integration coverage and branch-explicit, behavior-preserving production refactors in Access entities, lifecycle policy, mobile token handling, and value objects.
- Added a two-phase coverage workflow: standard PHPUnit method/line measurement plus Xdebug path-instrumented branch measurement, merged only after validating identical method and line populations.

### Final verification evidence
- PHPUnit: PASS, 261 tests / 2926 assertions; 124 non-failing PHPUnit notices remain.
- Canon040 coverage: PASS — methods 87.18% (605/694), branches 77.23% (1801/2332), lines 82.25% (3096/3764).
- PHPStan: PASS, 0 errors across 262 files.
- PHP-CS-Fixer dry run: PASS, 264 files, 0 fixable.
- Vendor-based Gating: PASS, 36 rules, 0 failed, 1 warning, 3 skipped.
- Canon042 remains the only warning because repository-owned behavioral/UI coverage evidence is not yet generated.
- Doctrine mapping metadata/migrations were not structurally changed by this wave; prior PostgreSQL schema-parity evidence remains applicable. The current schema-parity wrapper invocation exceeded the short Console MCP call window, so no new wrapper exit code is claimed.

### Integration boundary
- The two pre-existing deletions under `.gating/` are explicitly excluded from this integration commit.
- The worktree stayed stable across repeated status checks during final verification; no further concurrent writes were observed.

## 2026-09-22 — Canon047 Doctrine manager ownership closure

### Baseline
- Repository was clean before this pass.
- Full Gating baseline had exactly one blocking rule: Canon047, caused by direct `Doctrine\\ORM\\EntityManagerInterface` dependencies in `AccessDemoResetCommand`, `AccessEnsureAdminCommand`, `AccessCredentialService`, and `AccessSecondFactorService`.
- Existing entity repositories already owned normal persistence, but schema/fixture reset and multi-aggregate unit-of-work operations still leaked the Doctrine manager into application services and commands.

### Material implementation
- Added `AccessPersistenceRepositoryInterface` and `AccessPersistenceRepository` as the repository-owned low-level Doctrine boundary.
- Moved persist/remove/flush, schema reset, and ORM fixture execution behind that repository boundary.
- Rewired the four Canon047 offenders and the Symfony service binding to use the repository contract.
- Updated the directly affected unit tests to mock the persistence repository instead of `EntityManagerInterface`.

### Verification
- PHPStan: PASS, 0 errors.
- PHPUnit: PASS, 261 tests / 2935 assertions; existing 124 non-failing notices remain.
- Full Gating: PASS, 68 rules, 0 failed; Canon047 is PASS.
- Canon021 and Canon051 also remain PASS, confirming the new persistence boundary did not introduce local generic CRUD or repository orchestration leakage.
- Remaining warnings are evidence debt only: Canon040 coverage evidence stale after source changes and Canon042 behavioral/UI evidence missing.

## 2026-09-24 — Canon055 platform/consumer terminology closure

### Reconnaissance and canon mapping
- Re-read Accessing root manifests, Composer/Gating configuration, current worktree state, and the mandatory Objecting/Cruding/Viewing/Interfacing/Gating contour available through Console MCP.
- Consulted Canonization textual Canon055 directly: consumer/domain aliases such as SmartResponsor must not be used as platform/shared ecosystem identity in current human-facing documentation; explicit consumer/domain context and machine identifiers remain allowed.
- RC-critical workstream: remove current Canon055 documentation ambiguity and generated-artifact scan noise without widening Accessing into authorization, generic CRUD, Objecting, Viewing, or Interfacing responsibilities.
- Growth workstream remains post-RC identity/access maturity (adaptive-risk authentication, broader federation/social login, richer security UX) and is not required for this Canon055 correctness closure.

### Material implementation
- Clarified the legacy Smartresponsor namespace mention in ARCHITECTURE_MANIFEST.md as consumer-domain legacy namespace vocabulary.
- Clarified the smartresponsor/accessing Composer identifier in FULL_MANIFEST.md as a consumer package machine identifier.
- Marked the Vaulting smartresponsor/vaulting references in both market-analysis documents as explicit consumer/domain package references.
- Moved two ignored historical local Gating snapshots from var/ into the excluded .gating/ artifact surface so current-document scanning no longer treats generated historical copies as live Accessing documentation.
- Preserved unrelated pre-existing worktree changes in .gating/README.md, composer.json, LICENSE, and NOTICE.

### Verification
- Gating: PASS — 9 rules, 0 failed, 0 warning, 1 skipped; Canon055 PASS.
- composer validate --strict --check-lock: PASS.
- PHP lint: PASS.
- PHP-CS-Fixer dry run: PASS, 266 files, 0 fixable.
- PHPStan: PASS, 0 errors across 264 files.
- PHPUnit: PASS, 261 tests / 2935 assertions; 124 existing non-failing PHPUnit notices remain.

## 2026-09-24 — Licensing reconciliation and publication readiness

### Reconciliation
- Reviewed the remaining dirty worktree after the Canon055 commit.
- Rejected the copied Gating-owner README under Accessing/.gating as non-value because that directory is a consumer-generated artifact surface; restored the repository-local artifact README from HEAD.
- Retained the coherent licensing change set: composer.json now declares PolyForm-Noncommercial-1.0.0, with repository LICENSE and NOTICE files providing the matching human-facing terms and required notice.
- Preserved composer.prod.json as proprietary; the production/commercial distribution contract remains intentionally distinct from the development/noncommercial package surface.

### Verification
- composer validate --strict --check-lock: PASS.
- Gating: PASS — 9 rules, 0 failed, 0 warning, 1 skipped; Canon055 remains PASS.

## 2026-09-25 — Autonomous RC reconnaissance and acceptance pass

### Factual baseline
- Task: `engine-20260925220459-accessing-87b0d6`; branch `refactor/accessing-canonical-structure` at `bc6d1e60c77348c93081f97e36556170f4c5b16e`, synchronized with its upstream at reconnaissance time.
- The starting worktree contained one pre-existing modification: `.gating/README.md`. Its diff replaced the Accessing consumer-artifact README with Gating owner-side documentation. It was initially isolated as pre-existing work; subsequent Gating-boundary review proved it to be a regression of the consumer-artifact contract, so it was reconciled back to the tracked Accessing content before integration.
- Read the root Accessing manifests, product/bounding/architecture contracts, source/config manifests, current security/passkey milestone documentation, Composer manifest/scripts, and the required Objecting/Cruding/Viewing/Interfacing/Gating/Canonization contract contour.
- Market baseline rechecked against current Symfony Security and ZITADEL authentication/passkey/MFA/session documentation. RC expectations remain deterministic authentication/recovery/session/security behavior; federation/social breadth and adaptive-authentication growth remain separate from RC correctness.

### Canonization mapping consulted
- `Canon017DocumentationMatchesRuntimeRule`: current documentation must match implemented runtime; Accessing market docs now describe passkeys/WebAuthn as implemented with release/UX acceptance remaining, matching current code/dependency evidence.
- `Canon018ComposerIdentityMappingRule`: `accessing/access` maps to `App\\Accessing\\ => src/` and `Access*` subject vocabulary.
- `Canon021CrudingOwnsGenericCrudRule`: Accessing must not reintroduce generic CRUD routing/controllers/services; EasyAdmin remains the permitted admin-surface exception.
- `Canon022StandaloneApplicationDependencyBaselineRule`, `Canon023DevelopmentComposerSymlinkRule`, and `Canon024ProductionComposerBundleRule`: verify the standalone direct baseline, local development path/symlink wiring, and path-independent production manifest.
- `Canon029MandatoryPhpQualityToolingRule`: PHP-CS-Fixer/PHPStan dependencies, config, and reproducible scripts are required.
- `Canon030DoctrineSchemaParityRule`: current Entity metadata plus the full migration chain must reproduce a synchronized clean schema through the repository parity command.
- `Canon040PhpTestCoverageRule`: lines >= 80%, methods >= 80%, branches >= 70% using php-code-coverage evidence.
- `Canon042BehavioralUiCoverageRule`: standalone user-visible behavior requires reproducible inventory-backed behavioral/UI evidence; raw Panther/Playwright counts are not substitute percentages.
- `Canon047RepositoryOwnsDoctrineManagerRule` and `Canon051RepositoryHasNoOrchestrationDependencyRule`: Doctrine-manager access stays in repositories while repositories remain free of application orchestration dependencies.
- `Canon055PlatformIdentityTerminologyRule`: shared platform documentation must use neutral platform identity; consumer aliases are allowed only when explicitly scoped or used as machine identifiers.

### Workstreams
- RC-critical: execute the full deterministic quality/schema/dependency contour against the current tree, inspect any failures against the consulted Canonization evidence contracts, repair only factual in-scope defects, and re-run affected gates.
- Growth: social identity/federation breadth, richer self-service/passkey UX, adaptive authentication, and additional enterprise identity capabilities remain post-RC unless a current correctness failure proves they are required.

### Material implementation
- Repaired `bin/cmcp-coverage.ps1`: the bounded CMCP runner now delegates to the repository-owned `composer test:coverage` workflow instead of producing line/method-only evidence. This preserves one canonical coverage implementation and generates the required methods/branches/lines summary through `bin/merge-coverage.php`.
- Reconciled the pre-existing `.gating/README.md` replacement back to the tracked Accessing consumer-artifact content. After reconciliation the path has no worktree or staged diff; no owner-side Gating documentation is being integrated into Accessing.
- Investigated the unusual production Tabling VCS locator before changing it. `composer.prod.json` and the actual Tabling `origin` both use `git@github.com:smartresponsor/tabling-.git`; no speculative rename was made.

### Fresh verification evidence
- `composer validate --strict --check-lock`: PASS.
- `composer audit --format=summary`: PASS; no security vulnerability advisories found.
- Gating: PASS — 9 rules, 0 failed, 0 warning, 1 skipped; secret scan covered 811 files.
- PHP-CS-Fixer dry run: PASS — 266 files, 0 fixable.
- PHPStan: PASS — 264 files, 0 errors.
- PHPUnit: PASS — 261 tests / 2935 assertions; 124 existing non-failing PHPUnit notices remain.
- Canon040 fresh coverage via the repaired bounded runner: PASS — methods 86.71% (607/700), branches 77.12% (1803/2338), lines 82.20% (3098/3769). The two coverage runs used identical method/line populations and `merge-coverage.php` produced the canonical summary.
- Canon047 targeted scan: direct `EntityManagerInterface` and `ManagerRegistry` usage remains confined to `src/Repository/**`.
- Canon051 targeted scan: no repository imports of Controller/Handler/Service/Mailer/Notifier/RequestStack/Session/MessageBus orchestration types were found.
- Canon021 targeted scan: no `Crud`-named PHP source candidate was found in `src/`.
- Forbidden `App\\Accessing\\Domain` source namespace scan: no hit.

### Residual verification state
- A fresh `schema:parity` invocation exceeded the synchronous Console MCP execution window and returned no final exit; it is therefore not claimed as a fresh PASS or FAIL. No Doctrine Entity, mapping, migration, or persistence configuration changed in this execution window, so the previously proven PostgreSQL parity remains structurally applicable to the current schema.
- Canon042 inventory-backed behavioral/UI evidence is still not freshly established in this window. The repository contains opt-in Panther and Playwright sign-in smoke coverage, but raw browser-test presence is not equivalent to Canon042 inventory percentages.
- Managed PHP runtime probe found no healthy existing server on the default managed port. No restart was performed because this pass made no browser/UI behavior change and the runtime policy is reuse-existing-first.

### Continuation — Canon030 and Canon042 closure
- Re-ran the repository-owned PostgreSQL `schema:parity` contour through a bounded asynchronous CMCP wrapper. The first fresh run reproduced a real Canon030 failure: Doctrine mapping was correct, but the migrated PostgreSQL schema differed from metadata by four Objecting identity index names only.
- Diagnosed the host-PostgreSQL diff through the same database resolver used by parity. The only required SQL was renaming `uniq_6692b54d17f50a6` to `uniq_access_uuid`, `uniq_6692b54989d9b62` to `uniq_access_slug`, `uniq_8b5367e2d17f50a6` to `uniq_access_external_identity_uuid`, and `uniq_8b5367e2989d9b62` to `uniq_access_external_identity_slug`.
- Added forward-only PostgreSQL migration `Version20260926004500CanonicalObjectingIdentityIndexNames` with guarded index renames. A clean parity run then recreated the database, applied all four migrations, reported mapping OK, database schema in sync, migrations up to date, and `CMCP_SCHEMA_PARITY_EXIT=0`.
- Added a repository-owned Canon042 producer at `tools/coverage/access-behavioral-ui-coverage.php`, declared by Composer as `test:behavioral-coverage`. The evidence uses `behavioral-ui-coverage-v2` and explicit eligible/covered inventories for functional, behavioral, UI, and critical dimensions.
- Added real functional coverage for sign-in, registration, recovery, and password-reset-request public surfaces, and expanded Playwright browser coverage to the same four user-visible surfaces.
- Functional execution exposed two standalone-runtime integration defects that prior service-level tests did not reveal: Accessing route services were private in the standalone container, and the Interfacing-owned shell lacked its Twig namespace/extensions. Standalone DI now mirrors the component controller-service visibility contract, registers the installed `@Interfacing` template path, and imports Interfacing's own service/Twig-extension configuration without enabling the full Interfacing bundle/compiler passes.
- Final PHPUnit evidence: PASS — 265 tests / 2947 assertions; 124 existing non-failing PHPUnit notices remain.
- Final isolated Playwright evidence on the managed loopback runtime: PASS — 4/4 browser tests, exit 0. The long combined Console MCP parent process intermittently loses supervision while Playwright is active, so the final browser verdict was also confirmed independently; this is execution-plane noise rather than an application failure.
- Canon042 evidence generation: PASS. The producer wrote fresh `var/coverage/behavioral-ui.json` after the green PHPUnit and Playwright runs. All declared inventories are fully covered in the current RC evidence scope.
- Final quality evidence: Composer strict/lock validation PASS; PHP lint PASS for all changed PHP files; PHPStan PASS across 265 files; PHP-CS-Fixer dry run PASS across 267 files; current Accessing Gating profile PASS — 9 rules, 0 failed, 0 warning, 1 skipped. The current 9-rule profile does not independently execute Canon042, so Canon042 closure is based on the textual Canon contract plus the repository-owned producer/evidence execution rather than a fabricated Gating verdict.
- Visual evidence: GREEN. Playwright captured `var/Accessing/2026-09-25/engine-20260925220459-accessing-87b0d6/signin.png` from the restored standalone sign-in surface.

## 2026-09-26 — Autonomous RC regression check (engine-20260926081755-accessing-42a06a)

### Factual baseline
- Branch `refactor/accessing-canonical-structure` at `d3043f418667ab2dd66f0bf75ce33fe2f7cfc5c9`, synchronized with `origin/refactor/accessing-canonical-structure` at reconnaissance time.
- Starting worktree has one modification: `.gating/README.md`. Its diff replaces the Accessing consumer-artifact README with Gating owner documentation.
- Re-read Accessing root manifests and Composer/security/runtime surfaces, plus the required Objecting/Cruding/Viewing/Interfacing/Gating/Canonization contour.
- Market/OSS baseline: Symfony voters centralize reusable permissions; OPA/Cerbos/Permit demonstrate policy-decision/enforcement separation, contextual authorization, and deny-by-default. Accessing remains bounded to authentication/access lifecycle and does not grow into a rich authorization policy engine for RC.

### Canonization mapping consulted
- Canon017: current documentation must match runtime/boundary.
- Canon018: `accessing/access` maps to `App\\Accessing\\ => src/` and `Access*` vocabulary.
- Canon021: generic CRUD remains owned by Cruding.
- Canon022/023/024: standalone dependency baseline, local symlink development wiring, path-independent production manifest.
- Canon029: repository-owned PHP-CS-Fixer/PHPStan tooling.
- Canon030: clean migration chain must reproduce Doctrine metadata.
- Canon040: >=80% lines, >=80% methods, >=70% branches from php-code-coverage evidence.
- Canon042: inventory-backed functional/behavioral/UI/critical evidence.
- Canon047/051: Doctrine manager stays in repositories; repositories do not orchestrate application services.
- Canon055: neutral platform terminology for shared architecture.

### Workstreams
- RC-critical: restore the Accessing-owned `.gating/` consumer-artifact boundary regression, then re-run deterministic acceptance gates against the current tree.
- Growth: federation breadth, adaptive authentication, richer self-service/passkey UX, and external policy-engine integration remain post-RC unless a current correctness failure proves otherwise.

### Material risks and gates
- Preserve unrelated/local work; do not reset, stash, clean, or delete.
- Reuse existing runtime first; browser/UI execution is applicability-driven because the expected repair is documentation/artifact-boundary only.
- Gates: Composer validation, Gating, lint, PHP-CS-Fixer, PHPStan, PHPUnit; schema/coverage/behavioral evidence will be refreshed only if current source/schema/UI changes or gate freshness requires it.

### Material implementation and verification
- Restored the Accessing-owned `.gating/README.md` consumer-artifact wording after the worktree had copied Gating owner documentation into that generated-consumer surface. The textual diff is now empty; the path may remain marked modified by Git worktree normalization and is intentionally excluded from this task commit.
- The first aggregate `composer quality` attempt proved PHP-CS-Fixer GREEN but exposed an execution-environment failure in PHPStan: the default PHPStan cache attempted to write under `C:\\Users\\Admin\\AppData\\Local\\Temp` and failed with errno 28 (no space left on device).
- Added repository-owned `parameters.tmpDir: var/phpstan` to `phpstan.neon`, keeping generated PHPStan cache in the repository-local ignored var tree instead of depending on the global user temp volume.
- Fresh `composer stan`: PASS — 265/265 analyzed, 0 errors.
- Fresh Composer strict/lock validation: PASS.
- Fresh PHP lint: PASS.
- PHP-CS-Fixer dry run from the aggregate quality attempt: PASS — 267 files, 0 fixable.
- Fresh Gating: PASS — 9 rules, 0 failed, 0 warning, 1 skipped; 819 files scanned by the secret-leak rule.
- Fresh PHPUnit acceptance is NOT_VERIFIED in this execution window. One async run progressed through most of the 265-test corpus without assertion/error output but lost process supervision before a footer/exit code; a subsequent independent repository-check start was correctly refused by runtime policy with `RUNTIME_CAPACITY_ADMIT_LIGHT_ONLY` (`RESOURCE_PRESSURE_WATCH`, `ENGINE_BACKLOG_HIGH`). No test PASS or FAIL is inferred from that incomplete run.
- No PHP production source, Doctrine mapping/migration, browser UI, route, form, or user-flow implementation changed in this pass. The prior fresh Canon030/040/042 and GREEN visual evidence therefore remain structurally applicable, but this execution does not claim a replacement PHPUnit acceptance verdict.



