#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
from tempfile import NamedTemporaryFile
from typing import Any

from deep_translator import GoogleTranslator


ROOT = Path(__file__).resolve().parents[1]
LANG_PATH = ROOT / "lang"
TRANSLATION_SCRIPT = ROOT / ".ai" / "skills" / "translate" / "scripts" / "translate.py"

LOCALE_TO_LANGUAGE = {
    "en": "en",
    "es": "es",
    "lt": "lt",
    "ru": "ru",
}

PLACEHOLDER_PATTERNS = [
    re.compile(r"https?://\S+"),
    re.compile(r"</?[^>]+?>"),
    re.compile(r":[A-Za-z_][A-Za-z0-9_]*"),
    re.compile(r"%\d*\$?[bcdeEfFgGosuxX]"),
]
WORD_PATTERN = re.compile(r"[A-Za-zÀ-ÿĀ-žА-Яа-яЁё]+")
CYRILLIC_PATTERN = re.compile(r"[А-Яа-яЁё]")
LITHUANIAN_DIACRITICS_PATTERN = re.compile(r"[ĄČĘĖĮŠŲŪŽąčęėįšųūž]")
SPANISH_DIACRITICS_PATTERN = re.compile(r"[ÁÉÍÓÚÜÑáéíóúüñ¿¡]")
ENGLISH_HINTS = {
    "actions",
    "activity",
    "admin",
    "analytics",
    "audit",
    "authentication",
    "billing",
    "cache",
    "dashboard",
    "database",
    "delete",
    "download",
    "email",
    "endpoint",
    "error",
    "health",
    "invoice",
    "invoices",
    "language",
    "languages",
    "localization",
    "logout",
    "manager",
    "management",
    "meter",
    "meters",
    "monitoring",
    "notification",
    "notifications",
    "organization",
    "organizations",
    "payment",
    "pending",
    "performance",
    "platform",
    "profile",
    "properties",
    "property",
    "queue",
    "recent",
    "reports",
    "response",
    "save",
    "service",
    "services",
    "settings",
    "status",
    "subscription",
    "subscriptions",
    "system",
    "tariff",
    "tariffs",
    "tenant",
    "tenants",
    "translation",
    "translations",
    "user",
    "users",
    "utilities",
}
PLACEHOLDER_TOKEN_PATTERN = re.compile(r'<ph index="(\d+)"\s*/>')
POST_TRANSLATION_REPLACEMENTS = {
    "en": {
        "Actively": "Active",
        "All systems go": "All systems operational",
        "A total of gates": "Total gates",
        "A total of politicians": "Total policies",
        "Asset management": "Property management",
        "Cause": "Reason",
        "Complete impersonation": "End Impersonation",
        "Condition metrics": "Health metrics",
        "Condition status": "Health status",
        "End point": "Endpoint",
        "Enter maintenance mode": "Enable maintenance mode",
        "Health check completed": "Health check completed",
        "Health check failed": "Health check failed",
        "Hello": "Healthy",
        "History of impersonation": "Impersonation History",
        "Indications": "Readings",
        "Just not healthy": "Unhealthy only",
        "Mass health check completed": "Bulk health check completed",
        "No permission to admin panel": "No permission for admin panel",
        "Not healthy": "Unhealthy",
        "Registered gateway": "Registered gates",
        "Started in": "Started at",
        "Status check completed": "Health check completed",
        "Status check failed": "Health check failed",
        "Status check results": "Health check results",
        "Subscription management": "Manage subscriptions",
        "The reason": "Reason",
        "There is none": "None",
        "Too many tries": "Too many attempts",
        "Works": "Running",
    },
}


def read_php_source(file_path: Path, prefer_head: bool) -> str:
    if not prefer_head:
        return file_path.read_text(encoding="utf-8")

    relative = file_path.relative_to(ROOT).as_posix()
    result = subprocess.run(
        ["git", "show", f"HEAD:{relative}"],
        capture_output=True,
        text=True,
        cwd=ROOT,
    )

    if result.returncode == 0:
        return result.stdout

    return file_path.read_text(encoding="utf-8")


def run_php_json_loader(file_path: Path, prefer_head: bool) -> Any:
    source = read_php_source(file_path, prefer_head)

    with NamedTemporaryFile("w", suffix=".php", encoding="utf-8", delete=True) as handle:
        handle.write(source)
        handle.flush()

        command = [
            "php",
            "-r",
            (
                "$data = include $argv[1];"
                "if (! is_array($data)) { fwrite(STDERR, 'File did not return array'); exit(1); }"
                "echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);"
            ),
            handle.name,
        ]
        result = subprocess.run(command, capture_output=True, text=True, check=True)

    return json.loads(result.stdout)


def php_scalar(value: Any, indent: int) -> str:
    if isinstance(value, str):
        escaped = value.replace("\\", "\\\\").replace("'", "\\'")
        return f"'{escaped}'"

    if value is True:
        return "true"

    if value is False:
        return "false"

    if value is None:
        return "null"

    return str(value)


