#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Make measured Clover paths portable and reject reports outside Classes/."""
import argparse
from pathlib import Path
from defusedxml import ElementTree
from defusedxml.common import DefusedXmlException


def normalize(report: Path, root: Path) -> int:
    tree = ElementTree.parse(report, forbid_dtd=True)
    files = tree.findall('.//file')
    if not files:
        raise ValueError('Coverage report contains no source files.')
    root = root.resolve(strict=True)
    seen = set()
    for entry in files:
        name = entry.get('name')
        if not name:
            raise ValueError('Coverage file is missing its source path.')
        path = Path(name)
        absolute = path if path.is_absolute() else root / path
        relative = absolute.resolve(strict=True).relative_to(root)
        if relative.parts[0] != 'Classes' or relative.suffix != '.php':
            raise ValueError('Coverage path is outside the production source tree.')
        normalized = relative.as_posix()
        if normalized in seen:
            raise ValueError('Coverage report contains duplicate source paths.')
        seen.add(normalized)
        entry.set('name', normalized)
    tree.write(report, encoding='utf-8', xml_declaration=True)
    return len(files)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('report', type=Path)
    parser.add_argument('--root', type=Path, default=Path(__file__).resolve().parents[2])
    arguments = parser.parse_args()
    try:
        count = normalize(arguments.report, arguments.root)
    except (ValueError, OSError, ElementTree.ParseError, DefusedXmlException) as error:
        raise SystemExit(f'Invalid measured coverage report: {error}') from error
    print(f'Normalized {count} measured production paths without changing coverage counts.')
