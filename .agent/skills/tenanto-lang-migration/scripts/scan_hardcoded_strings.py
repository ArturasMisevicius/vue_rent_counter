#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Iterable

TARGET_DIRS = ("app", "resources", "routes")
SKIP_DIRS = {
    ".git",
    ".agent",
    ".codex",
    ".cursor",
    ".idea",
    ".vscode",
    "vendor",
    "node_modules",
    "storage",
    "bootstrap",
    "lang",
}
SOURCE_SUFFIXES = {".php", ".js", ".ts", ".vue"}

METHOD_PATTERNS = {
    "filament_label": re.compile(r"->label\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_placeholder": re.compile(r"->placeholder\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_helper_text": re.compile(r"->helperText\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_hint": re.compile(r"->hint\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_title": re.compile(r"->title\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_description": re.compile(r"->description\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_tooltip": re.compile(r"->tooltip\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_modal_heading": re.compile(r"->modalHeading\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"),
    "filament_modal_description": re.compile(
        r"->modalDescription\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"
    ),
    "filament_modal_submit": re.compile(
        r"->modalSubmitActionLabel\(\s*(['\"])(?P<text>[^'\"]+)\1\s*\)"
    ),
    "array_ui_key": re.compile(
        r"['\"](?:label|labels|placeholder|placeholders|title|description|"
        r"message|messages|helper_text|hint|value|values)['\"]\s*=>\s*(['\"])(?P<text>[^'\"]+)\1"
    ),
}
BLADE_TEXT_PATTERN = re.compile(r">\s*(?P<text>[^<{@][^<{]{1,200})\s*<")
LETTER_PATTERN = re.compile(r"[A-Za-z]")
TRANSLATION_WRAPPER_PATTERN = re.compile(r"__\(|@lang\(|trans\(")
TRANSLATION_KEY_PATTERN = re.compile(r"^[a-z0-9_]+(?:\.[a-z0-9_]+)+$")
URL_PATTERN = re.compile(r"^(?:https?://|mailto:|tel:)")
VALIDATION_RULE_PATTERN = re.compile(r"^[a-z_]+(?:\|[a-z_]+(?::[^|]+)?)*$")
TECHNICAL_LITERAL_PATTERN = re.compile(r"^[A-Za-z0-9_.:/\\-]+$")


@dataclass(frozen=True)
class Finding:
    file: str
    line: int
    category: str
    text: str


def is_source_file(path: Path) -> bool:
    if path.name.endswith(".blade.php"):
        return True
    return path.suffix in SOURCE_SUFFIXES


def should_skip(path: Path) -> bool:
    parts = set(path.parts)
    return bool(parts & SKIP_DIRS)


def iter_source_files(root: Path) -> Iterable[Path]:
    for directory in TARGET_DIRS:
        base = root / directory
        if not base.exists():
            continue
        for file_path in base.rglob("*"):
            if not file_path.is_file():
                continue
            if should_skip(file_path):
                continue
            if is_source_file(file_path):
                yield file_path


def normalize_text(value: str) -> str:
    value = value.strip()
    value = value.replace("\\n", " ").replace("\\t", " ")
    value = re.sub(r"\s+", " ", value)
    return value


def looks_translatable(text: str) -> bool:
    text = normalize_text(text)
    if len(text) < 2:
        return False
    if not LETTER_PATTERN.search(text):
        return False
    if TRANSLATION_WRAPPER_PATTERN.search(text):
        return False
    if TRANSLATION_KEY_PATTERN.fullmatch(text):
        return False
    if URL_PATTERN.search(text):
        return False
    if VALIDATION_RULE_PATTERN.fullmatch(text):
        return False
    if text.startswith("$") or text.startswith("@"):
        return False
    if text.count(" ") == 0 and TECHNICAL_LITERAL_PATTERN.fullmatch(text):
        if text.islower() and "_" in text:
            return False
    return True


def extract_line_number(text: str, position: int) -> int:
    return text.count("\n", 0, position) + 1


def scan_file(path: Path, include_blade_text: bool) -> list[Finding]:
    findings: list[Finding] = []
    try:
        content = path.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        content = path.read_text(encoding="utf-8", errors="ignore")

    for category, pattern in METHOD_PATTERNS.items():
        for match in pattern.finditer(content):
            value = normalize_text(match.group("text"))
            if not looks_translatable(value):
                continue
            line = extract_line_number(content, match.start())
            findings.append(
                Finding(
                    file=path.as_posix(),
                    line=line,
                    category=category,
                    text=value,
                )
            )

    if include_blade_text and path.name.endswith(".blade.php"):
        for line_number, line in enumerate(content.splitlines(), start=1):
            if "{{" in line or "{!!" in line:
                continue
            if "@lang(" in line or "__(" in line or "trans(" in line:
                continue
            if "<!--" in line or "{{--" in line:
                continue
            for match in BLADE_TEXT_PATTERN.finditer(line):
                value = normalize_text(match.group("text"))
                if not looks_translatable(value):
                    continue
                findings.append(
                    Finding(
                        file=path.as_posix(),
                        line=line_number,
                        category="blade_text",
                        text=value,
                    )
                )

    unique: dict[tuple[str, int, str, str], Finding] = {}
    for finding in findings:
        unique[(finding.file, finding.line, finding.category, finding.text)] = finding
    return list(unique.values())


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Scan Laravel/Filament code for likely hardcoded user-facing strings."
    )
    parser.add_argument("--root", default=".", help="Project root path")
    parser.add_argument("--json", dest="json_path", help="Write findings to JSON file")
    parser.add_argument(
        "--fail-on-findings",
        action="store_true",
        help="Return exit code 1 when findings exist",
    )
    parser.add_argument(
        "--max-print",
        type=int,
        default=300,
        help="Maximum findings to print in terminal output",
    )
    parser.add_argument(
        "--no-blade-text",
        action="store_true",
        help="Skip plain-text scanning between Blade HTML tags",
    )
    args = parser.parse_args()

    root = Path(args.root).resolve()
    files = list(iter_source_files(root))

    all_findings: list[Finding] = []
    for source_file in files:
        file_findings = scan_file(source_file, include_blade_text=not args.no_blade_text)
        all_findings.extend(file_findings)

    all_findings.sort(key=lambda item: (item.file, item.line, item.category, item.text))
    by_category: dict[str, int] = {}
    for finding in all_findings:
        by_category[finding.category] = by_category.get(finding.category, 0) + 1

    print(f"Scanned files: {len(files)}")
    print(f"Findings: {len(all_findings)}")
    if by_category:
        print("By category:")
        for category, count in sorted(by_category.items()):
            print(f"  {category}: {count}")

    print_limit = max(0, args.max_print)
    if all_findings:
        print("\nSample findings:")
        for finding in all_findings[:print_limit]:
            print(f"  {finding.file}:{finding.line} [{finding.category}] {finding.text}")
        if len(all_findings) > print_limit:
            print(f"  ... ({len(all_findings) - print_limit} more)")

    if args.json_path:
        json_file = Path(args.json_path)
        payload = [asdict(finding) for finding in all_findings]
        json_file.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"\nWrote JSON report: {json_file.as_posix()}")

    if args.fail_on_findings and all_findings:
        return 1

    return 0


if __name__ == "__main__":
    sys.exit(main())
