# Device Lifecycle API Reference

## Overview
- **Runtime**: Framework-free PHP 8 single-entry application (`public/index.php`).
- **Database**: MySQL (PDO). Configure connection via environment variables (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`).
- **Authentication**: All requests require the header `X-Api-Key`. Default key is `devkey`; override with the `API_KEY` environment variable.
- **Actors**: User accounts carry a `role` (`technician`, `asset_admin`, `admin`). Certain endpoints require `X-User-Id` to resolve the caller and enforce role-based authorisation.
- **Conventions**: Responses use JSON envelopes: `{"data": ...}` for success, `{"error": "code", "message": "..."}` for failures. Request bodies are JSON unless noted otherwise.

## Headers
| Header | Required | Description |
| --- | --- | --- |
| `X-Api-Key` | Yes | API key used to authenticate the client. |
| `Content-Type: application/json` | For POST requests | Required when sending a JSON body. |
| `X-User-Id` | For protected endpoints | Integer user ID that must map to a row in `users`. Used by role checks. |

## Error Model
```json
{"error": "<code>", "message": "<description>"}
```
Optional `details` may appear for validation hints.

| HTTP Status | `error` code | When it occurs |
| --- | --- | --- |
| 400 | `invalid_json` | Malformed JSON payload. |
| 401 | `unauthorized` | API key missing or invalid. |
| 403 | `forbidden` | Caller lacks the required role (e.g. missing `X-User-Id`). |
| 404 | `not_found` | Target asset or repair order not present. |
| 409 | `invalid_status` | Operation not allowed for the asset/repair order state. |
| 409 | `conflict` | Optimistic-lock or concurrent update conflict. Retry after refreshing state. |
| 422 | `name_required`, `validation_error` | Input validation failures. |

## Endpoints

### GET `/health`
Returns service liveness info.

**Response**
```json
{
  "data": {
    "status": "ok",
    "timestamp": "2024-04-16 12:34:56"
  }
}
```

**cURL**
```bash
curl -H "X-Api-Key: devkey" http://127.0.0.1:8000/health
```

---

### GET `/assets`
List assets, optionally filtered by `status` (`in_stock`, `in_use`, `under_repair`).

**Query Parameters**
- `status` (optional): Filter assets by lifecycle state.

**Response**
```json
{
  "data": {
    "items": [
      {
        "id": 2,
        "name": "3D Printer Mark II",
        "model": "Mark II",
        "status": "in_use",
        "created_at": "2024-04-15 00:00:00",
        "updated_at": "2024-04-15 00:00:00"
      }
    ]
  }
}
```

**cURL**
```bash
curl -H "X-Api-Key: devkey" "http://127.0.0.1:8000/assets?status=in_stock"
```

---

### POST `/assets`
Create a new asset. Defaults to `status = in_stock`.

**Body**
```json
{
  "name": "Label Printer",
  "model": "LP-500"
}
```

**Responses**
- `201 Created`
  ```json
  { "data": { "id": 5 } }
  ```
- `422 Unprocessable Entity` – missing name
  ```json
  { "error": "name_required", "message": "Asset name is required" }
  ```

**cURL**
```bash
curl -X POST http://127.0.0.1:8000/assets \
  -H "X-Api-Key: devkey" \
  -H "Content-Type: application/json" \
  -d '{"name":"Label Printer","model":"LP-500"}'
```

---

### GET `/assets/{id}`
Placeholder endpoint returning asset metadata stub. Useful for debugging the route.

**Response**
```json
{
  "data": {
    "message": "Asset detail endpoint placeholder",
    "asset_id": "1"
  }
}
```

---

### POST `/assets/{id}/assign`
Assign an asset to a user/project. Requires role `asset_admin` or `admin`.

**Headers**
- `X-User-Id`: ID of the caller. Must belong to a user with allowed role.

**Body**
```json
{
  "user_id": 1,
  "project_id": 1,
  "no": "REQ-202404-001"
}
```

**Success (`200 OK`)**
```json
{
  "data": {
    "asset": {
      "id": 2,
      "name": "3D Printer Mark II",
      "model": "Mark II",
      "status": "in_use",
      "created_at": "2024-04-15 00:00:00",
      "updated_at": "2024-04-16 08:30:00"
    },
    "usage": {
      "id": 3,
      "asset_id": 2,
      "user_id": 1,
      "project_id": 1,
      "request_no": "REQ-202404-001",
      "type": "assign",
      "occurred_at": "2024-04-16 08:30:00"
    },
    "idempotent": false
  }
}
```

**Idempotent retry**
If the same `no` already exists for this asset assignment, the service returns the recorded usage with `"idempotent": true`.

**Failure cases**
- `403 forbidden` – caller not `asset_admin`/`admin`.
- `404 not_found` – asset missing.
- `409 invalid_status` – asset not in `in_stock`/`in_use`.
- `409 conflict` – optimistic lock failed; refetch the asset and retry with a new business `no`.
- `422 validation_error` – missing `user_id`, `project_id`, or `no`.

**cURL**
```bash
curl -X POST http://127.0.0.1:8000/assets/2/assign \
  -H "X-Api-Key: devkey" \
  -H "X-User-Id: 1" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"project_id":1,"no":"REQ-202404-001"}'
