#!/usr/bin/env python3
"""
Moodle lang-string management tool for mod_bookit.

Run this script from the plugin root directory, i.e. the directory that contains
lang/, classes/, db/, templates/, amd/, and the other Moodle plugin folders.

Main commands
-------------
    python3 langstring_cleaner.py sort [FILE]
        Sort lang strings alphabetically. Default FILE: lang/en/bookit.php

    python3 langstring_cleaner.py unused [FILE]
        Print lang strings that are not referenced in the plugin tree.

    python3 langstring_cleaner.py prune [FILE]
        Remove unreferenced lang strings and print every removed key.

    python3 langstring_cleaner.py sync [--install-argos] [--install-model]
        Add EN strings missing from DE and translate them with Argos Translate.
        This uses a local/open-source translation library, not an LLM API.

    python3 langstring_cleaner.py strip-inline-comments [FILE]
        Remove inline comments from code/string lines, but keep standalone
        comment lines.

Optional setup for translation
------------------------------
    python3 langstring_cleaner.py install-translator --install-argos

This installs the Python package with pip and then installs the EN->DE Argos
model. No requirements.txt is needed.

Notes
-----
- Every write operation creates FILE.bak first.
- The sort command reorders complete $string[...] assignment blocks. It does not
  rewrite the assignment text, quotes, values, or inline comments.
- The sort command verifies this before saving: the set of keys and the exact
  statement body for each key must be unchanged.
"""

from __future__ import annotations

import argparse
import hashlib
import os
import re
import shutil
import subprocess
import sys
from collections import Counter
from dataclasses import dataclass
from pathlib import Path
from typing import Optional


DEFAULT_EN = "lang/en/bookit.php"
DEFAULT_DE = "lang/de/bookit.php"
COMPONENT = "mod_bookit"

SCAN_EXTENSIONS = {".php", ".js", ".mustache", ".html"}

SKIP_DIRS = {
    ".git",
    ".idea",
    ".vscode",
    "node_modules",
    "vendor",
    "__pycache__",
}

ALWAYS_USED = {
    # Moodle/plugin metadata strings.
    "bookit:addinstance",
    "bookit:view",
    "modulename",
    "modulenameplural",
    "pluginadministration",
    "pluginname",

    # Historically present in many activity plugins.
    "bookitfieldset",
}

LANGSTRING_START_RE = re.compile(
    r"""(?m)^[ \t]*\$string\s*\[\s*(['"])(?P<key>[^'"]+)\1\s*\]\s*="""
)


@dataclass(frozen=True)
class LangEntry:
    """One parsed Moodle lang-string assignment.

    leading:
        Text between the previous entry and this entry. This is moved together
        with the entry when sorting, so comments/blank lines directly attached
        to a string stay attached to that string.

    statement:
        The exact $string[...] = ...; statement, including the same-line text
        after the semicolon. This is never edited by the sort command.
    """

    key: str
    leading: str
    statement: str


@dataclass(frozen=True)
class LangFile:
    prefix: str
    entries: list[LangEntry]
    suffix: str


def read_text(path: str | Path) -> str:
    return Path(path).read_text(encoding="utf-8", errors="replace")


def write_text_with_backup(path: str | Path, text: str) -> None:
    path = Path(path)
    shutil.copy2(path, str(path) + ".bak")
    path.write_text(text, encoding="utf-8")


def detect_newline(text: str) -> str:
    return "\r\n" if "\r\n" in text else "\n"


def find_statement_end(text: str, start: int) -> int:
    """Return the end offset of a PHP assignment statement.

    The returned range includes the terminating semicolon and any same-line
    trailing text after it. That means an assignment like

        $string['x'] = 'Value'; // TODO

    is treated as one exact block by the sorter.
    """
    quote: Optional[str] = None
    escaped = False
    i = start

    while i < len(text):
        ch = text[i]

        if quote is not None:
            if escaped:
                escaped = False
            elif ch == "\\":
                escaped = True
            elif ch == quote:
                quote = None
            i += 1
            continue

        if ch in ("'", '"'):
            quote = ch
            i += 1
            continue

        if ch == ";":
            line_end = text.find("\n", i)
            return len(text) if line_end == -1 else line_end + 1

        i += 1

    raise ValueError("Unterminated $string assignment starting at offset " + str(start))


