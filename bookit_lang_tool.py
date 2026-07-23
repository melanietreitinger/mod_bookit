#!/usr/bin/env python3
"""Language string tool for mod_bookit: audit, sort, prune, translate.

Run it from the plugin root. Documentation is in lang folder. 

@author Vadym Kuzyak (generated with LLM assistance)
"""
from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
from collections import Counter, defaultdict
from dataclasses import asdict, dataclass
from pathlib import Path

DEFAULT_EN = "lang/en/bookit.php"
DEFAULT_DE = "lang/de/bookit.php"
COMPONENTS = {"mod_bookit", "bookit"}
SOURCE_EXTENSIONS = {".php", ".js", ".mustache"}

# amd/build is the compiled mirror of amd/src. If we scanned it, a string that
# was just removed from the source would still look used until someone runs
# grunt. thirdpartylibs is not ours to audit.
SKIP_DIRS = {
    ".git", ".idea", ".vscode", "__pycache__",
    "node_modules", "vendor", "thirdpartylibs",
}
SKIP_RELATIVE = {Path("amd/build"), Path("lang")}

# Moodle asks for these by convention, no call site exists.
STANDARD_KEYS = {
    "modulename", "modulenameplural", "modulename_help",
    "pluginname", "pluginadministration",
}

LANG_KEY_RE = re.compile(
    r"""(?m)^[ \t]*\$string\s*\[\s*(['"])(?P<key>[^'"]+)\1\s*\]\s*=""")

# {{#str}} key, mod_bookit {{/str}} and the cleanstr variant.
MUSTACHE_RE = re.compile(
    r"\{\{#\s*(str|cleanstr)\s*\}\}\s*([^,}]+?)\s*,\s*(?:mod_)?bookit\s*\{\{/\s*\1\s*\}\}",
    re.I,
)
JS_SINGLE_RE = re.compile(
    r"(?:\bgetString|\bget_string|\bstr\.get_string)\s*\(\s*(['\"])([^'\"]+)\1\s*,"
    r"\s*(['\"])(mod_bookit|bookit)\3",
    re.I,
)
JS_OBJECT_RE = re.compile(
    r"\{\s*key\s*:\s*(['\"])([^'\"]+)\1\s*,\s*component\s*:\s*(['\"])(mod_bookit|bookit)\3\s*\}",
    re.I,
)
JS_PREFETCH_RE = re.compile(
    r"prefetchStrings\s*\(\s*(['\"])(mod_bookit|bookit)\1\s*,\s*\[([^\]]*)\]",
    re.I | re.S,
)
JS_TERNARY_RE = re.compile(
    r"(?:\bgetString|\bget_string)\s*\(\s*[^,?]+\?\s*(['\"])([^'\"]+)\1\s*:\s*(['\"])([^'\"]+)\3"
    r"\s*,\s*(['\"])(mod_bookit|bookit)\5",
    re.I | re.S,
)
# Anything else passed as the first argument, i.e. a variable or a template
# literal. Used to spot dynamic JS we do not understand.
JS_ANY_CALL_RE = re.compile(r"\b(getString|get_strings?)\s*\(\s*([^,)]{1,80})", re.I)
JS_TEMPLATE_RE = re.compile(r"`([^`$]*)\$\{[^}]*\}`")


# --- file IO ---------------------------------------------------------------

def read_text(path: str | Path) -> str:
    """Read without newline translation so CRLF files survive a round trip."""
    with open(path, encoding="utf-8", errors="replace", newline="") as handle:
        return handle.read()


def write_with_backup(path: str | Path, text: str) -> None:
    path = Path(path)
    if path.exists():
        shutil.copy2(path, str(path) + ".bak")
    with open(path, "w", encoding="utf-8", newline="") as handle:
        handle.write(text)


def detect_newline(text: str) -> str:
    return "\r\n" if "\r\n" in text else "\n"


# --- lang file parsing -----------------------------------------------------

@dataclass(frozen=True)
class LangEntry:
    """One $string[...] assignment.

    leading is the text between the previous entry and this one, so a comment
    written above a string travels with it when we reorder. statement is the
    assignment itself and is never rewritten, only moved.
    """
    key: str
    leading: str
    statement: str


@dataclass(frozen=True)
class LangFile:
    prefix: str
    entries: list[LangEntry]
    suffix: str


def statement_end(text: str, start: int) -> int:
    """Offset just past an assignment, including trailing same-line text.

    Quote aware, so semicolons inside values and concatenations over several
    lines do not end the statement early.
    """
    quote = None
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
        elif ch in "'\"":
            quote = ch
        elif ch == ";":
            line_end = text.find("\n", i)
            return len(text) if line_end == -1 else line_end + 1
        i += 1
    raise ValueError(f"unterminated $string assignment at offset {start}")


