#!/usr/bin/env bash
# Merge deploy/phonepe.env into the hub .env (run on server from repo root).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SOURCE="${1:-$ROOT/deploy/phonepe.env}"
ENV_FILE="${2:-$ROOT/.env}"

if [[ ! -f "$SOURCE" ]]; then
    echo "Missing $SOURCE" >&2
    exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Missing $ENV_FILE" >&2
    exit 1
fi

declare -A MAP
while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line//$'\r'/}"
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" != *"="* ]] && continue
    key="${line%%=*}"
    val="${line#*=}"
    key="$(echo "$key" | xargs)"
    [[ "$key" == PHONEPE_* ]] || continue
    MAP["$key"]="$val"
done < "$SOURCE"

for key in "${!MAP[@]}"; do
    val="${MAP[$key]}"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
        echo "${key}=${val}" >> "$ENV_FILE"
    fi
done

echo "Updated PhonePe keys in $ENV_FILE"