def parse_langfile_text(text: str) -> LangFile:
    matches = list(LANGSTRING_START_RE.finditer(text))
    if not matches:
        return LangFile(prefix=text, entries=[], suffix="")

    prefix = text[:matches[0].start()]
    entries: list[LangEntry] = []
    cursor = matches[0].start()

    for match in matches:
        start = match.start()
        leading = text[cursor:start]
        end = find_statement_end(text, start)
        entries.append(
            LangEntry(
                key=match.group("key"),
                leading=leading,
                statement=text[start:end],
            )
        )
        cursor = end

    return LangFile(prefix=prefix, entries=entries, suffix=text[cursor:])


def parse_langfile(path: str | Path) -> LangFile:
    return parse_langfile_text(read_text(path))


def compose_langfile(langfile: LangFile, entries: list[LangEntry]) -> str:
    return langfile.prefix + "".join(e.leading + e.statement for e in entries) + langfile.suffix


def entry_counter(entries: list[LangEntry]) -> Counter[tuple[str, str]]:
    return Counter((entry.key, entry.statement) for entry in entries)


def statement_hash(statement: str) -> str:
    return hashlib.sha256(statement.encode("utf-8")).hexdigest()[:12]


def duplicate_keys(entries: list[LangEntry]) -> list[str]:
    counts = Counter(entry.key for entry in entries)
    return sorted([key for key, count in counts.items() if count > 1], key=str.lower)


def verify_sort_is_order_only(original: LangFile, output_entries: list[LangEntry], output_text: str) -> None:
    """Abort if sorting changed anything except the order of entries.

    The sorter composes the output from existing pieces, so the strongest useful
    check is that every original assignment statement still exists byte-for-byte
    and no new assignment statement was introduced. Leading blank/comment text
    may move together with its entry, which is why this function checks that the
    original file prefix remains at the start instead of comparing reparsed
    prefixes literally.
    """
    if entry_counter(original.entries) != entry_counter(output_entries):
        raise RuntimeError("Sort verification failed: output entry list changed a statement.")

    if not output_text.startswith(original.prefix):
        raise RuntimeError("Sort verification failed: original file prefix/header is not preserved.")

    if original.suffix and not output_text.endswith(original.suffix):
        raise RuntimeError("Sort verification failed: original file suffix is not preserved.")

    reparsed = parse_langfile_text(output_text)
    if entry_counter(original.entries) != entry_counter(reparsed.entries):
        original_counter = entry_counter(original.entries)
        new_counter = entry_counter(reparsed.entries)
        removed = original_counter - new_counter
        added = new_counter - original_counter

        details = []
        if removed:
            details.append("removed/changed blocks: " + str(list(removed.elements())[:5]))
        if added:
            details.append("added/changed blocks: " + str(list(added.elements())[:5]))

        raise RuntimeError("Sort verification failed: lang-string statements changed. " + "; ".join(details))


# ---------------------------------------------------------------------------
# Sorting
# ---------------------------------------------------------------------------

