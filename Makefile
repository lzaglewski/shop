.PHONY: test test-unit test-functional test-coverage test-debug test-file test-class test-filter db-init db-init-test db-reset db-reset-test

# Domyślne zmienne
DOCKER_PHP = docker exec shop_php
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

# Konfiguracja bazy danych
db-init: ## Inicjalizacja bazy danych deweloperskiej
	@echo "Inicjalizacja bazy danych deweloperskiej..."
	docker exec shop_mysql mariadb -uroot -p12345678 -e "CREATE DATABASE IF NOT EXISTS shop;"
	$(DOCKER_PHP) bin/console doctrine:schema:create || true
	$(DOCKER_PHP) bin/console doctrine:migrations:migrate --no-interaction || true
	@echo "Baza danych deweloperska została zainicjalizowana."

db-init-test: ## Inicjalizacja bazy danych testowej
	@echo "Inicjalizacja bazy danych testowej..."
	docker exec shop_mysql mariadb -uroot -p12345678 -e "CREATE DATABASE IF NOT EXISTS shop_test;"
	docker exec shop_mysql mariadb -uroot -p12345678 -e "CREATE DATABASE IF NOT EXISTS shop_test_test;"
	docker exec shop_mysql mariadb -uroot -p12345678 -e "GRANT ALL PRIVILEGES ON shop_test.* TO 'user'@'%';"
	docker exec shop_mysql mariadb -uroot -p12345678 -e "GRANT ALL PRIVILEGES ON shop_test_test.* TO 'user'@'%';"
	$(DOCKER_PHP) bin/console doctrine:schema:create --env=test || true
	@echo "Baza danych testowa została zainicjalizowana."

db-reset: ## Reset bazy danych deweloperskiej
	@echo "Resetowanie bazy danych deweloperskiej..."
	docker exec shop_mysql mariadb -uroot -p12345678 -e "DROP DATABASE IF EXISTS shop; CREATE DATABASE shop;"
	$(DOCKER_PHP) bin/console doctrine:schema:create
	$(DOCKER_PHP) bin/console doctrine:migrations:migrate --no-interaction
	@echo "Baza danych deweloperska została zresetowana."

db-reset-test: ## Reset bazy danych testowej
	@echo "Resetowanie bazy danych testowej..."
	docker exec shop_mysql mariadb -uroot -p12345678 -e "DROP DATABASE IF EXISTS shop_test; CREATE DATABASE shop_test;"
	docker exec shop_mysql mariadb -uroot -p12345678 -e "DROP DATABASE IF EXISTS shop_test_test; CREATE DATABASE shop_test_test;"
	docker exec shop_mysql mariadb -uroot -p12345678 -e "GRANT ALL PRIVILEGES ON shop_test.* TO 'user'@'%';"
	docker exec shop_mysql mariadb -uroot -p12345678 -e "GRANT ALL PRIVILEGES ON shop_test_test.* TO 'user'@'%';"
	$(DOCKER_PHP) bin/console doctrine:schema:create --env=test
	@echo "Baza danych testowa została zresetowana."

# Dla kompatybilności wstecznej
test-db-setup: db-init-test ## Alias dla db-init-test

# Pomoc
help: ## Wyświetl pomoc
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Domyślny cel
.DEFAULT_GOAL := help