def php_export(data: Any, indent: int = 0) -> str:
    spacing = " " * indent
    nested_spacing = " " * (indent + 4)

    if isinstance(data, dict):
        if not data:
            return "[]"

        lines = ["["]
        for key, value in data.items():
            key_export = php_scalar(key, indent + 4)
            value_export = php_export(value, indent + 4)
            lines.append(f"{nested_spacing}{key_export} => {value_export},")
        lines.append(f"{spacing}]")
        return "\n".join(lines)

    if isinstance(data, list):
        if not data:
            return "[]"

        lines = ["["]
        for value in data:
            value_export = php_export(value, indent + 4)
            lines.append(f"{nested_spacing}{value_export},")
        lines.append(f"{spacing}]")
        return "\n".join(lines)

    return php_scalar(data, indent)


def write_php_array(file_path: Path, data: Any) -> None:
    content = "<?php\n\nreturn " + php_export(data) + ";\n"
    file_path.write_text(content, encoding="utf-8")


def should_skip_translation(text: str) -> bool:
    stripped = text.strip()

    if stripped == "":
        return True

    if re.fullmatch(r"[-–—•.,:;!?/\\()[\]{}+=_*#@|<>~`\"'0-9\s]+", stripped):
        return True

    if re.fullmatch(r"[A-Z]{2,5}", stripped):
        return True

    if stripped in {"N/A", "n/a", "m²", "€", "$", "£", "¥"}:
        return True

    return False


def protect_placeholders(text: str) -> tuple[str, dict[str, str]]:
    token_map: dict[str, str] = {}
    index = 0

    def replace_pattern(pattern: re.Pattern[str], value: str) -> str:
        nonlocal index

        def replacer(match: re.Match[str]) -> str:
            nonlocal index
            token = f'<ph index="{index}"/>'
            token_map[str(index)] = match.group(0)
            index += 1
            return token

        return pattern.sub(replacer, value)

    protected = text
    for pattern in PLACEHOLDER_PATTERNS:
        protected = replace_pattern(pattern, protected)

    return protected, token_map


def restore_placeholders(text: str, token_map: dict[str, str]) -> str:
    def replacer(match: re.Match[str]) -> str:
        return token_map.get(match.group(1), match.group(0))

    return PLACEHOLDER_TOKEN_PATTERN.sub(replacer, text)


def apply_post_translation_replacements(text: str, target_locale: str) -> str:
    replacements = POST_TRANSLATION_REPLACEMENTS.get(target_locale, {})
    return replacements.get(text, text)


def load_english_words() -> set[str]:
    path = Path("/usr/share/dict/words")
    if not path.exists():
        return ENGLISH_HINTS.copy()

    words = {line.strip().lower() for line in path.read_text(errors="ignore").splitlines() if line.strip()}
    return words | ENGLISH_HINTS


def base_english_variants(word: str) -> set[str]:
    variants = {word}

    if word.endswith("ies") and len(word) > 3:
        variants.add(word[:-3] + "y")
    if word.endswith("s") and len(word) > 3:
        variants.add(word[:-1])
    if word.endswith("es") and len(word) > 4:
        variants.add(word[:-2])
    if word.endswith("ing") and len(word) > 5:
        variants.add(word[:-3])
        variants.add(word[:-3] + "e")
    if word.endswith("ed") and len(word) > 4:
        variants.add(word[:-2])
        variants.add(word[:-1])
    if word.endswith("ly") and len(word) > 4:
        variants.add(word[:-2])

    return variants


def looks_english(text: str, english_words: set[str]) -> bool:
    if CYRILLIC_PATTERN.search(text) or LITHUANIAN_DIACRITICS_PATTERN.search(text):
        return False

    words = [word.lower() for word in WORD_PATTERN.findall(text)]
    if not words:
        return False

    recognized = 0
    for word in words:
        if any(variant in english_words for variant in base_english_variants(word)):
            recognized += 1

    score = recognized / len(words)
    return score >= 0.6 or (len(words) == 1 and recognized == 1)


def build_locale_lexicon(file_payloads: list[Any]) -> set[str]:
    lexicon: set[str] = set()

    def collect(value: Any) -> None:
        if isinstance(value, dict):
            for nested in value.values():
                collect(nested)
        elif isinstance(value, list):
            for nested in value:
                collect(nested)
        elif isinstance(value, str):
            for word in WORD_PATTERN.findall(value):
                lexicon.add(word.lower())

    for payload in file_payloads:
        collect(payload)

    return lexicon


def looks_lithuanian(text: str, lithuanian_words: set[str], english_words: set[str]) -> bool:
    if LITHUANIAN_DIACRITICS_PATTERN.search(text):
        return True

    if CYRILLIC_PATTERN.search(text) or looks_english(text, english_words):
        return False

    words = [word.lower() for word in WORD_PATTERN.findall(text)]
    if not words:
        return False

    matches = sum(1 for word in words if word in lithuanian_words)
    return (matches / len(words)) >= 0.6


