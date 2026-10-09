#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Validate project-specific agent pointers and real local/CI entry points."""
import json
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[2]


def require(condition, label):
    if not condition:
        raise ValueError(label)


def run_commands(workflow):
    """Read run scalars only; workflow YAML syntax is separately actionlint-gated."""
    lines = workflow.splitlines()
    commands = []
    for position, line in enumerate(lines):
        match = re.fullmatch(r'(\s+)(?:-\s+)?run:\s*(.*)', line)
        if match is None:
            continue
        indent, value = match.groups()
        if value in {'|', '|-', '|+', '>', '>-', '>+'}:
            block = []
            for candidate in lines[position + 1:]:
                if candidate.strip() and len(candidate) - len(candidate.lstrip()) <= len(indent):
                    break
                block.append(candidate.strip())
            commands.append('\n'.join(block))
        else:
            commands.append(value.strip('"\''))
    return commands


def check():
    agents = ROOT / 'AGENTS.md'
    require(agents.is_file(), 'AGENTS.md missing')
    text = agents.read_text(encoding='utf-8')
    require(0 < len(text.splitlines()) < 150, 'AGENTS.md must stay below 150 lines')
    for heading in ('Overview', 'Setup', 'Commands', 'Architecture', 'Testing', 'Boundaries'):
        require(f'## {heading}\n' in text, f'Missing agent section: {heading}')
    require(re.search(r'\{\{[A-Z_]+\}\}', text) is None, 'Unresolved agent placeholder')
    for relative in ('AGENTS.md', '.github/copilot-instructions.md'):
        document = ROOT / relative
        for link in re.findall(r'\]\(([^)]+)\)', document.read_text(encoding='utf-8')):
            if link.startswith(('https://', 'http://', '#')):
                continue
            target = (document.parent / link.split('#', 1)[0]).resolve()
            require(target.is_relative_to(ROOT.resolve()) and target.exists(), f'Broken agent pointer: {relative}')
    manifest = json.loads((ROOT / 'composer.json').read_text())
    scripts = manifest.get('scripts', {})
    for command in re.findall(r'`composer ([a-zA-Z0-9:_-]+)(?:[^`]*)`', text):
        require(command in {'install'} or command in scripts, f'Undeclared Composer command: {command}')
    expected = {
        'check:harness': 'python3 Build/Scripts/verify-harness.py',
        'check:secrets': 'python3 Build/Scripts/check-staged-secrets.py',
        'check:local': 'bash Build/Scripts/check-local-quality.sh',
    }
    for name, command in expected.items():
        entry = scripts.get(name)
        require(entry == command or entry == [command], f'Composer hook command drift: {name}')
    hook_config = manifest.get('extra', {}).get('captainhook', {}).get('config')
    require(hook_config == 'Build/captainhook.json', 'Declare the canonical Build/ hook configuration')
    hooks = json.loads((ROOT / hook_config).read_text())
    require(hooks.get('config', {}).get('bootstrap') == '../.Build/vendor/autoload.php',
            'CaptainHook bootstrap must match the Composer vendor directory')
    require(manifest.get('config', {}).get('allow-plugins', {}).get('captainhook/hook-installer') is True,
            'CaptainHook installer must be explicitly enabled')
    precommit = hooks.get('pre-commit', {})
    require(precommit.get('enabled') is True, 'Pre-commit quality hook disabled')
    actions = {action.get('action') for action in precommit.get('actions', [])}
    require({'composer check:secrets', 'composer check:local'} <= actions, 'Pre-commit checks missing')
    commit = hooks.get('commit-msg', {})
    require(commit.get('enabled') is True and len(commit.get('actions', [])) >= 2,
            'Commit-message and sign-off checks missing')
    require(hooks.get('pre-push', {}).get('enabled') is True, 'Pre-push tests disabled')
    require('composer ci:test:php:unit' in {action.get('action') for action in hooks['pre-push'].get('actions', [])},
            'Pre-push Unit tests missing')
    workflow = (ROOT / '.github/workflows/ci.yml').read_text()
    require(any(re.search(r'(^|\n)composer check:harness(?:\s|$)', command) for command in run_commands(workflow)),
            'CI must execute the same harness check in a real run step')
    require((ROOT / 'Documentation/Development/Index.rst').is_file()
            and (ROOT / 'Documentation/Decisions/SingleExtension.rst').is_file(), 'Manual architecture pointers missing')
    require(not (ROOT / 'docs').exists(), 'Use the canonical Documentation/ tree')
    for name, scope in (('php', 'Classes/**/*.php'), ('tests', 'Tests/**/*.php')):
        instruction = ROOT / f'.github/instructions/{name}.instructions.md'
        require(instruction.is_file() and scope in instruction.read_text(), f'Scoped instruction missing: {name}')
    owners = (ROOT / '.github/CODEOWNERS').read_text()
    require(re.search(r'(?m)^\* @\w[\w-]*\s*$', owners) is not None, 'Default routing owner missing')
    print('Harness pointers, Composer commands, hooks and CI entry point are consistent.')


if __name__ == '__main__':
    try:
        check()
    except (OSError, ValueError, TypeError, KeyError) as error:
        print(f'Harness verification failed: {error}', file=sys.stderr)
        sys.exit(1)
