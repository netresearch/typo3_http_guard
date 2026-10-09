# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
.PHONY: test quality style fix-style static rector architecture fuzz mutation performance native package

test:
	composer ci:test:php:unit

quality:
	composer check:local

style:
	composer ci:test:php:cgl

fix-style:
	composer ci:fix:php:cgl

static:
	composer ci:test:php:phpstan

rector:
	composer ci:test:php:rector

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
