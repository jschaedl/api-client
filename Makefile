COMPOSER_BIN := composer
PHP_BIN := php
PHP_CS_FIXER_BIN := ./vendor/bin/php-cs-fixer
PHPSTAN_BIN	:= ./vendor/bin/phpstan
PHPUNIT_BIN	:= ./vendor/bin/phpunit
PARATEST_BIN := ./vendor/bin/paratest
RECTOR_BIN	:= ./vendor/bin/rector

.PHONY: it
it: vendor tests cs-check qa

vendor: composer.json $(wildcard composer.lock)
	$(COMPOSER_BIN) install --optimize-autoloader

.PHONY: tests
tests: tests-unit tests-integration tests-e2e

.PHONY: tests-unit
tests-unit:
	XDEBUG_MODE=off $(PARATEST_BIN) --group=unit

.PHONY: tests-integration
tests-integration:
	XDEBUG_MODE=off $(PARATEST_BIN) --group=integration

.PHONY: tests-e2e
tests-e2e:
	XDEBUG_MODE=off $(PARATEST_BIN) --group=e2e

.PHONY: tests-coverage
tests-coverage:
	XDEBUG_MODE=coverage $(PHPUNIT_BIN) --coverage-html coverage

.PHONY: qa
qa: cs-check analyse

.PHONY: cs-check
cs-check:
	XDEBUG_MODE=off PHP_CS_FIXER_FUTURE_MODE=1 PHP_CS_FIXER_IGNORE_ENV=1 $(PHP_CS_FIXER_BIN) fix --allow-risky=yes --diff --using-cache=no --verbose --dry-run

.PHONY: cs-fix
cs-fix:
	XDEBUG_MODE=off PHP_CS_FIXER_FUTURE_MODE=1 PHP_CS_FIXER_IGNORE_ENV=1 $(PHP_CS_FIXER_BIN) fix --allow-risky=yes

.PHONY: analyse
analyse:
	XDEBUG_MODE=off $(PHPSTAN_BIN) analyse -c phpstan.neon --memory-limit=-1

.PHONY: phpstan-baseline
analyse-baseline:
	 XDEBUG_MODE=off $(PHPSTAN_BIN)  analyze -c phpstan.neon --generate-baseline=.phpstan/baseline.neon --memory-limit=-1

.PHONY: refactor
refactor:
	XDEBUG_MODE=off $(RECTOR_BIN) process

.PHONY: release
release:
	git tag -as ${VERSION} -m '${VERSION}'
	git tag -v ${VERSION}
	git push origin ${VERSION}
