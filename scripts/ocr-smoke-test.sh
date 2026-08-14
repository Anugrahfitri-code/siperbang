#!/usr/bin/env bash
set -euo pipefail

# 1. Resolve repository root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"

# 2. Check token
if [ -z "${OCR_SERVICE_TOKEN:-}" ]; then
    echo "FAIL: OCR_SERVICE_TOKEN is not set."
    exit 1
fi

# 3. Check dependencies
if ! command -v curl >/dev/null 2>&1; then
    echo "FAIL: curl is required but not installed."
    exit 1
fi
if ! command -v php >/dev/null 2>&1; then
    echo "FAIL: php is required but not installed."
    exit 1
fi

# 4. Resolve fixture
FIXTURE_PATH="$REPO_ROOT/ocr-service/tests/fixtures/synthetic-smoke-receipt.png"
if [ ! -f "$FIXTURE_PATH" ]; then
    echo "FAIL: Synthetic fixture not found at $FIXTURE_PATH"
    exit 1
fi
if [ ! -s "$FIXTURE_PATH" ]; then
    echo "FAIL: Synthetic fixture is empty."
    exit 1
fi

OCR_BASE_URL="${OCR_SERVICE_URL:-http://127.0.0.1:8001}"
# Normalize trailing slash
OCR_BASE_URL="${OCR_BASE_URL%/}"

# 5. Temporary response cleanup
TEMP_RESP=$(mktemp)
trap 'rm -f "$TEMP_RESP"' EXIT

echo "Checking OCR health..."
if ! curl --silent --show-error --fail --max-time 10 "$OCR_BASE_URL/health" > /dev/null; then
    echo "FAIL: OCR health check failed."
    exit 1
fi
echo "PASS: OCR health endpoint is ready."

echo "Running authenticated OCR smoke test..."
HTTP_STATUS=$(curl --silent --show-error --write-out "%{http_code}" --output "$TEMP_RESP" \
    --request POST "$OCR_BASE_URL/internal/v1/receipt-ocr" \
    --header "X-Service-Token: ${OCR_SERVICE_TOKEN}" \
    --form "document=@${FIXTURE_PATH};type=image/png" \
    --max-time 110 --connect-timeout 5) || {
        echo "FAIL: curl transport or network error occurred during OCR request."
        exit 1
}

if [ "$HTTP_STATUS" -ne 200 ]; then
    echo "FAIL: OCR request returned HTTP $HTTP_STATUS."
    echo "Check OCR container logs for details."
    exit 1
fi

# 6. JSON Validation and Assertions
php -r '
$json = file_get_contents($argv[1]);
if ($json === false) {
    echo "FAIL: Error reading response JSON.\n";
    exit(1);
}

$data = json_decode($json);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "FAIL: OCR response is not valid JSON.\n";
    exit(1);
}

if (!is_object($data)) {
    echo "FAIL: OCR response root is not an object.\n";
    exit(1);
}

if (!isset($data->success) || $data->success !== true) {
    echo "FAIL: OCR response success flag is invalid.\n";
    exit(1);
}

if (!isset($data->pages) || !is_array($data->pages) || count($data->pages) === 0) {
    echo "FAIL: OCR response pages is missing or empty.\n";
    exit(1);
}

if (property_exists($data, "document") && $data->document !== null && !is_object($data->document)) {
    echo "FAIL: OCR response document has invalid structure.\n";
    exit(1);
}
' "$TEMP_RESP" || exit 1

echo "PASS: OCR authenticated smoke test succeeded."
exit 0
