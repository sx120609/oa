#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
COOKIE_JAR="$(mktemp)"
TMP_SQL="$(mktemp)"

cleanup() {
  rm -f "$COOKIE_JAR" "$TMP_SQL"
}
trap cleanup EXIT

info() { printf "\n== %s ==\n" "$1"; }

MYSQL_HOST=${MYSQL_HOST:-127.0.0.1}
MYSQL_PORT=${MYSQL_PORT:-3306}
MYSQL_USER=${MYSQL_USER:-root}
MYSQL_PASSWORD=${MYSQL_PASSWORD:-}
MYSQL_DB=${MYSQL_DB:-app_db}

mysql_exec() {
  MYSQL_PWD="$MYSQL_PASSWORD" mysql --protocol=TCP -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" "$@"
}

mysql_db() {
  mysql_exec -D "$MYSQL_DB" "$@"
}

future_iso() {
  local hours="$1"
  if date -u -d "0 hour" >/dev/null 2>&1; then
    date -u -d "${hours} hour" +"%Y-%m-%dT%H:%M:%S"
  else
    date -u -v "${hours}H" +"%Y-%m-%dT%H:%M:%S"
  fi
}

# 1. Seed user (manual DB insert)
info "Seeding users table"
cat > "$TMP_SQL" <<'SQL'
INSERT INTO users (email, name, password_hash, role, created_at)
VALUES ('owner@example.com', 'Owner', '$2y$10$K8zVvN6wX6Zl6VdI6yYB1.MX4T5xFZbCW9HEIblzEl3bLTsayDb/m', 'owner', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);
SQL
mysql_db < "$TMP_SQL"

# Prime session
curl -sS -c "$COOKIE_JAR" "$BASE_URL/" >/dev/null
TOKEN=$(awk '/^X-CSRF-Token:/ {print $2}' <(curl -si -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/") | tr -d '\r')
echo "CSRF token: $TOKEN"

# 2. Login success/failure
info "Login success"
curl -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$BASE_URL/login" \
  -d "_token=$TOKEN&email=owner@example.com&password=secretpass"
printf "\n"

info "Login failure"
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/login" \
  -d "_token=$TOKEN&email=owner@example.com&password=wrong" || true

# Refresh token after successful login
TOKEN=$(awk '/_csrf_token/ {print $7}' "$COOKIE_JAR" | tail -n1)
echo "Session CSRF token: $TOKEN"

# 3. Create project success
info "Create project success"
START=$(future_iso 0)
DUE=$(future_iso 1)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/projects/create" \
  -d "_token=$TOKEN&name=Test+Project&location=HQ&starts_at=$START&due_at=$DUE&quote_amount=1000&note=demo"
printf "\n"

PROJECT_ID=$(mysql_db -N -e "SELECT id FROM projects ORDER BY id DESC LIMIT 1")
echo "Project ID: $PROJECT_ID"

# Create project with invalid time
info "Create project invalid time"
START=$(future_iso 2)
DUE=$(future_iso 1)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/projects/create" \
  -d "_token=$TOKEN&name=Bad+Project&location=HQ&starts_at=$START&due_at=$DUE"
printf "\n"

# 4. Create device success
info "Create device success"
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/devices/create" \
  -d "_token=$TOKEN&code=DEV-001&model=Camera&serial=SN123"
printf "\n"

DEVICE_ID=$(mysql_db -N -e "SELECT id FROM devices WHERE code='DEV-001'")
echo "Device ID: $DEVICE_ID"

# Duplicate code
info "Create device duplicate"
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/devices/create" \
  -d "_token=$TOKEN&code=DEV-001&model=Camera"
printf "\n"

# 5. Reserve success
info "Reserve device success"
FROM=$(future_iso 4)
TO=$(future_iso 5)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/reservations/create" \
  -d "_token=$TOKEN&project_id=$PROJECT_ID&device_id=$DEVICE_ID&from=$FROM&to=$TO"
printf "\n"

# Reserve conflict (overlap)
info "Reserve conflict"
FROM=$(future_iso 4)
TO=$(future_iso 6)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/reservations/create" \
  -d "_token=$TOKEN&project_id=$PROJECT_ID&device_id=$DEVICE_ID&from=$FROM&to=$TO"
printf "\n"

# 6. Checkout success
info "Checkout success"
NOW=$(future_iso 4)
DUE=$(future_iso 7)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/checkouts/create" \
  -d "_token=$TOKEN&device_id=$DEVICE_ID&project_id=$PROJECT_ID&now=$NOW&due=$DUE&note=first"
printf "\n"

CHECKOUT_ID=$(mysql_db -N -e "SELECT id FROM checkouts ORDER BY id DESC LIMIT 1")
echo "Checkout ID: $CHECKOUT_ID"

# Checkout failure (device already checked out)
info "Checkout failure"
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/checkouts/create" \
  -d "_token=$TOKEN&device_id=$DEVICE_ID&project_id=$PROJECT_ID&now=$NOW&due=$DUE"
printf "\n"

# 7. Return success
info "Return success"
RETURN_TIME=$(future_iso 8)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/returns/create" \
  -d "_token=$TOKEN&device_id=$DEVICE_ID&now=$RETURN_TIME&note=returned"
printf "\n"

# Return failure (no open checkout)
info "Return failure - no open checkout"
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/returns/create" \
  -d "_token=$TOKEN&device_id=$DEVICE_ID&now=$RETURN_TIME"
printf "\n"

# Return overdue notification
info "Return overdue notification"
NOW=$(future_iso 0)
DUE=$(future_iso 1)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/checkouts/create" \
  -d "_token=$TOKEN&device_id=$DEVICE_ID&project_id=$PROJECT_ID&now=$NOW&due=$DUE"
printf "\n"

sleep 2
PAST=$(future_iso -2)
curl -sS -b "$COOKIE_JAR" \
  -X POST "$BASE_URL/returns/create" \
  -d "_token=$TOKEN&device_id=$DEVICE_ID&now=$PAST"
printf "\n"

# 8. Run notification script
info "Run due notification script"
php bin/notify_due.php

info "Final database state checks"
mysql_db -e "SELECT id, action, entity_type, created_at FROM audit_logs ORDER BY id DESC LIMIT 10"
mysql_db -e "SELECT id, status FROM devices WHERE id = $DEVICE_ID"
mysql_db -e "SELECT id, title, body, created_at FROM notifications ORDER BY id DESC LIMIT 10"
