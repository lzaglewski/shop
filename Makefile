.PHONY: test test-unit test-functional test-coverage test-debug test-file test-class test-filter test-db-setup

# Domyślne zmienne
DOCKER_PHP = docker exec kakawa_php
PHPUNIT = vendor/bin/phpunit
FILTER =
FILE =
CLASS =

# Główne cele
test: ## Uruchom wszystkie testy
	$(DOCKER_PHP) $(PHPUNIT)

test-unit: ## Uruchom tylko testy jednostkowe
	$(DOCKER_PHP) $(PHPUNIT) --testsuite Unit

test-functional: ## Uruchom tylko testy funkcjonalne
	$(DOCKER_PHP) $(PHPUNIT) --testsuite Functional

test-coverage: ## Uruchom testy z pokryciem kodu
	$(DOCKER_PHP) XDEBUG_MODE=coverage $(PHPUNIT) --coverage-html var/coverage

test-debug: ## Uruchom testy z debugowaniem
	$(DOCKER_PHP) XDEBUG_MODE=debug $(PHPUNIT)

# Cele specyficzne
test-file: ## Uruchom testy z konkretnego pliku (użyj: make test-file FILE=ścieżka/do/pliku)
	$(DOCKER_PHP) $(PHPUNIT) $(FILE)

test-class: ## Uruchom testy konkretnej klasy (użyj: make test-class CLASS=NazwaKlasyTest)
	$(DOCKER_PHP) $(PHPUNIT) --filter $(CLASS)

test-filter: ## Uruchom testy pasujące do filtra (użyj: make test-filter FILTER=nazwaMetody)
	$(DOCKER_PHP) $(PHPUNIT) --filter $(FILTER)

# Konfiguracja bazy danych testowej
test-db-setup: ## Skonfiguruj bazę danych testową
	$(DOCKER_PHP) bin/console doctrine:database:drop --force --env=test || true
	$(DOCKER_PHP) bin/console doctrine:database:create --env=test
	$(DOCKER_PHP) bin/console doctrine:schema:create --env=test

# Pomoc
help: ## Wyświetl pomoc
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Domyślny cel
.DEFAULT_GOAL := help
