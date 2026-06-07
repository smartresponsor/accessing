# Access CRUD service/form alignment

This component does not own the frontend controller boundary. Cruding owns the frontend grammar and controller output. Accessing owns the backend service/form targets behind the `access.*` grammar.

## Grammar-first rule

`config/platform/routes/crud/access.yaml` is the naming source of truth. Accessing must conform its physical service and form tree to that grammar without changing the grammar, introducing a second Composer root namespace, or hiding old entrypoints behind facade aliases.

## Physical targets

Canonical physical tree:

- `src/Service/Http/Access/*Service.php`
- `src/Form/Access/*Type.php`

Canonical component namespace:

- `App\Accessing\Service\Http\Access\User`
- `App\Accessing\Form\Access\User`

## Form mapping status

The current slice contains grammar-aligned form types for all `access.*` routes that declare a `type` in the grammar:

- `access.create` -> `AccessCreateType`
- `access.update_id` / `access.update_slug` -> `AccessUpdateType`
- `access.delete_id` / `access.delete_slug` -> `AccessDeleteType`
- `access.bulk` -> `AccessBulkType`
- `access.import` -> `AccessImportType`
- `access.export` -> `AccessExportType`
- `access.archive_id` / `access.archive_slug` -> `AccessArchiveType`
- `access.restore_id` / `access.restore_slug` -> `AccessRestoreType`
- `access.duplicate_id` / `access.duplicate_slug` -> `AccessDuplicateType`

Authentication and self-service forms remain outside the CRUD tree:

- `AccessRegistrationType`
- `AccessSignInType`
- `AccessRecovery*Type`
- `AccessPassword*Type`
- `AccessVerification*Type`

These forms must not be treated as `access.*` CRUD forms unless the grammar explicitly changes upstream.