def parse_lang_text(text: str) -> LangFile:
    matches = list(LANG_KEY_RE.finditer(text))
    if not matches:
        return LangFile(text, [], "")

    entries = []
    cursor = matches[0].start()
    for match in matches:
        end = statement_end(text, match.start())
        entries.append(LangEntry(match.group("key"),
                                 text[cursor:match.start()],
                                 text[match.start():end]))
        cursor = end
    return LangFile(text[:matches[0].start()], entries, text[cursor:])


def parse_lang_file(path: str | Path) -> LangFile:
    return parse_lang_text(read_text(path))


def compose(langfile: LangFile, entries: list[LangEntry]) -> str:
    return langfile.prefix + "".join(e.leading + e.statement for e in entries) + langfile.suffix


def entry_counter(entries: list[LangEntry]) -> Counter:
    return Counter((e.key, e.statement) for e in entries)


def duplicate_keys(entries: list[LangEntry]) -> list[str]:
    counts = Counter(e.key for e in entries)
    return sorted((k for k, n in counts.items() if n > 1), key=str.lower)


def verify_only_reordered(original: LangFile, entries: list[LangEntry], text: str) -> None:
    """Abort unless the output is the same statements in a different order."""
    if entry_counter(original.entries) != entry_counter(entries):
        raise RuntimeError("verification failed: an assignment was altered")
    if not text.startswith(original.prefix):
        raise RuntimeError("verification failed: file header changed")
    if original.suffix and not text.endswith(original.suffix):
        raise RuntimeError("verification failed: file footer changed")

    reparsed = parse_lang_text(text)
    before, after = entry_counter(original.entries), entry_counter(reparsed.entries)
    if before != after:
        lost = list((before - after).elements())[:5]
        gained = list((after - before).elements())[:5]
        raise RuntimeError(f"verification failed on reparse. lost: {lost} gained: {gained}")


# --- PHP call collection ---------------------------------------------------

def source_files(root: Path):
    for dirpath, dirnames, filenames in os.walk(root):
        rel_dir = Path(dirpath).relative_to(root)
        dirnames[:] = [d for d in dirnames
                       if d not in SKIP_DIRS and (rel_dir / d) not in SKIP_RELATIVE]
        for name in filenames:
            path = Path(dirpath) / name
            if path.suffix.lower() in SOURCE_EXTENSIONS and not name.endswith(
                    (".bak", ".orig", ".rej", ".min.js")):
                yield path


# Regex on raw PHP would also match commented-out calls, so we let PHP itself
# tokenize the tree and hand us back the call sites as JSON.
PHP_COLLECTOR = r"""<?php
$root = rtrim($argv[1], '/');
$skip = ['.git/', 'lang/', 'thirdpartylibs/', 'amd/build/', 'vendor/', 'node_modules/'];
$calls = [];

function text($token) {
    return is_array($token) ? $token[1] : $token;
}

function skip_space($tokens, $i) {
    $n = count($tokens);
    while ($i < $n && is_array($tokens[$i])
           && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
        $i++;
    }
    return $i;
}

// Split the argument list at top level commas, keeping nested calls intact.
function arguments($tokens, $open) {
    $args = [];
    $current = '';
    $depth = [];
    for ($i = $open + 1, $n = count($tokens); $i < $n; $i++) {
        $t = text($tokens[$i]);
        if (in_array($t, ['(', '[', '{'], true)) {
            $depth[] = $t;
        } else if (in_array($t, [')', ']', '}'], true)) {
            if ($t === ')' && !$depth) {
                $args[] = trim($current);
                return $args;
            }
            array_pop($depth);
        } else if ($t === ',' && !$depth) {
            $args[] = trim($current);
            $current = '';
            continue;
        }
        $current .= $t;
    }
    return $args;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $rel = substr($file->getPathname(), strlen($root) + 1);
    foreach ($skip as $prefix) {
        if (str_starts_with($rel, $prefix)) {
            continue 2;
        }
    }

    $tokens = token_get_all(file_get_contents($file->getPathname()));
    $function = '';
    for ($i = 0, $n = count($tokens); $i < $n; $i++) {
        // Remember the enclosing function, we need it to tell two identical
        // reflection calls apart without relying on line numbers.
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
            $j = skip_space($tokens, $i + 1);
            if ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $function = $tokens[$j][1];
            }
            continue;
        }
        if (!is_array($tokens[$i]) || !in_array($tokens[$i][0],
                [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            continue;
        }

        $name = strtolower(preg_replace('/^.*\\\\/', '', ltrim($tokens[$i][1], '\\')));
        $call = null;
        if (in_array($name, ['get_string', 'get_strings', 'addhelpbutton',
                             'help_icon', 'moodle_exception', 'print_error'], true)) {
            $call = $name;
        } else if ($name === 'lang_string') {
            $k = $i - 1;
            while ($k >= 0 && is_array($tokens[$k])
                   && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $k--;
            }
            if ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_NEW) {
                $call = 'lang_string';
            }
        }
        if ($call === null) {
            continue;
        }

        $j = skip_space($tokens, $i + 1);
        if ($j < $n && $tokens[$j] === '(') {
            $calls[] = [
                'file' => $rel,
                'line' => $tokens[$i][2],
                'call' => $call,
                'function' => $function,
                'args' => arguments($tokens, $j),
            ];
        }
    }
}

echo json_encode($calls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
"""


