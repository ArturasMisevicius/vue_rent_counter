#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPOSITORY_PATH="${GPT_RESEARCHER_MCP_PATH:-$PROJECT_ROOT/storage/app/mcp/gptr-mcp}"
SERVER_PATH="$REPOSITORY_PATH/server.py"
VENV_PYTHON="$REPOSITORY_PATH/.venv/bin/python"

if [[ -f "$PROJECT_ROOT/.env" ]]; then
  set +u
  set -a
  source "$PROJECT_ROOT/.env"
  set +a
  set -u
fi

if [[ ! -f "$SERVER_PATH" ]]; then
  echo "GPT Researcher MCP server is not installed at $REPOSITORY_PATH. Run 'php artisan gptr-mcp:install --no-interaction' first." >&2
  exit 1
fi

PYTHON_BINARY="${GPT_RESEARCHER_MCP_PYTHON:-python3}"

if [[ -x "$VENV_PYTHON" ]]; then
  PYTHON_BINARY="$VENV_PYTHON"
fi

if ! "$PYTHON_BINARY" -c 'import sys; raise SystemExit(0 if sys.version_info >= (3, 11) else 1)' >/dev/null 2>&1; then
  PYTHON_VERSION="$("$PYTHON_BINARY" --version 2>&1 || printf 'unavailable')"
  echo "GPT Researcher MCP requires Python 3.11 or newer. $PYTHON_BINARY resolved to $PYTHON_VERSION." >&2
  exit 1
fi

if [[ -z "${OPENAI_API_KEY:-}" ]]; then
  echo "OPENAI_API_KEY is not set. Add it to $PROJECT_ROOT/.env before starting GPT Researcher MCP." >&2
  exit 1
fi

export MCP_TRANSPORT="${GPT_RESEARCHER_MCP_TRANSPORT:-stdio}"

cd "$REPOSITORY_PATH"

exec "$PYTHON_BINARY" "$SERVER_PATH"
