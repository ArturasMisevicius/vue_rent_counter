#!/usr/bin/env bash

set -Eeuo pipefail

if [[ "${CODEX_AUTO_PUSH_DISABLED:-}" == "1" ]]; then
    exit 0
fi

if [[ "${CODEX_CHANGELOG_COMMIT_RUNNING:-}" == "1" ]]; then
    exit 0
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    exit 0
fi

repo_root="$(git rev-parse --show-toplevel)"
cd "${repo_root}"

log_file="$(git rev-parse --git-path codex-auto-push.log)"
lock_dir="$(git rev-parse --git-path codex-auto-push.lock)"

mkdir -p "$(dirname "${log_file}")"
exec >> "${log_file}" 2>&1

log() {
    printf '%s %s\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$*"
}

if ! mkdir "${lock_dir}" 2>/dev/null; then
    log "skip: another Codex auto-push hook is running"
    exit 0
fi

cleanup() {
    rmdir "${lock_dir}" 2>/dev/null || true
}

trap cleanup EXIT

branch="$(git branch --show-current)"

if [[ -z "${branch}" ]]; then
    log "skip: detached HEAD cannot be pushed safely"
    exit 0
fi

if [[ -e "$(git rev-parse --git-path MERGE_HEAD)" ]] \
    || [[ -d "$(git rev-parse --git-path rebase-merge)" ]] \
    || [[ -d "$(git rev-parse --git-path rebase-apply)" ]]; then
    log "skip: repository has an active merge or rebase"
    exit 0
fi

if [[ -z "$(git status --porcelain=v1)" ]]; then
    log "skip: working tree is clean"
    exit 0
fi

git add -A

if git diff --cached --quiet --exit-code; then
    log "skip: no staged changes after git add"
    exit 0
fi

if ! git diff --cached --check; then
    log "skip: staged diff has whitespace errors"
    exit 0
fi

message_file="$(git rev-parse --git-path codex-commit-message.txt)"

if [[ -x scripts/generate_commit_message.php || -f scripts/generate_commit_message.php ]]; then
    php scripts/generate_commit_message.php \
        --subject="${CODEX_AUTO_PUSH_MESSAGE:-}" \
        > "${message_file}"
else
    printf '%s\n\n%s\n' \
        "${CODEX_AUTO_PUSH_MESSAGE:-chore: update Codex changes}" \
        "Generated from the staged git diff." \
        > "${message_file}"
fi

commit_subject="$(sed -n '1p' "${message_file}")"

if [[ -x scripts/update_changelog.php || -f scripts/update_changelog.php ]]; then
    CODEX_CHANGELOG_LANGUAGE="${CODEX_CHANGELOG_LANGUAGE:-en}" \
        php scripts/update_changelog.php \
        --mode=staged \
        --state-file=.git/tenanto-changelog-entry-id \
        --language="${CODEX_CHANGELOG_LANGUAGE:-en}" \
        --title="${CODEX_CHANGELOG_TITLE:-${commit_subject}}"
    git add CHANGELOG.md
fi

if [[ -f changelog.md && -f CHANGELOG.md && ! changelog.md -ef CHANGELOG.md ]]; then
    cp CHANGELOG.md changelog.md
    git add changelog.md
fi

if [[ -f .codex/hooks.json ]]; then
    python3 -m json.tool .codex/hooks.json >/dev/null
fi

if [[ -f .codex/hooks/auto-changelog-commit-push.sh ]]; then
    bash -n .codex/hooks/auto-changelog-commit-push.sh
fi

if git diff --cached --quiet --exit-code; then
    log "skip: no staged changes after changelog update"
    exit 0
fi

if [[ -x scripts/generate_commit_message.php || -f scripts/generate_commit_message.php ]]; then
    php scripts/generate_commit_message.php \
        --subject="${CODEX_AUTO_PUSH_MESSAGE:-}" \
        > "${message_file}"
fi

if ! CODEX_CHANGELOG_LANGUAGE="${CODEX_CHANGELOG_LANGUAGE:-en}" \
    CODEX_CHANGELOG_TITLE="${CODEX_CHANGELOG_TITLE:-${commit_subject}}" \
    CODEX_CHANGELOG_COMMIT_RUNNING=1 \
    git commit -F "${message_file}"; then
    log "skip: git commit failed"
    exit 0
fi

if [[ "${CODEX_AUTO_PUSH_SKIP_PUSH:-}" == "1" ]]; then
    log "skip: push disabled by CODEX_AUTO_PUSH_SKIP_PUSH"
    exit 0
fi

if git rev-parse --abbrev-ref --symbolic-full-name '@{u}' >/dev/null 2>&1; then
    git push || log "warning: git push failed"
elif git remote get-url origin >/dev/null 2>&1; then
    git push -u origin "${branch}" || log "warning: git push -u origin ${branch} failed"
else
    log "warning: commit created but push skipped because no git remote is configured"
fi