def collect_php_calls(root: Path) -> list[dict]:
    with tempfile.NamedTemporaryFile("w", suffix=".php", delete=False, encoding="utf-8") as f:
        f.write(PHP_COLLECTOR)
        script = f.name
    try:
        proc = subprocess.run(["php", script, str(root)],
                              check=True, text=True, capture_output=True)
        return json.loads(proc.stdout)
    except FileNotFoundError:
        sys.exit("php is not on PATH, but the PHP scan needs it.")
    except subprocess.CalledProcessError as exc:
        sys.exit(f"PHP collector failed:\n{exc.stderr}")
    finally:
        Path(script).unlink(missing_ok=True)


# --- value sources for dynamic keys ----------------------------------------

def php_literal(expr: str) -> str | None:
    """Return the value of a plain PHP string literal, else None."""
    expr = expr.strip()
    if len(expr) < 2 or expr[0] not in "'\"" or expr[-1] != expr[0]:
        return None
    quote, body = expr[0], expr[1:-1]
    if quote == '"' and re.search(r"[$\{]", body):
        return None  # interpolated, not a constant key
    if quote == "'":
        return body.replace("\\'", "'").replace("\\\\", "\\")
    return bytes(body, "utf-8").decode("unicode_escape")


def line_of(text: str, offset: int) -> int:
    return text.count("\n", 0, offset) + 1


def enum_values(path: Path, name: str) -> list[str]:
    if not path.exists():
        return []
    text = read_text(path)
    block = re.search(rf"enum\s+{re.escape(name)}\s*:\s*string\s*\{{(.*?)\n\}}", text, re.S)
    return re.findall(r"case\s+\w+\s*=\s*['\"]([^'\"]+)['\"]", block.group(1)) if block else []


def const_array(path: Path, name: str) -> list[str]:
    if not path.exists():
        return []
    block = re.search(rf"const\s+{re.escape(name)}\s*=\s*\[(.*?)\]\s*;", read_text(path), re.S)
    return re.findall(r"['\"]([^'\"]+)['\"]", block.group(1)) if block else []


def int_constants(path: Path) -> list[str]:
    if not path.exists():
        return []
    return re.findall(r"const\s+([A-Z][A-Z0-9_]*)\s*=\s*\d+\s*;", read_text(path))


def notification_prefixes(root: Path) -> list[str]:
    """Values returned by get_notification_default_message_prefix() overrides."""
    found = []
    for path in root.rglob("*.php"):
        if any(part in SKIP_DIRS for part in path.relative_to(root).parts):
            continue
        found += re.findall(
            r"function\s+get_notification_default_message_prefix[^{]*\{\s*return\s*['\"]([^'\"]+)['\"]",
            read_text(path))
    return sorted(set(found))


def booking_status_values(root: Path) -> list[str]:
    path = root / "classes/local/manager/event_manager.php"
    if not path.exists():
        return []
    block = re.search(r"function\s+get_booking_status_colors[^{]*\{\s*return\s*\[(.*?)\]\s*;",
                      read_text(path), re.S)
    return re.findall(r"(?m)^\s*(\d+)\s*=>", block.group(1)) if block else []


def reflection_target(root: Path, call: dict) -> tuple[str, list[str]]:
    """For get_string(strtolower($constantname)) find the reflected class.

    Looking the class up inside the enclosing function is stable against edits
    above it, which a line number is not.
    """
    path = root / call["file"]
    if not path.exists() or not call["function"]:
        return "", []
    body = re.search(
        rf"function\s+{re.escape(call['function'])}\s*\(.*?"
        r"(?=\n    (?:public|private|protected|function)|\Z)",
        read_text(path), re.S)
    if not body:
        return "", []
    target = re.search(r"ReflectionClass\s*\(\s*\\?([\w\\]+)::class", body.group(0))
    if not target:
        return "", []
    name = target.group(1).rsplit("\\", 1)[-1]
    matches = list(root.rglob(f"{name}.php"))
    return name, (int_constants(matches[0]) if matches else [])


