#!/usr/bin/env bash
# Expose storage/app/public at public/storage (Laravel uploads: logos, gallery).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LINK="$ROOT/public/storage"
TARGET="$ROOT/storage/app/public"

mkdir -p "$TARGET"

if [[ -e "$LINK" && ! -L "$LINK" ]]; then
    echo "Refusing to replace non-symlink: $LINK" >&2
    exit 1
fi

ln -sfn "../storage/app/public" "$LINK"
echo "Linked $LINK -> ../storage/app/public"