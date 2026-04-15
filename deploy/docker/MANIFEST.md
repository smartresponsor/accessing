# Docker deployment/test manifest

This directory owns the Docker-backed runtime surface for Accessing.

- `compose.yaml` declares PostgreSQL and optional PHP test runner services.
- `.env` contains local test-runtime defaults, not production secrets.
- `bin/run-postgres-tests.php` runs the Accessing PHPUnit suite against the Docker PostgreSQL runtime.
- `php/Dockerfile` defines the optional PHP 8.4 CLI runner image.
