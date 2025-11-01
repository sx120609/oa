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

CONFLICT_NO_A="REQ-CONFLICT-${RANDOM}A"
CONFLICT_NO_B="REQ-CONFLICT-${RANDOM}B"

PARALLEL_ASSET_ID="${NEW_ASSET_ID}" \
PARALLEL_API_KEY="${API_KEY_VALUE}" \
PARALLEL_NO_A="${CONFLICT_NO_A}" \
PARALLEL_NO_B="${CONFLICT_NO_B}" \
php <<'PHP'
<?php
$assetId = (int) getenv('PARALLEL_ASSET_ID');
$apiKey = getenv('PARALLEL_API_KEY');
$noA = getenv('PARALLEL_NO_A');
$noB = getenv('PARALLEL_NO_B');

if ($assetId <= 0 || $apiKey === false || $noA === false || $noB === false) {
    throw new RuntimeException('Missing environment for parallel assignment test.');
}

$endpoint = sprintf('http://127.0.0.1:8000/assets/%d/assign', $assetId);
$headers = [
    'X-Api-Key: ' . $apiKey,
    'X-User-Id: 1',
    'Content-Type: application/json',
];

$requests = [
    ['payload' => json_encode(['user_id' => 1, 'project_id' => 1, 'no' => $noA], JSON_THROW_ON_ERROR)],
    ['payload' => json_encode(['user_id' => 1, 'project_id' => 1, 'no' => $noB], JSON_THROW_ON_ERROR)],
];

$multi = curl_multi_init();

foreach ($requests as $index => &$request) {
    $handle = curl_init($endpoint);
    curl_setopt_array($handle, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $request['payload'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $request['handle'] = $handle;
    curl_multi_add_handle($multi, $handle);
}

unset($request);

$running = null;
do {
    $status = curl_multi_exec($multi, $running);
    if ($status !== CURLM_OK && $status !== CURLM_CALL_MULTI_PERFORM) {
        throw new RuntimeException('cURL multi error: ' . curl_multi_strerror($status));
    }

    if ($running > 0) {
        $selectResult = curl_multi_select($multi, 1.0);
        if ($selectResult === -1) {
            usleep(100000);
        }
    }
} while ($running > 0);

$summary = ['success' => 0, 'conflict' => 0];

foreach ($requests as $request) {
    $handle = $request['handle'];
    $response = curl_multi_getcontent($handle);
    $statusCode = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

    curl_multi_remove_handle($multi, $handle);
    curl_close($handle);

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Failed to decode JSON response: ' . $response);
    }

    if ($statusCode === 200) {
        $summary['success']++;
    } elseif ($statusCode === 409 && ($decoded['error'] ?? '') === 'conflict') {
        $summary['conflict']++;
    } else {
        throw new RuntimeException(sprintf('Unexpected response: HTTP %d %s', $statusCode, $response));
    }
}

curl_multi_close($multi);

if ($summary['success'] !== 1 || $summary['conflict'] !== 1) {
    throw new RuntimeException('Expected one success and one conflict response from parallel assignments.');
}

PHP
echo "All checks passed." >&2
