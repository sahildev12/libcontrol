#!/usr/bin/env bash
# Expose libcontrol-website/assets at public/assets so Apache serves images statically.
# PHP file responses break Hostinger CDN image optimization (422 Invalid source image).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TARGET="$ROOT/libcontrol-website/assets"
LINK="$ROOT/public/assets"

if [[ ! -d "$TARGET" ]]; then
    echo "Missing marketing assets directory: $TARGET" >&2
    exit 1
fi

if [[ -e "$LINK" && ! -L "$LINK" ]]; then
    echo "Refusing to replace non-symlink: $LINK" >&2
    exit 1
fi

ln -sfn "../libcontrol-website/assets" "$LINK"
echo "Linked $LINK -> ../libcontrol-website/assets"
