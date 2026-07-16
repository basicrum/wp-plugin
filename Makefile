COMPOSE ?= docker compose
PHP_SERVICE = php
WP_SERVICE = wordpress
DB_SERVICE = db
WPCLI_SERVICE = wpcli
WPCLI_PLUGIN_WORKDIR = /var/www/html/wp-content/plugins/basicrum
PLUGIN_DIR = plugins/basicrum
PLUGIN_WORKDIR = /workspace
TEST_DB_NAME = wordpress_test
TEST_DB_USER = root
TEST_DB_PASS = root
TEST_DB_HOST = db
WP_TEST_VERSION ?= latest

.PHONY: help build up down restart logs shell composer-install wp-install lint lint-fix lint-php translations integration-setup integration test package clean

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
	@echo "  translations       Update POT, PO, and MO translation catalogs"
	@echo "  unit               Run unit tests"
	@echo "  integration-setup  Install WordPress test suite inside containers"
	@echo "  integration        Run integration tests"
	@echo "  test               Run unit and integration tests"
	@echo "  package            Build release zip via prerelease workflow logic"

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

translations:
	$(COMPOSE) run --rm --no-deps --user "$$(id -u):$$(id -g)" -e HOME=/tmp -w $(WPCLI_PLUGIN_WORKDIR) $(WPCLI_SERVICE) sh /tools/update-translations.sh .

unit:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer unit

integration-setup:
	$(COMPOSE) up -d $(DB_SERVICE)
	$(COMPOSE) run --rm $(PHP_SERVICE) bash /tools/install-wp-tests.sh $(TEST_DB_NAME) $(TEST_DB_USER) $(TEST_DB_PASS) $(TEST_DB_HOST) $(WP_TEST_VERSION)

integration:
	$(COMPOSE) run --rm -w $(PLUGIN_WORKDIR) $(PHP_SERVICE) composer integration

test: unit integration

package:
	$(COMPOSE) run --rm $(PHP_SERVICE) bash -lc 'mkdir -p /tmp/release/basicrum && rsync -rc --exclude-from=/workspace/.distignore /workspace/ /tmp/release/basicrum/ --delete --delete-excluded && cd /tmp/release && zip -r /workspace/basicrum.zip basicrum'

clean:
	$(COMPOSE) down -v
	rm -f basicrum.zip
	rm -rf release
