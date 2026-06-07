# Access Operator Surface Builder Evacuation

W4.5 removes the remaining `src/Builder/AccessOperatorSurfaceBuilder.php` class from the access flow surface.

The only remaining method, `securityEvents()`, did not represent a controller or an EasyAdmin controller. It was a thin render entrypoint for operator security-event listing.

The handling now lives in the same grammar-oriented service tree used by the rest of Access flows:

```text
src/Service/Http/Access/AccessOperatorSecurityEventsService.php
```

The service is self-contained through constructor injection and can be called directly by the upstream Cruding/controller boundary.

No `App\` root namespace, controller bridge, alias, or grammar rewrite is introduced.