# --- the audit -------------------------------------------------------------

@dataclass(frozen=True)
class Ref:
    """One place where a key is referenced."""
    key: str
    kind: str
    file: str
    line: int
    detail: str = ""


@dataclass(frozen=True)
class DynamicSite:
    file: str
    line: int
    expression: str
    resolution: str
    keys: tuple[str, ...]


def audit(root: Path, lang_path: Path) -> dict:
    defined = [e.key for e in parse_lang_file(lang_path).entries]
    refs: dict[str, list[Ref]] = defaultdict(list)
    resolved: list[DynamicSite] = []
    unresolved: list[DynamicSite] = []

    def ref(key, kind, file, line, detail=""):
        refs[key].append(Ref(key, kind, file, line, detail))

    # Key families whose values live somewhere in the code.
    statuses = enum_values(root / "classes/local/entity/resource/bookit_resource_status.php",
                           "bookit_resource_status")
    types = enum_values(root / "classes/local/entity/bookit_notification_type.php",
                        "bookit_notification_type")
    columns = const_array(root / "classes/local/entity/masterchecklist/bookit_checklist_master.php",
                          "DISPLAY_TABLE_COLUMNS")
    prefixes = notification_prefixes(root)
    families = {"resources:status_": statuses,
                "event_bookingstatus_": booking_status_values(root)}
    for prefix in prefixes:
        families[prefix + "_"] = types

    php_calls = collect_php_calls(root)

    # Literal PHP calls.
    for call in php_calls:
        args, where = call["args"], (call["file"], call["line"])
        if call["call"] in {"get_string", "lang_string", "moodle_exception", "print_error"}:
            if len(args) >= 2 and php_literal(args[1]) in COMPONENTS:
                key = php_literal(args[0])
                if key is not None:
                    ref(key, "php", *where, call["call"])
        elif call["call"] == "get_strings" and len(args) >= 2:
            # get_strings(['a', 'b'], 'mod_bookit')
            if php_literal(args[1]) in COMPONENTS:
                for key in re.findall(r"['\"]([^'\"]+)['\"]", args[0]):
                    ref(key, "php", *where, "get_strings")
        elif call["call"] in {"addhelpbutton", "help_icon"}:
            # addHelpButton($field, $identifier, $component), help_icon($identifier, $component)
            index = 1 if call["call"] == "addhelpbutton" else 0
            if len(args) > index + 1 and php_literal(args[index + 1]) in COMPONENTS:
                identifier = php_literal(args[index])
                if identifier is not None:
                    ref(identifier, "help", *where, "help identifier")
                    # _help is mandatory for Moodle, _link optional. Only count
                    # them when they exist, otherwise every help button would
                    # invent a missing _link key.
                    for suffix in ("_help", "_link"):
                        if identifier + suffix in defined:
                            ref(identifier + suffix, "help", *where, "derived from help identifier")

    # Dynamic PHP calls: resolve the ones we know, report the rest.
    for call in php_calls:
        if call["call"] != "get_string" or len(call["args"]) < 2:
            continue
        if php_literal(call["args"][1]) not in COMPONENTS:
            continue
        expr = re.sub(r"\s+", " ", call["args"][0].strip())
        if php_literal(expr) is not None:
            continue  # already handled above

        keys, why = [], ""
        prefix_match = re.match(r"['\"]([^'\"]+)['\"]\s*\.", expr)
        if prefix_match and prefix_match.group(1) in families:
            prefix = prefix_match.group(1)
            keys = [prefix + v for v in families[prefix]]
            why = f"'{prefix}' + known values"
        elif expr == "$case->value":
            keys, why = list(types), "bookit_notification_type enum"
        elif re.fullmatch(r"\$prefix \. '_' \. \$case->value", expr):
            keys = [f"{p}_{t}" for p in prefixes for t in types]
            why = "message prefixes x notification enum"
        elif expr == "$column":
            keys, why = list(columns), "DISPLAY_TABLE_COLUMNS"
        elif expr == "strtolower($constantname)":
            name, constants = reflection_target(root, call)
            if constants:
                keys = [c.lower() for c in constants]
                why = f"{name} constants"
            else:
                unresolved.append(DynamicSite(
                    call["file"], call["line"], expr,
                    f"reflection target {name or '?'} has no integer constants, "
                    "the call cannot produce a valid key", ()))
                continue

        if not keys:
            unresolved.append(DynamicSite(call["file"], call["line"], expr,
                                          "unrecognised dynamic expression", ()))
            continue

        resolved.append(DynamicSite(call["file"], call["line"], expr, why, tuple(keys)))
        for key in keys:
            ref(key, "dynamic-php", call["file"], call["line"], why)

    # JS and Mustache.
    for path in source_files(root):
        rel = str(path.relative_to(root))
        text = read_text(path)

        if path.suffix == ".mustache":
            for m in MUSTACHE_RE.finditer(text):
                ref(m.group(2).strip(), "mustache", rel, line_of(text, m.start()), m.group(1))
            continue
        if path.suffix != ".js":
            continue

        for m in JS_SINGLE_RE.finditer(text):
            ref(m.group(2), "js", rel, line_of(text, m.start()), "core/str")
        for m in JS_OBJECT_RE.finditer(text):
            ref(m.group(2), "js", rel, line_of(text, m.start()), "get_strings array")
        for m in JS_PREFETCH_RE.finditer(text):
            for key in re.findall(r"['\"]([^'\"]+)['\"]", m.group(3)):
                ref(key, "js", rel, line_of(text, m.start()), "prefetchStrings")
        for m in JS_TERNARY_RE.finditer(text):
            for key in (m.group(2), m.group(4)):
                ref(key, "js", rel, line_of(text, m.start()), "conditional getString")

        # Template literals such as `customtemplatedefaultmessage_${type}`.
        for m in JS_TEMPLATE_RE.finditer(text):
            prefix = m.group(1)
            if prefix in families:
                line = line_of(text, m.start())
                keys = [prefix + v for v in families[prefix]]
                resolved.append(DynamicSite(rel, line, m.group(0),
                                            f"'{prefix}' + known values", tuple(keys)))
                for key in keys:
                    ref(key, "dynamic-js", rel, line, f"'{prefix}' + known values")

        # Non-literal first arguments we did not recognise. A variable handed
        # to the plural get_strings() is fine when the file builds its list as
        # object literals, JS_OBJECT_RE already picked those up. A variable key
        # on the singular getString() is not.
        has_object_list = bool(JS_OBJECT_RE.search(text))
        covered = {m.start() for m in JS_TERNARY_RE.finditer(text)}
        for m in JS_ANY_CALL_RE.finditer(text):
            if any(abs(m.start() - c) < 12 for c in covered):
                continue
            name, arg = m.group(1), m.group(2).strip()
            plural = name.lower() == "get_strings"
            if arg[:1] in "'\"" or (arg[:1] == "[" and plural):
                continue
            if arg[:1] == "`" and any(f"`{p}" in arg for p in families):
                continue
            if plural and has_object_list and re.fullmatch(r"[A-Za-z_$][\w$]*", arg):
                continue
            unresolved.append(DynamicSite(rel, line_of(text, m.start()), arg,
                                          "unrecognised dynamic JS expression", ()))

    # Strings Moodle reads without a call site.
    access = root / "db/access.php"
    if access.exists():
        text = read_text(access)
        for m in re.finditer(r"['\"]mod/bookit:([^'\"]+)['\"]\s*=>", text):
            ref("bookit:" + m.group(1), "implicit", "db/access.php",
                line_of(text, m.start()), "capability label")

    messages = root / "db/messages.php"
    if messages.exists():
        text = read_text(messages)
        # Only the top level entries are providers; nested keys sit deeper.
        for m in re.finditer(r"(?m)^\s{4}['\"]([^'\"]+)['\"]\s*=>\s*\[", text):
            ref("messageprovider:" + m.group(1), "implicit", "db/messages.php",
                line_of(text, m.start()), "message provider label")

    for key in STANDARD_KEYS:
        ref(key, "implicit", "<Moodle convention>", 0, "standard plugin key")

    used = set(refs)
    literal = {"php", "js", "mustache", "help"}
    return {
        "defined_count": len(defined),
        "used_count": len(set(defined) & used),
        "unused": sorted(set(defined) - used, key=str.lower),
        "missing_referenced": sorted(used - set(defined), key=str.lower),
        # Keys nothing points at literally. Renaming these needs a look at the
        # call site, grep alone will not find them.
        "rename_hazards": sorted((k for k in defined
                                  if k in used and not any(r.kind in literal for r in refs[k])),
                                 key=str.lower),
        "resolved_dynamic_sites": [asdict(s) for s in resolved],
        "unresolved_dynamic_sites": [asdict(s) for s in unresolved],
        "references": {k: [asdict(r) for r in v] for k, v in sorted(refs.items())},
    }


