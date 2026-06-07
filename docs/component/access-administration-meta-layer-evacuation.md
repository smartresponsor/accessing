# Access administration meta-layer evacuation

The `Admin` / `AccessAdministration*` meta-layer has been evacuated from Accessing.

Accessing keeps responsibility for access, authentication, credentials, recovery, verification, sessions, and security-event runtime flows. Generic administration workbench concepts such as action catalogs, bridges, audit-workbench projections, capability matrices, contract matrices, execution plans, readiness reports, remediation plans, and work plans no longer live in this component.

The evacuated files are preserved in a separate archive with their original relative paths for future migration into Administering, Gating, Operating, or another owning component.

This pass does not remove access-domain runtime services, authentication flows, sessions, credentials, recovery, verification, security events, or the component identity integration metadata.