def cmd_sort(filepath: str, check_only: bool = False) -> int:
    langfile = parse_langfile(filepath)
    if not langfile.entries:
        print(f"No lang-string assignments found in {filepath}.")
        return 1

    duplicates = duplicate_keys(langfile.entries)
    if duplicates:
        print("Duplicate keys found. Resolve them before sorting:")
        for key in duplicates:
            print(f"  {key}")
        return 1

    original_order = [entry.key for entry in langfile.entries]
    sorted_entries = sorted(langfile.entries, key=lambda entry: entry.key.lower())
    sorted_order = [entry.key for entry in sorted_entries]
    is_sorted = original_order == sorted_order

    print(f"Parsed {len(langfile.entries)} string(s) from {filepath}")

    if is_sorted:
        print("Already sorted.")
        return 0

    for index, (got, expected) in enumerate(zip(original_order, sorted_order), start=1):
        if got != expected:
            print(f"First mismatch at #{index}: found '{got}', expected '{expected}'.")
            break

    sorted_text = compose_langfile(langfile, sorted_entries)
    verify_sort_is_order_only(langfile, sorted_entries, sorted_text)

    print("Integrity check passed:")
    print("  - same keys")
    print("  - same exact $string assignment blocks")
    print("  - same file prefix/header")
    print("  - same file suffix")
    print("  - only the order of complete entries changes")

    if check_only:
        print("Check-only mode: file is not sorted.")
        return 1

    write_text_with_backup(filepath, sorted_text)
    print(f"Sorted and saved. Backup: {filepath}.bak")
    return 0


# ---------------------------------------------------------------------------
# Unused-string scanner
# ---------------------------------------------------------------------------

def collect_referenced_keys(plugin_root: str) -> tuple[set[str], set[str]]:
    """Return exact string-key references and dynamic prefixes.

    A lang key is considered used if it is referenced exactly, or if it starts
    with a detected dynamic prefix such as "status_" from code like:

        get_string('status_' . $status, 'mod_bookit')
    """
    exact: set[str] = set()
    prefixes: set[str] = set()

    get_string_exact = re.compile(
        r"""get_string\s*\(\s*['"]([^'"]+)['"]\s*,\s*['"](?:mod_)?bookit['"]""",
        re.IGNORECASE,
    )

    get_string_dynamic_prefix = re.compile(
        r"""get_string\s*\(\s*['"]([^'"]*_)['"]\s*\.""",
        re.IGNORECASE,
    )

    js_exact_patterns = [
        re.compile(
            r"""get_string\s*\(\s*['"]([^'"]+)['"]\s*,\s*['"](?:mod_)?bookit['"]""",
            re.IGNORECASE,
        ),
        re.compile(
            r"""getString\s*\(\s*['"]([^'"]+)['"]\s*,\s*['"](?:mod_)?bookit['"]""",
            re.IGNORECASE,
        ),
        re.compile(
            r"""str\.get_string\s*\(\s*['"]([^'"]+)['"]\s*,\s*['"](?:mod_)?bookit['"]""",
            re.IGNORECASE,
        ),
    ]

    mustache_str = re.compile(
        r"""\{\{#\s*str\s*\}\}\s*([^,}]+?)\s*,\s*(?:mod_)?bookit\s*\{\{/str\s*\}\}""",
        re.IGNORECASE,
    )

    capability = re.compile(r"""['"]mod/bookit:([^'"]+)['"]""")

    lang_dir = os.path.abspath(os.path.join(plugin_root, "lang"))

    for dirpath, dirnames, filenames in os.walk(plugin_root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]

        abs_dir = os.path.abspath(dirpath)
        if os.path.isdir(lang_dir) and os.path.commonpath([abs_dir, lang_dir]) == lang_dir:
            continue

        for filename in filenames:
            if filename.endswith((".bak", ".orig", ".rej")):
                continue

            ext = os.path.splitext(filename)[1].lower()
            if ext not in SCAN_EXTENSIONS:
                continue

            path = os.path.join(dirpath, filename)
            try:
                content = Path(path).read_text(encoding="utf-8", errors="replace")
            except OSError:
                continue

            if ext == ".php":
                exact.update(match.group(1) for match in get_string_exact.finditer(content))
                prefixes.update(match.group(1) for match in get_string_dynamic_prefix.finditer(content))
                exact.update("bookit:" + match.group(1) for match in capability.finditer(content))

            elif ext == ".mustache":
                exact.update(match.group(1).strip() for match in mustache_str.finditer(content))

            elif ext == ".js":
                for pattern in js_exact_patterns:
                    exact.update(match.group(1) for match in pattern.finditer(content))

    return exact, prefixes