def warn_unresolved(result: dict) -> None:
    for site in result["unresolved_dynamic_sites"]:
        print(f"WARNING: {site['file']}:{site['line']}: {site['expression']} "
              f"({site['resolution']})", file=sys.stderr)


# --- commands: audit -------------------------------------------------------

def cmd_unused(root: Path, lang: Path, as_json: str | None) -> int:
    result = audit(root, lang)
    if as_json:
        Path(as_json).write_text(json.dumps(result, indent=2, ensure_ascii=False), encoding="utf-8")
    print("\n".join(result["unused"]))
    warn_unresolved(result)
    return 1 if result["unused"] or result["unresolved_dynamic_sites"] else 0


def cmd_report(root: Path, lang: Path, as_json: str | None) -> int:
    result = audit(root, lang)
    if as_json:
        Path(as_json).write_text(json.dumps(result, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"Defined:                  {result['defined_count']}")
    print(f"Used:                     {result['used_count']}")
    print(f"Unused:                   {len(result['unused'])}")
    print(f"Referenced but undefined: {len(result['missing_referenced'])}")
    print(f"Dynamic sites resolved:   {len(result['resolved_dynamic_sites'])}")
    print(f"Dynamic sites unresolved: {len(result['unresolved_dynamic_sites'])}")
    print(f"Rename hazards:           {len(result['rename_hazards'])}")
    warn_unresolved(result)
    return 1 if result["unused"] or result["unresolved_dynamic_sites"] else 0


# --- commands: sort and prune ----------------------------------------------

def cmd_sort(lang: Path, check_only: bool) -> int:
    langfile = parse_lang_file(lang)
    if not langfile.entries:
        print(f"No $string assignments found in {lang}.")
        return 1

    duplicates = duplicate_keys(langfile.entries)
    if duplicates:
        print("Duplicate keys, resolve these before sorting:")
        for key in duplicates:
            print(f"  {key}")
        return 1

    ordered = sorted(langfile.entries, key=lambda e: e.key.lower())
    print(f"Parsed {len(langfile.entries)} string(s) from {lang}")
    if [e.key for e in langfile.entries] == [e.key for e in ordered]:
        print("Already sorted.")
        return 0

    for i, (got, want) in enumerate(zip(langfile.entries, ordered), start=1):
        if got.key != want.key:
            print(f"First mismatch at #{i}: found '{got.key}', expected '{want.key}'.")
            break

    text = compose(langfile, ordered)
    verify_only_reordered(langfile, ordered, text)
    print("Verified: same keys, same assignment blocks, only the order changes.")

    if check_only:
        print("Check only, nothing written.")
        return 1

    write_with_backup(lang, text)
    print(f"Sorted. Backup: {lang}.bak")
    return 0


def cmd_prune(root: Path, lang: Path, force: bool) -> int:
    result = audit(root, lang)
    warn_unresolved(result)

    # An unresolved call site may well be the only user of a key we are about
    # to delete, so refuse unless someone insists.
    if result["unresolved_dynamic_sites"] and not force:
        print("Refusing to prune while dynamic call sites are unresolved.")
        print("Check the warnings above, then rerun with --force to override.")
        return 1

    unused = set(result["unused"])
    if not unused:
        print("Nothing to remove.")
        return 0

    langfile = parse_lang_file(lang)
    kept = [e for e in langfile.entries if e.key not in unused]
    print(f"Removing {len(unused)} string(s):")
    for key in sorted(unused, key=str.lower):
        print(f"  - {key}")

    # Keep the order of what remains so the diff shows deletions only.
    pruned = LangFile(langfile.prefix, kept, langfile.suffix)
    text = compose(pruned, kept)
    verify_only_reordered(pruned, kept, text)

    write_with_backup(lang, text)
    print(f"Pruned. Backup: {lang}.bak")
    return 0


# --- commands: inline comments ---------------------------------------------

def strip_inline_comment(line: str, in_block: bool) -> tuple[str, bool]:
    """Drop a trailing comment, keep whole comment lines.

    in_block tracks an open /* ... */ across lines. Without it the "//" inside
    a URL in the file header docblock looks like the start of a comment and the
    line gets cut in half.
    """
    body, newline = line, ""
    for ending in ("\r\n", "\n"):
        if line.endswith(ending):
            body, newline = line[:-len(ending)], ending
            break

    if in_block:
        return line, "*/" not in body

    quote = None
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
        if ch in "'\"":
            quote = ch
            i += 1
            continue

        end = len(body)
        if body.startswith("//", i) or ch == "#":
            pass
        elif body.startswith("/*", i):
            close = body.find("*/", i + 2)
            if close == -1:
                # Block comment continues on the next line.
                return line, True
            end = close + 2
        else:
            i += 1
            continue

        before = body[:i]
        if not before.strip():
            return line, False  # the whole line is a comment, keep it
        rest = "" if end == len(body) else body[end:]
        return before.rstrip() + rest + newline, False

    return line, False


def cmd_strip_comments(path: Path) -> int:
    original = read_text(path)
    lines = original.splitlines(keepends=True)
    changed = []
    out = []
    in_block = False
    for number, line in enumerate(lines, start=1):
        stripped, in_block = strip_inline_comment(line, in_block)
        if stripped != line:
            changed.append(number)
        out.append(stripped)

    if not changed:
        print("No inline comments found.")
        return 0

    write_with_backup(path, "".join(out))
    print(f"Removed inline comments on {len(changed)} line(s): "
          f"{', '.join(str(n) for n in changed)}")
    print(f"Standalone comment lines untouched. Backup: {path}.bak")
    return 0


# --- commands: translation -------------------------------------------------

# Things a machine translator must not touch.
PLACEHOLDER_RE = re.compile(r"""(
        \{\$a(?:->[A-Za-z0-9_]+)?\}   |   # {$a} and {$a->name}
        \{[A-Z0-9_]+\}                |   # ###RECIPIENT### style markers
        <[^>]+>                       |   # HTML tags
        &[A-Za-z][A-Za-z0-9]+;        |   # HTML entities
        %[sdif]                       |   # printf placeholders
        \d+(?:[.,]\d+)?                   # numbers
    )""", re.VERBOSE)


def protect(text: str) -> tuple[str, list[str]]:
    keep: list[str] = []

    def swap(match):
        keep.append(match.group(0))
        return f"BOOKITPLACEHOLDER{len(keep) - 1}X"

    return PLACEHOLDER_RE.sub(swap, text), keep


def restore(text: str, keep: list[str]) -> str:
    for index, value in enumerate(keep):
        text = text.replace(f"BOOKITPLACEHOLDER{index}X", value)
    return text


def php_escape(text: str) -> str:
    return text.replace("\\", "\\\\").replace("'", "\\'")


def php_value(statement: str) -> str | None:
    """Value of a one-literal assignment, or None for concatenations."""
    eq = statement.find("=")
    if eq == -1:
        return None
    i = eq + 1
    while i < len(statement) and statement[i].isspace():
        i += 1
    if i >= len(statement) or statement[i] not in "'\"":
        return None

    quote = statement[i]
    i += 1
    chars: list[str] = []
    escaped = False
    while i < len(statement):
        ch = statement[i]
        if escaped:
            chars.append("\\" + ch)
            escaped = False
        elif ch == "\\":
            escaped = True
        elif ch == quote:
            break
        else:
            chars.append(ch)
        i += 1
    else:
        return None

    i += 1
    while i < len(statement) and statement[i].isspace():
        i += 1
    if i >= len(statement) or statement[i] != ";":
        return None  # concatenation or something else we should not touch

    raw = "".join(chars)
    if quote == "'":
        return raw.replace("\\'", "'").replace("\\\\", "\\")
    return raw.replace('\\"', '"').replace("\\\\", "\\")


def argos_translator(from_code: str, to_code: str, install_package: bool, install_model: bool):
    try:
        import argostranslate.package
        import argostranslate.translate
    except ImportError:
        if not install_package:
            raise RuntimeError(
                "argostranslate is not installed. Run\n"
                "  python3 -m pip install argostranslate\n"
                "or pass --install-argos.")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "argostranslate"])
        import argostranslate.package
        import argostranslate.translate

    def lookup():
        languages = argostranslate.translate.get_installed_languages()
        source = next((l for l in languages if l.code == from_code), None)
        target = next((l for l in languages if l.code == to_code), None)
        return source.get_translation(target) if source and target else None

    translator = lookup()
    if translator:
        return translator
    if not install_model:
        raise RuntimeError(f"No Argos model for {from_code}->{to_code}. "
                           "Pass --install-model or run the install-translator command.")

    argostranslate.package.update_package_index()
    package = next((p for p in argostranslate.package.get_available_packages()
                    if p.from_code == from_code and p.to_code == to_code), None)
    if package is None:
        raise RuntimeError(f"No Argos package available for {from_code}->{to_code}.")
    argostranslate.package.install_from_path(package.download())

    translator = lookup()
    if translator is None:
        raise RuntimeError("Argos package installed but the language pair is still missing.")
    return translator


