#!/usr/bin/env bash

set -euo pipefail

echo "=================================================="
echo "SIPERBANG DEPLOYMENT PREFLIGHT"
echo "=================================================="

WITH_OCR=0
if [ $# -gt 0 ]; then
    if [[ "$1" == "--with-ocr" ]]; then
        WITH_OCR=1
    elif [[ "$1" == "--help" ]]; then
        echo "Usage: bash scripts/deployment/preflight.sh [--with-ocr]"
        echo "default: application prerequisites"
        echo "--with-ocr: also Docker/OCR architecture and token synchronization"
        exit 0
    else
        echo "FAIL: Unknown argument: $1"
        echo "Usage: bash scripts/deployment/preflight.sh [--with-ocr]"
        exit 1
    fi
fi

# Check PHP
if ! command -v php >/dev/null 2>&1; then
    echo "FAIL: php is not installed or not in PATH."
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
if php -r "exit(version_compare(PHP_VERSION, '8.4.0', '>=') ? 0 : 1);"; then
    echo "PASS: PHP version is $PHP_VERSION (>= 8.4.0)."
else
    echo "FAIL: PHP version is $PHP_VERSION. Required is >= 8.4.0."
    exit 1
fi

# Check PHP Extensions
REQUIRED_EXTS=("dom" "curl" "libxml" "pdo" "pdo_pgsql" "mbstring" "fileinfo" "zip" "gd" "opcache")
for ext in "${REQUIRED_EXTS[@]}"; do
    if [ "$ext" = "opcache" ]; then
        if php -r "exit(extension_loaded('Zend OPcache') || extension_loaded('opcache') ? 0 : 1);"; then
            echo "PASS: PHP extension $ext is loaded"
            continue
        fi
    else
        if php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
            echo "PASS: PHP extension $ext is loaded"
            continue
        fi
    fi
    echo "FAIL: required PHP extension $ext is not loaded"
    exit 1
done

# Check Composer
if ! command -v composer >/dev/null 2>&1; then
    echo "FAIL: composer is not installed or not in PATH."
    exit 1
fi

if composer --version | grep -q "Composer version 2"; then
    echo "PASS: Composer major version 2 is installed."
else
    echo "FAIL: Composer major version 2 is required."
    exit 1
fi

# Check Node
if ! command -v node >/dev/null 2>&1; then
    echo "FAIL: node is not installed or not in PATH."
    exit 1
fi

NODE_VERSION=$(node -v | sed 's/^v//')
# Node constraint: ^20.19.0 || >=22.12.0
if node -e "
const semver = process.versions.node.split('.');
const major = parseInt(semver[0]);
const minor = parseInt(semver[1]);
if (major === 20 && minor >= 19) process.exit(0);
if (major >= 22 && (major > 22 || minor >= 12)) process.exit(0);
process.exit(1);
"; then
    echo "PASS: Node version $NODE_VERSION satisfies ^20.19.0 || >=22.12.0"
else
    echo "FAIL: Node version $NODE_VERSION does not satisfy ^20.19.0 || >=22.12.0"
    exit 1
fi

# Check npm
if ! command -v npm >/dev/null 2>&1; then
    echo "FAIL: npm is not installed or not in PATH."
    exit 1
fi
echo "PASS: npm is available."


# Check Composer Platform Reqs
if [ -d "vendor" ]; then
    if composer check-platform-reqs --no-dev >/dev/null 2>&1; then
        echo "PASS: composer platform requirements met."
    else
        echo "FAIL: composer platform requirements not met. Run 'composer check-platform-reqs --no-dev' for details."
        exit 1
    fi
else
    echo "FAIL: vendor directory not found. Please run 'composer install --no-dev' before preflight."
    exit 1
fi

echo "=================================================="
echo "ENVIRONMENT SAFETY"
echo "=================================================="

if [ ! -f ".env" ]; then
    echo "FAIL: .env file is missing."
    exit 1
else
    echo "PASS: .env file exists."
fi

# Function to safely get env without printing it or dumping
get_env() {
    local key=$1
    local file=$2
    awk -F= -v key="$key" '
    BEGIN { found=0 }
    $1 == key {
        val = substr($0, length($1) + 2)
        sub(/\r$/, "", val)
        if (val ~ /^".*"$/ || val ~ /^\047.*\047$/) {
            val = substr(val, 2, length(val) - 2)
        }
        print val
        found=1
        exit 0
    }
    ' "$file" || true
}

APP_ENV=$(get_env "APP_ENV" ".env")
APP_DEBUG=$(get_env "APP_DEBUG" ".env")
APP_URL=$(get_env "APP_URL" ".env")
DB_CONNECTION=$(get_env "DB_CONNECTION" ".env")
DB_HOST=$(get_env "DB_HOST" ".env")
DB_PORT=$(get_env "DB_PORT" ".env")
DB_DATABASE=$(get_env "DB_DATABASE" ".env")
DB_USERNAME=$(get_env "DB_USERNAME" ".env")
DB_PASSWORD=$(get_env "DB_PASSWORD" ".env")
APP_KEY=$(get_env "APP_KEY" ".env")
OCR_SERVICE_URL=$(get_env "OCR_SERVICE_URL" ".env")
OCR_SERVICE_TOKEN=$(get_env "OCR_SERVICE_TOKEN" ".env")

if [ "$APP_ENV" = "production" ]; then
    echo "PASS: APP_ENV=production"
else
    echo "FAIL: APP_ENV must be production"
    exit 1
fi

if [ "$APP_DEBUG" = "false" ]; then
    echo "PASS: APP_DEBUG=false"
else
    echo "FAIL: APP_DEBUG must be false"
    exit 1
fi

if [ -n "$APP_URL" ]; then
    echo "PASS: APP_URL is present"
    if [[ "$APP_URL" != https:* ]]; then
        echo "FAIL: APP_URL must be HTTPS in production"
        exit 1
    fi
else
    echo "FAIL: APP_URL is missing"
    exit 1
fi

if [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "PASS: DB_CONNECTION=pgsql"
else
    echo "FAIL: DB_CONNECTION must be pgsql"
    exit 1
fi

for var in DB_HOST DB_PORT DB_DATABASE DB_USERNAME OCR_SERVICE_URL; do
    val=$(get_env "$var" ".env")
    if [ -n "$val" ]; then
        echo "PASS: $var is present"
    else
        echo "FAIL: $var is missing"
        exit 1
    fi
done

for var in DB_PASSWORD APP_KEY OCR_SERVICE_TOKEN; do
    val=$(get_env "$var" ".env")
    if [ -n "$val" ]; then
        echo "PASS: $var is non-empty"
    else
        echo "FAIL: $var is empty"
        exit 1
    fi
done

echo "=================================================="
echo "OCR PREFLIGHT"
echo "=================================================="


if [ $WITH_OCR -eq 1 ]; then
    if ! command -v docker >/dev/null 2>&1; then
        echo "FAIL: docker is not available"
        exit 1
    else
        echo "PASS: docker is available"
    fi

    if ! docker compose version >/dev/null 2>&1; then
        echo "FAIL: docker compose is not available"
        exit 1
    else
        echo "PASS: docker compose is available"
    fi

    if ! docker info >/dev/null 2>&1; then
        echo "FAIL: docker engine is not reachable"
        exit 1
    else
        echo "PASS: docker engine is reachable"
    fi

    ARCH=$(uname -m)
    if [[ "$ARCH" == "x86_64" || "$ARCH" == "amd64" ]]; then
        echo "PASS: CPU architecture is $ARCH"
    else
        echo "FAIL: OCR HOST ARCHITECTURE NOT ACCEPTED FOR CURRENT PADDLEPADDLE BASELINE"
        exit 1
    fi

    if [ -f "ocr-service/.env" ]; then
        OCR_ENV_TOKEN=$(get_env "OCR_SERVICE_TOKEN" "ocr-service/.env")
        if [ -z "$OCR_ENV_TOKEN" ]; then
            echo "FAIL: OCR token synchronization: FAIL (missing in ocr-service/.env)"
            exit 1
        elif [ "$OCR_SERVICE_TOKEN" = "$OCR_ENV_TOKEN" ]; then
            echo "PASS: OCR token synchronization: PASS"
        else
            echo "FAIL: OCR token synchronization: FAIL (mismatch)"
            exit 1
        fi
    else
        echo "FAIL: OCR token synchronization: FAIL (ocr-service/.env missing)"
        exit 1
    fi
else
    echo "Skipping OCR preflight (use --with-ocr to enable)"
fi

echo "=================================================="
echo "PREFLIGHT COMPLETE: ALL GATES PASSED"
echo "=================================================="
exit 0