def find_unused(filepath: str, plugin_root: str) -> list[str]:
    langfile = parse_langfile(filepath)
    exact, prefixes = collect_referenced_keys(plugin_root)

    unused: list[str] = []
    for entry in sorted(langfile.entries, key=lambda item: item.key.lower()):
        key = entry.key
        if key in ALWAYS_USED:
            continue
        if key in exact:
            continue
        if any(key.startswith(prefix) for prefix in prefixes):
            continue
        unused.append(key)

    return unused


def cmd_unused(filepath: str) -> int:
    unused = find_unused(filepath, os.getcwd())

    if not unused:
        print("No unused strings detected.")
        return 0

    print(f"Found {len(unused)} potentially unused string(s):")
    for key in unused:
        print(f"  {key}")

    print()
    print("Check dynamic usages manually before deleting, especially strings built from variables.")
    return 1


def cmd_prune(filepath: str) -> int:
    langfile = parse_langfile(filepath)
    unused = find_unused(filepath, os.getcwd())

    if not unused:
        print("No unused strings to remove.")
        return 0

    unused_set = set(unused)
    kept_entries = [entry for entry in langfile.entries if entry.key not in unused_set]

    print(f"Removing {len(unused)} unused string(s):")
    for key in unused:
        print(f"  - {key}")

    pruned = LangFile(prefix=langfile.prefix, entries=kept_entries, suffix=langfile.suffix)
    # Keep the file sorted after pruning. This still preserves each remaining
    # assignment block byte-for-byte.
    sorted_entries = sorted(pruned.entries, key=lambda entry: entry.key.lower())
    new_text = compose_langfile(pruned, sorted_entries)
    verify_sort_is_order_only(pruned, sorted_entries, new_text)

    write_text_with_backup(filepath, new_text)
    print(f"Pruned and saved. Backup: {filepath}.bak")
    return 0


# ---------------------------------------------------------------------------
# Inline-comment removal
# ---------------------------------------------------------------------------

def strip_inline_comment_from_line(line: str) -> str:
    """Remove inline comments from one line while preserving standalone comments.

    Examples
    --------
    "$string['x'] = 'Value'; // TODO\\n" -> "$string['x'] = 'Value';\\n"
    "    // TODO\\n"                         -> unchanged
    "$string['x'] = 'https://x';\\n"         -> unchanged
    """
    newline = ""
    body = line
    if line.endswith("\r\n"):
        body, newline = line[:-2], "\r\n"
    elif line.endswith("\n"):
        body, newline = line[:-1], "\n"

    quote: Optional[str] = None
    escaped = False
    i = 0

    while i < len(body):
        ch = body[i]

        if quote is not None:
            if escaped:
                escaped = False
            elif ch == "\\":
                escaped = True
            elif ch == quote:
                quote = None
            i += 1
            continue

        if ch in ("'", '"'):
            quote = ch
            i += 1
            continue

        comment_start = False
        comment_end = len(body)

        if body.startswith("//", i):
            comment_start = True
        elif body[i] == "#":
            comment_start = True
        elif body.startswith("/*", i):
            close = body.find("*/", i + 2)
            if close != -1:
                comment_start = True
                comment_end = close + 2

        if comment_start:
            before = body[:i]
            if before.strip():
                if comment_end == len(body):
                    return before.rstrip() + newline
                return (before.rstrip() + body[comment_end:]) + newline

            # Standalone comment: keep it.
            return line

        i += 1

    return line


def cmd_strip_inline_comments(filepath: str) -> int:
    original = read_text(filepath)
    lines = original.splitlines(keepends=True)
    changed_lines: list[int] = []
    new_lines: list[str] = []

    for line_number, line in enumerate(lines, start=1):
        stripped = strip_inline_comment_from_line(line)
        if stripped != line:
            changed_lines.append(line_number)
        new_lines.append(stripped)

    if not changed_lines:
        print("No inline comments found.")
        return 0

    new_text = "".join(new_lines)
    write_text_with_backup(filepath, new_text)

    print(f"Removed inline comments from {len(changed_lines)} line(s).")
    print("Standalone comment lines were left unchanged.")
    print(f"Backup: {filepath}.bak")
    print("Changed lines:")
    for line_number in changed_lines:
        print(f"  {line_number}")
    return 0