def cmd_install_translator(from_code: str, to_code: str, install_package: bool) -> int:
    argos_translator(from_code, to_code, install_package, install_model=True)
    print(f"Argos model ready: {from_code}->{to_code}")
    return 0


def cmd_sync(en_path: Path, de_path: Path, placeholder: bool,
             install_package: bool, install_model: bool) -> int:
    en = parse_lang_file(en_path)
    de = parse_lang_file(de_path) if de_path.exists() else LangFile(en.prefix, [], en.suffix)

    de_keys = {e.key for e in de.entries}
    en_keys = {e.key for e in en.entries}
    missing = sorted((e for e in en.entries if e.key not in de_keys), key=lambda e: e.key.lower())

    stale = sorted(de_keys - en_keys, key=str.lower)
    if stale:
        print(f"{len(stale)} key(s) in DE but not in EN:")
        for key in stale:
            print(f"  {key}")

    if not missing:
        print("DE already has every EN key.")
        return 0
    print(f"{len(missing)} EN string(s) missing from DE.")

    newline = detect_newline(read_text(en_path))
    translator = None
    if not placeholder:
        translator = argos_translator("en", "de", install_package, install_model)

    added = []
    for entry in missing:
        english = php_value(entry.statement)
        if english is None:
            # Concatenation or similar. Copy it verbatim and let a human deal.
            statement = entry.statement.rstrip("\r\n") + " // TODO: translate" + newline
            print(f"  {entry.key}: complex assignment copied as is")
        else:
            if placeholder:
                value, note = english, "// TODO: translate"
            else:
                protected, kept = protect(english)
                value = restore(translator.translate(protected), kept)
                note = "// TODO: check machine translation"
            statement = f"$string['{entry.key}'] = '{php_escape(value)}'; {note}{newline}"
            print(f"  {entry.key}: {'copied' if placeholder else 'translated'}")
        added.append(LangEntry(entry.key, newline, statement))

    combined = LangFile(de.prefix, de.entries + added, de.suffix)
    ordered = sorted(combined.entries, key=lambda e: e.key.lower())
    text = compose(combined, ordered)

    # Existing DE entries must come through byte for byte.
    before = entry_counter(de.entries)
    after = entry_counter([e for e in parse_lang_text(text).entries if e.key in de_keys])
    if before != after:
        raise RuntimeError("verification failed: existing DE entries changed")

    write_with_backup(de_path, text)
    print(f"Wrote {len(added)} string(s) to {de_path}. Backup: {de_path}.bak")
    print(f"Drop the TODO markers later with: strip-comments {de_path}")
    return 0


