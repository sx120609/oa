# Constraints Overview

## Runtime Environment
- PHP 8.2 with Laravel 11.
- MySQL 8 for primary relational storage.
- Redis for queues, cache, and Horizon metrics.
- Laravel Sanctum for API authentication.
- Spatie Permission for RBAC.
- Laravel Telescope and Horizon for observability and queue management.
- Laravel-Excel for import/export workflows.
- Sentry for application error monitoring.

## Directory Conventions
- Domain orchestration resides in `app/Domain/*` (e.g., `Services`, `Policies`).
- HTTP layer code stays under `app/Http/*`.
- Database schema migrations belong to `database/migrations`.
- End-to-end lifecycle coverage uses feature specs under `tests/Feature/*`.

## State Machines
### `Asset.status`
`draft → purchased → in_stock → in_use ↔ under_repair → disposed`

### `RepairOrder.status`
`created → assigned → diagnosed → waiting_parts → repairing → qa → closed | scrapped`

## Audit & Observability
- Persist operator identifier, previous and next status, full `changes` JSON payload, request ID, and request source for each audited action.
- Propagate and honor a global `X-Request-Id` header across all entrypoints.

## Numbering & Idempotency
- Asset numbers follow `AS{yyyy}{seq6}` and repair orders follow `RO{yyyy}{seq6}`.
- All write operations must be idempotent based on the business `no`; repeated submissions with the same number return the existing resource.

## RBAC Model
- Roles: `applicant`, `dept_manager`, `project_manager`, `asset_admin`, `storekeeper`, `technician`, `auditor`, `admin`.
- Authorizations must consider both resource ownership and project scope.

## API Contract
- Standard response envelope contains `data`, `meta`, and `errors` sections.
- Maintain a documented error code catalogue, including explicit authentication and authorization failure examples.

## Quality Gates
- PHPUnit test coverage must include the critical lifecycle paths.
- Static analysis with PHPStan (maximum level) and Larastan is mandatory.
- Code style enforced via Pint (or PHP-CS-Fixer equivalent).
- CI pipelines must execute the full suite: tests, static analysis, and formatting checks.
