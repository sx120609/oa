# Device Lifecycle Domain Specification

## Platform Baseline
- **Runtime stack:** PHP 8.2, Laravel 11.
- **Infrastructure:** MySQL 8 (primary persistence), Redis (cache, queue, Horizon metrics).
- **Framework services:** Laravel Sanctum for token authentication, Spatie Permission for RBAC, Telescope & Horizon for observability, Laravel-Excel for data exchange, Sentry for error tracking.

## Architectural Boundaries
- Domain services and policies live inside `app/Domain/` (e.g., `app/Domain/Services/`, `app/Domain/Policies/`).
- HTTP controllers, requests, and resources are restricted to `app/Http/`.
- Migrations are added under `database/migrations/` with chronological ordering.
- Behavioural coverage is authored in `tests/Feature/`.

## Lifecycle State Machines
### Asset
`draft → purchased → in_stock → in_use ↔ under_repair → disposed`
- `draft`: device definition before procurement approval.
- `purchased`: requisition completed with purchase order recorded.
- `in_stock`: received in store but not yet assigned.
- `in_use`: assigned to an owner or project.
- `under_repair`: temporarily withdrawn for repair; can transition back to `in_use`.
- `disposed`: permanently retired; no further state changes.

### Repair Order
`created → assigned → diagnosed → waiting_parts → repairing → qa → closed | scrapped`
- `created`: request registered.
- `assigned`: technician or team allocated.
- `diagnosed`: issue assessment documented.
- `waiting_parts`: pending replacement components.
- `repairing`: active repair in progress.
- `qa`: quality assurance / validation stage.
- `closed`: repair successful, device can re-enter lifecycle.
- `scrapped`: repair led to disposal decision.

## Auditing & Traceability
- Every command captures operator identity, previous state, next state, a structured `changes` JSON diff, request identifier, and origin/source metadata.
- All inbound API traffic must supply or receive a generated `X-Request-Id`, propagated to logs, audit records, and downstream integrations.

## Numbering & Idempotency Rules
- Asset numbers follow `AS{yyyy}{seq6}` (e.g., `AS2024000123`).
- Repair order numbers follow `RO{yyyy}{seq6}`.
- The `no` field is globally unique per workflow; repeated submissions with the same number must return the existing resource without side effects.

## Role-Based Access Control
- Supported roles: `applicant`, `dept_manager`, `project_manager`, `asset_admin`, `storekeeper`, `technician`, `auditor`, `admin`.
- Authorization checks combine role assignments with resource ownership/project scope evaluations.
- Spatie Permission manages role/permission bindings; policies consult both role and contextual scope.

## API Contract Expectations
- Responses use a consistent envelope:
  ```json
  {
    "data": { /* resource payload or null */ },
    "meta": { "request_id": "...", "pagination": { /* optional */ } },
    "errors": [
      { "code": "ASSET.NOT_FOUND", "title": "Asset not found", "detail": "..." }
    ]
  }
  ```
- Maintain an error code registry covering validation, authentication (`AUTH.UNAUTHENTICATED`), authorization (`AUTH.FORBIDDEN`), idempotency conflicts, and domain invariants.
- Example authentication failure:
  ```json
  {
    "data": null,
    "meta": { "request_id": "..." },
    "errors": [
      { "code": "AUTH.UNAUTHENTICATED", "title": "Unauthenticated", "detail": "Sanctum token missing or expired." }
    ]
  }
  ```
- Example authorization failure:
  ```json
  {
    "data": null,
    "meta": { "request_id": "..." },
    "errors": [
      { "code": "AUTH.FORBIDDEN", "title": "Forbidden", "detail": "Role lacks asset_admin privileges for this project." }
    ]
  }
  ```

## Quality Assurance Pipeline
- PHPUnit feature tests must exercise the complete asset lifecycle and repair order flows.
- Static analysis via PHPStan (max level) and Larastan is part of the CI gate.
- Coding standards enforced through Laravel Pint (or compatible PHP-CS-Fixer ruleset).
- CI workflow stages: install dependencies → run linters (Pint) → static analysis (PHPStan/Larastan) → tests (PHPUnit) → artifact/report publication.
