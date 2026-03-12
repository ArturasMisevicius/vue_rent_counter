#!/usr/bin/env python
"""Lightweight mobile UX/touch audit.

This script performs heuristic scans for common mobile anti-patterns.
It is intentionally conservative and should be treated as a hinting tool.
"""

from __future__ import annotations

import argparse
from pathlib import Path

JS_EXTS = {".js", ".jsx", ".ts", ".tsx"}
DART_EXTS = {".dart"}
NATIVE_EXTS = {".swift", ".kt", ".kts"}

IGNORE_DIRS = {
    ".git",
    ".idea",
    ".vscode",
    ".venv",
    "node_modules",
    "vendor",
    "dist",
    "build",
    "public",
    "storage",
    ".agent",
    ".agents",
}

LINE_PATTERNS = {
    "ScrollView": "Avoid ScrollView for long lists; use FlatList or FlashList.",
    "console.log": "Remove console.log in production builds.",
    "useNativeDriver: false": "Use native driver for animations when possible.",
    "AsyncStorage": "Do not store tokens in AsyncStorage; use SecureStore/Keychain.",
    "SharedPreferences": "Do not store tokens in SharedPreferences; use encrypted storage.",
}

FILE_PATTERNS = [
    (
        "FlatList missing keyExtractor",
        lambda text: "FlatList" in text and "keyExtractor" not in text,
        "FlatList should define keyExtractor with stable IDs.",
    ),
    (
        "ListView.builder missing",
        lambda text: "ListView(" in text and "ListView.builder" not in text and "ListView.separated" not in text,
        "Prefer ListView.builder or ListView.separated for long lists.",
    ),
]


def iter_files(root: Path) -> list[Path]:
    files: list[Path] = []
    for ext in JS_EXTS | DART_EXTS | NATIVE_EXTS:
        for path in root.rglob(f"*{ext}"):
            if any(part in IGNORE_DIRS for part in path.parts):
                continue
            files.append(path)
    return files


def scan_file(path: Path) -> list[str]:
    warnings: list[str] = []
    try:
        text = path.read_text(encoding="utf-8", errors="ignore")
    except OSError:
        return warnings

    lines = text.splitlines()
    for line_no, line in enumerate(lines, start=1):
        for needle, message in LINE_PATTERNS.items():
            if needle in line:
                warnings.append(f"{path}:{line_no} - {message}")

    for label, predicate, message in FILE_PATTERNS:
        if predicate(text):
            warnings.append(f"{path} - {message} ({label})")

    return warnings


def main() -> int:
    parser = argparse.ArgumentParser(description="Mobile UX and touch audit")
    parser.add_argument("project_path", help="Path to project root")
    args = parser.parse_args()

    root = Path(args.project_path).resolve()
    if not root.exists():
        print(f"Path does not exist: {root}")
        return 2

    files = iter_files(root)
    if not files:
        print("No mobile-related source files found. Nothing to audit.")
        return 0

    all_warnings: list[str] = []
    for file_path in files:
        all_warnings.extend(scan_file(file_path))

    if not all_warnings:
        print("No issues detected by heuristic scan.")
        return 0

    print("Heuristic findings (review manually):")
    for warning in all_warnings:
        print(f"- {warning}")

    print(f"Total findings: {len(all_warnings)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