def should_translate_for_locale(text: str, target_locale: str, english_words: set[str], lithuanian_words: set[str]) -> bool:
    if should_skip_translation(text):
        return False

    if target_locale == "en":
        return not looks_english(text, english_words)

    if target_locale == "lt":
        if looks_lithuanian(text, lithuanian_words, english_words):
            return False

        return CYRILLIC_PATTERN.search(text) is not None or looks_english(text, english_words)

    if target_locale == "ru":
        return CYRILLIC_PATTERN.search(text) is None

    if target_locale == "es":
        return SPANISH_DIACRITICS_PATTERN.search(text) is None

    return True


def infer_source_locale(text: str, target_locale: str, english_words: set[str], lithuanian_words: set[str]) -> str:
    if CYRILLIC_PATTERN.search(text):
        return "ru"

    if looks_english(text, english_words):
        return "en"

    if looks_lithuanian(text, lithuanian_words, english_words):
        return "lt"

    if SPANISH_DIACRITICS_PATTERN.search(text):
        return "es"

    return "auto"


def translate_one(text: str, target_locale: str, english_words: set[str], lithuanian_words: set[str]) -> tuple[str, str]:
    protected, token_map = protect_placeholders(text)
    source_locale = infer_source_locale(text, target_locale, english_words, lithuanian_words)
    translator = GoogleTranslator(source=source_locale, target=LOCALE_TO_LANGUAGE[target_locale])
    result = translator.translate(protected)
    restored = restore_placeholders(result, token_map)
    return text, apply_post_translation_replacements(restored, target_locale)


def translate_batch(strings: list[str], target_locale: str, english_words: set[str], lithuanian_words: set[str]) -> dict[str, str]:
    translated: dict[str, str] = {}

    with ThreadPoolExecutor(max_workers=8) as executor:
        futures = [
            executor.submit(translate_one, text, target_locale, english_words, lithuanian_words)
            for text in strings
        ]

        for future in as_completed(futures):
            original, result = future.result()
            translated[original] = result
            time.sleep(0.02)

    return translated


def walk_scalars(data: Any) -> list[str]:
    values: list[str] = []

    if isinstance(data, dict):
        for value in data.values():
            values.extend(walk_scalars(value))
    elif isinstance(data, list):
        for value in data:
            values.extend(walk_scalars(value))
    elif isinstance(data, str) and not should_skip_translation(data):
        values.append(data)

    return values


def apply_translations(data: Any, translations: dict[str, str]) -> Any:
    if isinstance(data, dict):
        return {key: apply_translations(value, translations) for key, value in data.items()}

    if isinstance(data, list):
        return [apply_translations(value, translations) for value in data]

    if isinstance(data, str) and data in translations:
        return translations[data]

    return data


def locale_files(locales: list[str], files: list[str] | None) -> list[Path]:
    if files:
        return [ROOT / file for file in files]

    collected: list[Path] = []
    for locale in locales:
        locale_path = LANG_PATH / locale
        if not locale_path.is_dir():
            continue
        collected.extend(sorted(locale_path.glob("*.php")))

    return collected


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Normalize locale files by translating each locale to its own language.")
    parser.add_argument("--locale", action="append", dest="locales", help="Locale(s) to normalize, e.g. --locale en --locale lt")
    parser.add_argument("--file", action="append", dest="files", help="Specific file(s) relative to repo root")
    parser.add_argument("--from-head", action="store_true", help="Load source locale files from HEAD before writing")
    parser.add_argument("--dry-run", action="store_true", help="Print the files that would be normalized without writing them")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    locales = args.locales or sorted([path.name for path in LANG_PATH.iterdir() if path.is_dir() and path.name in LOCALE_TO_LANGUAGE])
    files = locale_files(locales, args.files)

    if not files:
        print("No locale files found.", file=sys.stderr)
        return 1

    english_words = load_english_words()
    lithuanian_payloads = [
        run_php_json_loader(path, args.from_head)
        for path in sorted((LANG_PATH / "lt").glob("*.php"))
        if path.is_file()
    ]
    lithuanian_words = build_locale_lexicon(lithuanian_payloads)

    files_by_locale: dict[str, list[Path]] = {}
    for file_path in files:
        locale = file_path.parent.name
        if locale not in LOCALE_TO_LANGUAGE:
            continue
        files_by_locale.setdefault(locale, []).append(file_path)

    for locale, locale_file_paths in files_by_locale.items():
        print(f"Normalizing locale '{locale}' ({len(locale_file_paths)} files)...")

        file_payloads: dict[Path, Any] = {}
        all_strings: list[str] = []

        for file_path in locale_file_paths:
            payload = run_php_json_loader(file_path, args.from_head)
            file_payloads[file_path] = payload
            all_strings.extend(
                [
                    value
                    for value in walk_scalars(payload)
                    if should_translate_for_locale(value, locale, english_words, lithuanian_words)
                ]
            )

        unique_strings = list(dict.fromkeys(all_strings))
        print(f"  Translating {len(unique_strings)} unique strings")

        translations = translate_batch(unique_strings, locale, english_words, lithuanian_words)

        for file_path, payload in file_payloads.items():
            translated_payload = apply_translations(payload, translations)

            if args.dry_run:
                print(f"  Would write {file_path.relative_to(ROOT)}")
                continue

            write_php_array(file_path, translated_payload)
            print(f"  Wrote {file_path.relative_to(ROOT)}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