# ---------------------------------------------------------------------------
# Translation with Argos Translate
# ---------------------------------------------------------------------------

PLACEHOLDER_RE = re.compile(
    r"""(
        \{\$a(?:->[A-Za-z0-9_]+)?\}      |  # Moodle {$a} / {$a->name}
        \{[A-Z0-9_]+\}                   |  # generic all-caps placeholders
        <[^>]+>                          |  # HTML tags
        &[A-Za-z][A-Za-z0-9]+;           |  # HTML entities
        %[sdif]                          |  # printf-style placeholders
        \d+(?:[.,]\d+)?                  # numbers
    )""",
    re.VERBOSE,
)


def protect_placeholders(text: str) -> tuple[str, list[str]]:
    protected: list[str] = []

    def repl(match: re.Match[str]) -> str:
        protected.append(match.group(0))
        return f"BOOKITPLACEHOLDER{len(protected) - 1}X"

    return PLACEHOLDER_RE.sub(repl, text), protected


def restore_placeholders(text: str, protected: list[str]) -> str:
    for index, value in enumerate(protected):
        text = text.replace(f"BOOKITPLACEHOLDER{index}X", value)
    return text


def php_unescape_single_quoted(text: str) -> str:
    return text.replace("\\'", "'").replace("\\\\", "\\")


def php_escape_single_quoted(text: str) -> str:
    return text.replace("\\", "\\\\").replace("'", "\\'")


def extract_simple_php_string(statement: str) -> Optional[str]:
    """Extract the value from a simple one-literal PHP string assignment.

    Returns None for concatenations or other complex assignments.
    """
    eq = statement.find("=")
    if eq == -1:
        return None

    i = eq + 1
    while i < len(statement) and statement[i].isspace():
        i += 1

    if i >= len(statement) or statement[i] not in ("'", '"'):
        return None

    quote = statement[i]
    i += 1
    value_chars: list[str] = []
    escaped = False

    while i < len(statement):
        ch = statement[i]
        if escaped:
            value_chars.append("\\" + ch)
            escaped = False
        elif ch == "\\":
            escaped = True
        elif ch == quote:
            break
        else:
            value_chars.append(ch)
        i += 1
    else:
        return None

    i += 1
    while i < len(statement) and statement[i].isspace():
        i += 1

    if i >= len(statement) or statement[i] != ";":
        return None

    raw_value = "".join(value_chars)
    if quote == "'":
        return php_unescape_single_quoted(raw_value)

    # Good enough for Moodle UI strings. Avoid interpreting arbitrary PHP
    # double-quoted escape sequences too aggressively.
    return raw_value.replace('\\"', '"').replace("\\\\", "\\")


