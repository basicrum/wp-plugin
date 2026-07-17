COMPOSE ?= docker compose
PHP_SERVICE = php
WP_SERVICE = wordpress
DB_SERVICE = db
WPCLI_SERVICE = wpcli
JS_SERVICE = javascript
WPCLI_PLUGIN_WORKDIR = /var/www/html/wp-content/plugins/basicrum
PLUGIN_DIR = plugins/basicrum
PLUGIN_WORKDIR = /workspace
TEST_DB_NAME = wordpress_test
TEST_DB_USER = root
TEST_DB_PASS = root
TEST_DB_HOST = db
WP_TEST_VERSION ?= latest

.PHONY: help build up down restart logs shell composer-install wp-install lint lint-fix lint-php analyse composer-validate composer-audit translations js-install js-test integration-setup integration test package package-verify package-smoke clean

help:
	@echo "Targets:"
	@echo "  build              Build or rebuild local Docker images"
	@echo "  up                 Start and provision local WordPress with Basicrum active"
	@echo "  wp-install         Install WordPress and activate Basicrum if needed"
	@echo "  down               Stop containers"
	@echo "  restart            Restart containers"
	@echo "  logs               Tail logs from WordPress and MySQL"
	@echo "  shell              Open a shell in the PHP container"
	@echo "  composer-install   Install Composer dependencies in container"
	@echo "  lint-php           Run PHP syntax lint"
	@echo "  lint               Run PHPCS"
	@echo "  lint-fix           Run PHPCBF"
	@echo "  analyse            Run PHPStan static analysis"
	@echo "  composer-validate  Validate Composer metadata and lock file"
	@echo "  composer-audit     Audit locked Composer dependencies"
	@echo "  translations       Update POT, PO, and MO translation catalogs"
	@echo "  js-install         Install locked JavaScript test dependencies"
	@echo "  js-test            Run loader behavior tests in Chromium"
	@echo "  unit               Run unit tests"
	@echo "  integration-setup  Install WordPress test suite inside containers"
	@echo "  integration        Run integration tests"
	@echo "  test               Run unit and integration tests"
	@echo "  package            Build and inspect the release ZIP and checksum"
	@echo "  package-verify     Inspect the existing release ZIP and checksum"
	@echo "  package-smoke      Install and test the release ZIP in clean WordPress"

build:
	$(COMPOSE) build $(PHP_SERVICE)

up:
	$(MAKE) composer-install
	$(MAKE) wp-install

wp-install:
	$(COMPOSE) up -d $(DB_SERVICE) $(WP_SERVICE)
	$(COMPOSE) run --rm $(WPCLI_SERVICE) sh /tools/setup-wordpress.sh

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f $(WP_SERVICE) $(DB_SERVICE)

shell:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) bash

composer-install:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer install --no-interaction --prefer-dist

lint-php:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer lint:php

lint:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer lint

lint-fix:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer lint:fix

analyse:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer analyse

composer-validate:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer validate --strict

composer-audit:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer audit --locked

translations:
	$(COMPOSE) run --rm --no-deps --user "$$(id -u):$$(id -g)" -e HOME=/tmp -w $(WPCLI_PLUGIN_WORKDIR) $(WPCLI_SERVICE) sh /tools/update-translations.sh .

js-install:
	$(COMPOSE) run --rm --no-deps --user "$$(id -u):$$(id -g)" -e HOME=/tmp $(JS_SERVICE) npm ci --no-audit --no-fund

js-test: js-install
	$(COMPOSE) run --rm --no-deps --user "$$(id -u):$$(id -g)" -e HOME=/tmp $(JS_SERVICE) npm run test:js

unit:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer unit

integration-setup:
	$(COMPOSE) up -d $(DB_SERVICE)
	$(COMPOSE) run --rm $(PHP_SERVICE) bash /tools/install-wp-tests.sh $(TEST_DB_NAME) $(TEST_DB_USER) $(TEST_DB_PASS) $(TEST_DB_HOST) $(WP_TEST_VERSION)

integration:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer integration

test: unit integration js-test

package:
	$(COMPOSE) run --rm --no-deps -w /repo $(PHP_SERVICE) sh /tools/build-release.sh /repo/$(PLUGIN_DIR) /repo/release

package-verify:
	$(COMPOSE) run --rm --no-deps -w /repo $(PHP_SERVICE) sh /tools/verify-release.sh /repo/release/basicrum.zip

package-smoke: package
	sh tools/smoke-test-release.sh release/basicrum.zip

clean:
	$(COMPOSE) down -v
	rm -rf release
