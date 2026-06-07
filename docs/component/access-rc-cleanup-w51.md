# Access RC Cleanup W5.1

This pass fixes RC blockers found in `Accessing(13)`:

- quoted machine-readable paths in `config/platform/routes/crud/access.yaml`;
- added canonical access flow route registry in `config/platform/routes/flow/access.yaml`;
- moved the sign-in template from `templates/accessin/...` to `templates/access/sign-in/...`;
- materialized all templates referenced by `AccessPageSurfaceContractFactory`;
- changed form block prefixes from `user_*` to `access_*`;
- replaced legacy host route names in Access HTTP flow services with canonical `access.*` keys;
- refreshed root manifests after removing the `User` wrapper;
- scheduled stale legacy directories for physical deletion.