def ensure_pip_available() -> None:
    """Make sure pip is available for the current Python interpreter."""
    try:
        subprocess.check_call(
            [sys.executable, "-m", "pip", "--version"],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
        return
    except subprocess.CalledProcessError:
        pass

    print("pip is not available for this Python interpreter; enabling it with ensurepip...")
    subprocess.check_call([sys.executable, "-m", "ensurepip", "--upgrade"])


def ensure_argos_package(install_argos: bool) -> None:
    try:
        import argostranslate.package  # noqa: F401
        import argostranslate.translate  # noqa: F401
        return
    except ImportError as exc:
        if not install_argos:
            raise RuntimeError(
                "Argos Translate is not installed. Either run:\n"
                "  python -m pip install argostranslate\n"
                "or use this script inline:\n"
                "  python langstring_cleaner.py install-translator --install-argos"
            ) from exc

    ensure_pip_available()
    print("Installing Python package: argostranslate")
    subprocess.check_call([sys.executable, "-m", "pip", "install", "argostranslate"])


def get_argos_translation(from_code: str, to_code: str, install_model: bool, install_argos: bool):
    ensure_argos_package(install_argos)

    import argostranslate.package
    import argostranslate.translate

    def find_translation():
        installed_languages = argostranslate.translate.get_installed_languages()
        from_lang = next((lang for lang in installed_languages if lang.code == from_code), None)
        to_lang = next((lang for lang in installed_languages if lang.code == to_code), None)
        if from_lang is None or to_lang is None:
            return None
        return from_lang.get_translation(to_lang)

    translation = find_translation()
    if translation is not None:
        return translation

    if not install_model:
        raise RuntimeError(
            f"No Argos model installed for {from_code}->{to_code}. "
            "Run: python3 langstring_cleaner.py install-translator "
            "or use: python3 langstring_cleaner.py sync --install-model"
        )

    argostranslate.package.update_package_index()
    available = argostranslate.package.get_available_packages()
    package = next(
        (
            pkg for pkg in available
            if pkg.from_code == from_code and pkg.to_code == to_code
        ),
        None,
    )
    if package is None:
        raise RuntimeError(f"No Argos package found for {from_code}->{to_code}.")

    package_path = package.download()
    argostranslate.package.install_from_path(package_path)

    translation = find_translation()
    if translation is None:
        raise RuntimeError(f"Installed Argos package, but {from_code}->{to_code} is still unavailable.")

    return translation


def translate_argos(text: str, from_code: str, to_code: str, install_model: bool, install_argos: bool) -> str:
    translation = get_argos_translation(from_code, to_code, install_model, install_argos)
    protected_text, protected = protect_placeholders(text)
    translated = translation.translate(protected_text)
    return restore_placeholders(translated, protected)


def cmd_install_translator(from_code: str, to_code: str, install_argos: bool) -> int:
    get_argos_translation(from_code, to_code, install_model=True, install_argos=install_argos)
    print(f"Argos translation model installed: {from_code}->{to_code}")
    return 0


def cmd_sync(en_path: str, de_path: str, install_model: bool, placeholder: bool, install_argos: bool) -> int:
    en_file = parse_langfile(en_path)

    if os.path.isfile(de_path):
        de_file = parse_langfile(de_path)
    else:
        de_file = LangFile(prefix=en_file.prefix, entries=[], suffix=en_file.suffix)

    de_by_key = {entry.key: entry for entry in de_file.entries}
    missing = sorted(
        [entry for entry in en_file.entries if entry.key not in de_by_key],
        key=lambda entry: entry.key.lower(),
    )
    extra_keys = sorted(set(de_by_key) - {entry.key for entry in en_file.entries}, key=str.lower)

    if extra_keys:
        print(f"{len(extra_keys)} key(s) exist in DE but not EN:")
        for key in extra_keys:
            print(f"  {key}")

    if not missing:
        print("DE already contains all EN keys.")
        return 0

    print(f"{len(missing)} EN string(s) missing from DE.")

    newline = detect_newline(read_text(en_path))
    new_entries: list[LangEntry] = []

    for entry in missing:
        english_value = extract_simple_php_string(entry.statement)

        if english_value is None:
            value = entry.statement.strip()
            print(f"  {entry.key}: complex assignment copied as English placeholder.")
            new_statement = entry.statement.rstrip("\r\n") + " // TODO: translate complex string" + newline
        elif placeholder:
            value = english_value
            new_statement = (
                f"$string['{entry.key}'] = '{php_escape_single_quoted(value)}'; "
                f"// TODO: translate"
                f"{newline}"
            )
            print(f"  {entry.key}: copied as placeholder.")
        else:
            try:
                value = translate_argos(english_value, "en", "de", install_model, install_argos)
            except RuntimeError as exc:
                print(str(exc))
                print("No changes written.")
                return 1

            new_statement = (
                f"$string['{entry.key}'] = '{php_escape_single_quoted(value)}'; "
                f"// TODO: verify machine translation"
                f"{newline}"
            )
            print(f"  {entry.key}: translated.")

        new_entries.append(LangEntry(key=entry.key, leading=newline, statement=new_statement))

    combined_entries = de_file.entries + new_entries
    combined = LangFile(prefix=de_file.prefix, entries=combined_entries, suffix=de_file.suffix)
    sorted_entries = sorted(combined.entries, key=lambda item: item.key.lower())
    new_text = compose_langfile(combined, sorted_entries)

    # This verifies that existing DE entries were moved byte-for-byte only.
    existing_before = LangFile(prefix=de_file.prefix, entries=de_file.entries, suffix=de_file.suffix)
    existing_after = parse_langfile_text(new_text)
    before_counter = entry_counter(existing_before.entries)
    after_counter = entry_counter([entry for entry in existing_after.entries if entry.key in de_by_key])
    if before_counter != after_counter:
        raise RuntimeError("Sync verification failed: existing DE entries changed.")

    write_text_with_backup(de_path, new_text)
    print(f"Synced and saved. Backup: {de_path}.bak")
    print("New translations are marked with inline comments.")
    print("Remove those markers later with: python3 langstring_cleaner.py strip-inline-comments " + de_path)
    return 0


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(
        description="Moodle lang-string management tool for mod_bookit.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    sub = parser.add_subparsers(dest="command", required=True)

    p_sort = sub.add_parser("sort", help="Sort strings alphabetically.")
    p_sort.add_argument("file", nargs="?", default=DEFAULT_EN)
    p_sort.add_argument("--check", action="store_true", help="Only check whether sorting would be needed.")

    p_unused = sub.add_parser("unused", help="List unused strings.")
    p_unused.add_argument("file", nargs="?", default=DEFAULT_EN)

    p_prune = sub.add_parser("prune", help="Remove unused strings.")
    p_prune.add_argument("file", nargs="?", default=DEFAULT_EN)

    p_sync = sub.add_parser("sync", help="Sync EN to DE and translate missing strings with Argos Translate.")
    p_sync.add_argument("--en", default=DEFAULT_EN)
    p_sync.add_argument("--de", default=DEFAULT_DE)
    p_sync.add_argument(
        "--install-argos",
        action="store_true",
        help="Install the argostranslate Python package with pip if it is missing.",
    )
    p_sync.add_argument(
        "--install-model",
        action="store_true",
        help="Download/install the Argos EN->DE model if it is missing.",
    )
    p_sync.add_argument(
        "--placeholder",
        action="store_true",
        help="Copy English strings instead of translating. Useful when Argos is not installed yet.",
    )

    p_strip = sub.add_parser("strip-inline-comments", help="Remove inline comments, keep standalone comments.")
    p_strip.add_argument("file", nargs="?", default=DEFAULT_EN)

    p_install = sub.add_parser("install-translator", help="Install Argos Translate and the EN->DE translation model.")
    p_install.add_argument("--from-code", default="en")
    p_install.add_argument("--to-code", default="de")
    p_install.add_argument(
        "--install-argos",
        action="store_true",
        help="Install the argostranslate Python package with pip if it is missing.",
    )

    args = parser.parse_args()

    try:
        if args.command == "sort":
            return cmd_sort(args.file, check_only=args.check)
        if args.command == "unused":
            return cmd_unused(args.file)
        if args.command == "prune":
            return cmd_prune(args.file)
        if args.command == "sync":
            return cmd_sync(args.en, args.de, install_model=args.install_model, placeholder=args.placeholder, install_argos=args.install_argos)
        if args.command == "strip-inline-comments":
            return cmd_strip_inline_comments(args.file)
        if args.command == "install-translator":
            return cmd_install_translator(args.from_code, args.to_code, install_argos=args.install_argos)
    except (OSError, ValueError, RuntimeError) as exc:
        print(f"Error: {exc}", file=sys.stderr)
        return 1

    return 2


if __name__ == "__main__":
    sys.exit(main())
