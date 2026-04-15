# Accessing Docker test runtime

This directory contains the Docker-backed test surface for the Accessing Symfony application.

## Purpose

The default `composer test` command should remain a fast local contour. The Docker contour provides a reproducible PostgreSQL-backed runtime for integration and functional checks that should not depend on a developer machine having a local database named `app`.

## Services

- `postgres`: PostgreSQL 17 test database.
- `php`: optional PHP 8.4 CLI runner profile for fully containerized test execution.

## Host-driven PostgreSQL test run

```bash
docker compose -f deploy/docker/compose.yaml --env-file deploy/docker/.env up -d postgres
composer test:postgres
```

The host runner uses `127.0.0.1:${ACCESSING_POSTGRES_PORT}` and the credentials from `deploy/docker/.env`.

## Fully containerized test run

```bash
docker compose -f deploy/docker/compose.yaml --env-file deploy/docker/.env --profile app run --rm php
```

This uses the `postgres` service hostname from inside Docker.

## Shutdown

```bash
docker compose -f deploy/docker/compose.yaml --env-file deploy/docker/.env down
```

To delete the PostgreSQL volume as well:

```bash
docker compose -f deploy/docker/compose.yaml --env-file deploy/docker/.env down -v
```
