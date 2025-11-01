#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
API_KEY_VALUE="${API_KEY:-devkey}"

php "${ROOT_DIR}/scripts/init_db.php"

SERVER_LOG="$(mktemp)"
php -S 127.0.0.1:8000 -t "${ROOT_DIR}/public" >"${SERVER_LOG}" 2>&1 &
SERVER_PID=$!

cleanup() {
    if kill -0 "${SERVER_PID}" >/dev/null 2>&1; then
        kill "${SERVER_PID}" >/dev/null 2>&1 || true
        wait "${SERVER_PID}" >/dev/null 2>&1 || true
    fi
    rm -f "${SERVER_LOG}"
}
trap cleanup EXIT

for _ in $(seq 1 20); do
    if curl --fail --silent --show-error \
        -H "X-Api-Key: ${API_KEY_VALUE}" \
        "http://127.0.0.1:8000/health" \
        > /dev/null 2>&1; then
        break
    fi
    sleep 0.5
    if ! kill -0 "${SERVER_PID}" >/dev/null 2>&1; then
        cat "${SERVER_LOG}" >&2
        exit 1
    fi
done

curl --fail --silent --show-error \
    -H "X-Api-Key: ${API_KEY_VALUE}" \
    "http://127.0.0.1:8000/health"
echo

curl --fail --silent --show-error \
    -H "X-Api-Key: ${API_KEY_VALUE}" \
    "http://127.0.0.1:8000/assets"
echo

ASSET_NAME="Check Asset $(date +%s)"
CREATE_RESPONSE=$(curl --fail --silent --show-error \
    -X POST \
    -H "X-Api-Key: ${API_KEY_VALUE}" \
    -H "Content-Type: application/json" \
    -d "{\"name\":\"${ASSET_NAME}\"}" \
    "http://127.0.0.1:8000/assets")

echo "${CREATE_RESPONSE}"

NEW_ASSET_ID=$(echo "${CREATE_RESPONSE}" | php -r ' $data=json_decode(stream_get_contents(STDIN), true); if(!is_array($data)||!isset($data["data"]["id"])) exit(1); echo $data["data"]["id"]; ')

if [[ -z "${NEW_ASSET_ID}" ]]; then
    echo "Failed to capture new asset ID" >&2
    exit 1
fi

REQUEST_NO="REQ-CHECK-$(date +%s)"
ASSIGN_PAYLOAD=$(php -r '[$user,$project,$no]=array_slice($argv,1); echo json_encode(["user_id"=>(int)$user,"project_id"=>(int)$project,"no"=>$no]);' 1 1 "${REQUEST_NO}")

curl --fail --silent --show-error \
    -X POST \
    -H "X-Api-Key: ${API_KEY_VALUE}" \
    -H "X-User-Id: 1" \
    -H "Content-Type: application/json" \
    -d "${ASSIGN_PAYLOAD}" \
    "http://127.0.0.1:8000/assets/${NEW_ASSET_ID}/assign"
echo

curl --fail --silent --show-error \
    -X POST \
    -H "X-Api-Key: ${API_KEY_VALUE}" \
    -H "X-User-Id: 1" \
    -H "Content-Type: application/json" \
    -d "${ASSIGN_PAYLOAD}" \
    "http://127.0.0.1:8000/assets/${NEW_ASSET_ID}/assign"
echo

RETURN_PAYLOAD=$(php -r '[$user,$project,$no]=array_slice($argv,1); echo json_encode(["user_id"=>(int)$user,"project_id"=>(int)$project,"no"=>$no]);' 1 1 "RET-CHECK-$(date +%s)")

curl --fail --silent --show-error \
    -X POST \
    -H "X-Api-Key: ${API_KEY_VALUE}" \
    -H "X-User-Id: 1" \
    -H "Content-Type: application/json" \
    -d "${RETURN_PAYLOAD}" \
    "http://127.0.0.1:8000/assets/${NEW_ASSET_ID}/return"
echo

echo "All checks passed." >&2