# --- CLI -------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description="Language string tool for mod_bookit.")
    parser.add_argument("--root", default=".", help="plugin root (default: current directory)")
    sub = parser.add_subparsers(dest="command", required=True)

    for name, help_text in [("unused", "list strings nothing references"),
                            ("report", "print counts only")]:
        p = sub.add_parser(name, help=help_text)
        p.add_argument("file", nargs="?", default=DEFAULT_EN)
        p.add_argument("--json", help="also write the full result here")

    p = sub.add_parser("sort", help="sort strings alphabetically")
    p.add_argument("file", nargs="?", default=DEFAULT_EN)
    p.add_argument("--check", action="store_true", help="report only, write nothing")

    p = sub.add_parser("prune", help="delete unused strings")
    p.add_argument("file", nargs="?", default=DEFAULT_EN)
    p.add_argument("--force", action="store_true",
                   help="prune even though dynamic call sites are unresolved")

    p = sub.add_parser("sync", help="add missing DE strings, translated with Argos")
    p.add_argument("--en", default=DEFAULT_EN)
    p.add_argument("--de", default=DEFAULT_DE)
    p.add_argument("--placeholder", action="store_true", help="copy English instead of translating")
    p.add_argument("--install-argos", action="store_true", help="pip install argostranslate if missing")
    p.add_argument("--install-model", action="store_true", help="download the en->de model if missing")

    p = sub.add_parser("strip-comments", help="remove trailing comments, keep full comment lines")
    p.add_argument("file", nargs="?", default=DEFAULT_DE)

    p = sub.add_parser("install-translator", help="install Argos and the en->de model")
    p.add_argument("--from-code", default="en")
    p.add_argument("--to-code", default="de")
    p.add_argument("--install-argos", action="store_true", help="pip install argostranslate if missing")

    args = parser.parse_args()
    root = Path(args.root).resolve()

    def resolve(name: str) -> Path:
        path = Path(name)
        return path if path.is_absolute() else root / path

    try:
        if args.command in ("unused", "report"):
            lang = resolve(args.file)
            if not lang.exists():
                sys.exit(f"language file not found: {lang}")
            runner = cmd_unused if args.command == "unused" else cmd_report
            return runner(root, lang, args.json)
        if args.command == "sort":
            return cmd_sort(resolve(args.file), args.check)
        if args.command == "prune":
            return cmd_prune(root, resolve(args.file), args.force)
        if args.command == "sync":
            return cmd_sync(resolve(args.en), resolve(args.de), args.placeholder,
                            args.install_argos, args.install_model)
        if args.command == "strip-comments":
            return cmd_strip_comments(resolve(args.file))
        if args.command == "install-translator":
            return cmd_install_translator(args.from_code, args.to_code, args.install_argos)
    except (OSError, ValueError, RuntimeError) as exc:
        print(f"Error: {exc}", file=sys.stderr)
        return 1
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
