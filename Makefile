# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
-include .Build/vendor/netresearch/typo3-ci-workflows/Makefile.include

.PHONY: style fix-style static architecture fuzz mutation performance native package

quality: architecture

style:
	composer ci:test:php:cgl

fix-style:
	composer ci:fix:php:cgl

static:
	composer ci:test:php:phpstan

architecture:
	composer ci:test:php:architecture

fuzz:
	composer ci:test:php:fuzz

mutation:
	composer ci:test:php:mutation

performance:
	composer ci:test:php:performance

native:
	composer ci:test:php:functional

package:
	python3 Build/Scripts/build-extension.py

# Keep the shared entry points, while preserving the project's reviewed AST
# workflow and its policy against suppressing diagnostics in baselines.
rector-fix:
	@printf '%s\n' 'Review the Rector dry-run and apply PHP edits with php-ast-edit.' >&2
	@exit 2

phpstan-baseline:
	@printf '%s\n' 'Fix static-analysis diagnostics; this project does not generate suppressing baselines.' >&2
	@exit 2
