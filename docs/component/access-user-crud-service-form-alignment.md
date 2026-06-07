# Access User CRUD service/form alignment

This component does not own the frontend controller boundary. Cruding owns the frontend grammar and controller output. Accessing owns the backend service/form targets behind the `access.user.*` grammar.

## Grammar-first rule

`config/platform/routes/crud/access.user.yaml` is the naming source of truth. Accessing must conform its physical service and form tree to that grammar without changing the grammar, introducing a second Composer root namespace, or hiding old entrypoints behind facade aliases.

## Physical targets

Canonical physical tree:

- `src/Service/Http/Access/User/*Service.php`
- `src/Form/Access/User/*Type.php`

Canonical component namespace:

- `App\Accessing\Service\Http\Access\User`
- `App\Accessing\Form\Access\User`

## Form mapping status

The current slice contains grammar-aligned form types for all `access.user.*` routes that declare a `type` in the grammar:

- `access.user.create` -> `AccessUserCreateType`
- `access.user.update_id` / `access.user.update_slug` -> `AccessUserUpdateType`
- `access.user.delete_id` / `access.user.delete_slug` -> `AccessUserDeleteType`
- `access.user.bulk` -> `AccessUserBulkType`
- `access.user.import` -> `AccessUserImportType`
- `access.user.export` -> `AccessUserExportType`
- `access.user.archive_id` / `access.user.archive_slug` -> `AccessUserArchiveType`
- `access.user.restore_id` / `access.user.restore_slug` -> `AccessUserRestoreType`
- `access.user.duplicate_id` / `access.user.duplicate_slug` -> `AccessUserDuplicateType`

Authentication and self-service forms remain outside the CRUD tree:

- `AccessUserRegistrationType`
- `AccessUserSignInType`
- `AccessRecovery*Type`
- `AccessPassword*Type`
- `AccessVerification*Type`

These forms must not be treated as `access.user.*` CRUD forms unless the grammar explicitly changes upstream.
