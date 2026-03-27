# Accessing Product Manifest

Mission:
Provide a reusable ecosystem brick that owns secure account access from registration to verified, recoverable, and auditable usage.

Primary persona groups:
- end users
- support operators
- sibling applications consuming account access state

MVP outcomes:
- a user can register and verify email
- a user can sign in securely
- a user can enable TOTP second factor
- a user can verify phone ownership
- a user can recover access through reset and recovery codes
- the system can throttle abuse and record security events
- operators can review meaningful demo data and key account states

Key UI outcomes:
- clean Bootstrap-based management pages
- scenario-driven flows instead of disconnected CRUD screens
- visible flashes, statuses, tables, and action pages

Key CLI outcomes:
- diagnostics
- fixtures load and reset
- report generation
- cleanup and demo reset
- security or verification maintenance tasks when useful

Key acceptance principles:
- every important product flow must be demonstrable locally
- fixtures must represent realistic access scenarios
- browser automation must target real user journeys