```

---

### POST `/assets/{id}/return`
Return an asset back to inventory. Requires role `asset_admin` or `admin`.

**Body**
```json
{
  "user_id": 1,
  "project_id": 1,
  "no": "RET-202404-001"
}
```

**Success (`200 OK`)**
```json
{
  "data": {
    "asset": {
      "id": 2,
      "status": "in_stock",
      "updated_at": "2024-04-16 10:00:00"
    },
    "usage": {
      "type": "return",
      "request_no": "RET-202404-001",
      "occurred_at": "2024-04-16 10:00:00"
    },
    "idempotent": false
  }
}
```

**Failure cases** mirror the assignment endpoint, with an additional `409 invalid_status` when the asset is not currently `in_use`.

**cURL**
```bash
curl -X POST http://127.0.0.1:8000/assets/2/return \
  -H "X-Api-Key: devkey" \
  -H "X-User-Id: 1" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"project_id":1,"no":"RET-202404-001"}'
```

---

### GET `/repairs`
Placeholder returning a stub response. Extend this endpoint to implement repair order listings.

---

### POST `/repair-orders`
Create a repair order and move the asset into `under_repair`.

**Body**
```json
{
  "asset_id": 2,
  "symptom": "Extruder jammed"
}
```

**Success (`201 Created`)**
```json
{
  "data": {
    "order": {
      "id": 5,
      "asset_id": 2,
      "status": "created",
      "description": "Extruder jammed",
      "created_at": "2024-04-16 11:00:00",
      "updated_at": "2024-04-16 11:00:00"
    }
  }
}
```

**Failure cases**
- `404 not_found` – asset missing.
- `409 invalid_status` – asset not `in_use`.
- `409 conflict` – asset changed since last read.
- `422 validation_error` – missing `asset_id` or `symptom`.

**cURL**
```bash
curl -X POST http://127.0.0.1:8000/repair-orders \
  -H "X-Api-Key: devkey" \
  -H "Content-Type: application/json" \
  -d '{"asset_id":2,"symptom":"Extruder jammed"}'
```

---

### POST `/repair-orders/{id}/close`
Close a repair order (`created`, `repairing`, or `qa` → `closed`) and return the asset to `in_use`.

**Success (`200 OK`)**
```json
{
  "data": {
    "order": {
      "id": 5,
      "asset_id": 2,
      "status": "closed",
      "description": "Extruder jammed",
      "created_at": "2024-04-16 11:00:00",
      "updated_at": "2024-04-16 12:15:00"
    }
  }
}
```

**Failure cases**
- `404 not_found` – order or asset missing.
- `409 invalid_status` – order not in a closable state, or asset not `under_repair`.
- `409 conflict` – order or asset updated concurrently.

**cURL**
```bash
curl -X POST http://127.0.0.1:8000/repair-orders/5/close \
  -H "X-Api-Key: devkey"
```

---

### GET `/reports/summary`
Placeholder stub for future summary reporting.

---

### GET `/reports/costs`
Aggregate per-asset repair spend (labor + parts costs).

**Response**
```json
{
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Dell Latitude 7440",
        "model": "7440",
        "total_cost": 0
      },
      {
        "id": 2,
        "name": "3D Printer Mark II",
        "model": "Mark II",
        "total_cost": 165.5
      }
    ],
    "generated_at": "2024-04-16 12:34:56"
  }
}
```

**cURL**
```bash
curl -H "X-Api-Key: devkey" http://127.0.0.1:8000/reports/costs
```

## Typical Business Flow
End-to-end example demonstrating the lifecycle: create asset → assign → create repair order → close repair → return.

```bash
# 1. Create a fresh asset (defaults to in_stock)
curl -s -X POST http://127.0.0.1:8000/assets \
  -H "X-Api-Key: devkey" \
  -H "Content-Type: application/json" \
  -d '{"name":"Laser Cutter","model":"LC-9000"}'
# => {"data":{"id":6}}

# 2. Assign it to a project (caller must be asset_admin or admin)
curl -s -X POST http://127.0.0.1:8000/assets/6/assign \
  -H "X-Api-Key: devkey" \
  -H "X-User-Id: 1" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"project_id":1,"no":"REQ-202404-900"}'
# => returns updated asset + usage record

# 3. File a repair order when an issue is detected
curl -s -X POST http://127.0.0.1:8000/repair-orders \
  -H "X-Api-Key: devkey" \
  -H "Content-Type: application/json" \
  -d '{"asset_id":6,"symptom":"Lens alignment"}'
# => asset transitions to under_repair

# 4. Close the repair once QA passes
curl -s -X POST http://127.0.0.1:8000/repair-orders/<orderId>/close \
  -H "X-Api-Key: devkey"
# => repair order status becomes closed, asset returns to in_use

# 5. Return the asset to inventory after user hand-back
curl -s -X POST http://127.0.0.1:8000/assets/6/return \
  -H "X-Api-Key: devkey" \
  -H "X-User-Id: 1" \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"project_id":1,"no":"RET-202404-900"}'
# => asset status resets to in_stock and logs a return usage
```

> **Tip:** Parallel assignment attempts trigger the optimistic-lock guard. When `{ "error": "conflict" }` is returned, refresh the asset view, pick a new business `no`, and submit the assignment again.
