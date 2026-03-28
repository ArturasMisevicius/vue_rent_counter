#!/bin/sh

set -eu

repo_root="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

git -C "$repo_root" config core.hooksPath .githooks

echo "Git hooks path set to .githooks"
