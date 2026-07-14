.PHONY: test test-unit test-feature test-integration test-coverage test-watch test-debug test-all help

# Colors for output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
RED    := \033[0;31m
NC     := \033[0m # No Color

help: ## Show this help message
	@echo "$(GREEN)SaiQ Testing Commands$(NC)"
	@echo "$(YELLOW)========================$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

test: ## Run all tests (fastest)
	@echo "$(GREEN)Running all tests...$(NC)"
	@./vendor/bin/phpunit --testdox

test-unit: ## Run only unit tests
	@echo "$(GREEN)Running unit tests...$(NC)"
	@./vendor/bin/phpunit --testsuite Unit --testdox

test-feature: ## Run only feature tests
	@echo "$(GREEN)Running feature tests...$(NC)"
	@./vendor/bin/phpunit --testsuite Feature --testdox

test-integration: ## Run only integration tests
	@echo "$(GREEN)Running integration tests...$(NC)"
	@./vendor/bin/phpunit --testsuite Integration --testdox

test-coverage: ## Generate coverage report (HTML)
	@echo "$(GREEN)Generating coverage report...$(NC)"
	@./vendor/bin/phpunit --coverage-html coverage/
	@echo "$(GREEN)✓ Report generated in coverage/index.html$(NC)"

test-coverage-text: ## Show coverage in terminal
	@echo "$(GREEN)Coverage summary:$(NC)"
	@./vendor/bin/phpunit --coverage-text

test-watch: ## Run tests in watch mode (requires phpunit-watcher)
	@echo "$(GREEN)Running tests in watch mode...$(NC)"
	@php vendor/bin/phpunit-watcher watch

test-debug: ## Run tests with verbose output
	@echo "$(GREEN)Running tests in debug mode...$(NC)"
	@./vendor/bin/phpunit --verbose

test-single: ## Run a single test file: make test-single FILE=tests/Feature/AuthenticationTest.php
	@echo "$(GREEN)Running single test file: $(FILE)$(NC)"
	@./vendor/bin/phpunit $(FILE) --testdox

test-method: ## Run a single test method: make test-method FILE=tests/Feature/AuthenticationTest.php METHOD=testCanLoginWithValidCredentials
	@echo "$(GREEN)Running single test method...$(NC)"
	@./vendor/bin/phpunit $(FILE)::$(METHOD) --verbose

test-all: test-unit test-feature test-integration ## Run all test suites

test-fail-fast: ## Stop on first failure
	@echo "$(GREEN)Running tests (stop on first failure)...$(NC)"
	@./vendor/bin/phpunit --stop-on-failure

test-parallel: ## Run tests in parallel (requires ParaTest)
	@echo "$(GREEN)Running tests in parallel...$(NC)"
	@./vendor/bin/paratest --processes 4

db-test-reset: ## Reset test database
	@echo "$(YELLOW)Resetting test database...$(NC)"
	@php artisan migrate:fresh --env=testing --seed
	@echo "$(GREEN)✓ Test database reset$(NC)"

db-test-setup: ## Setup test database
	@echo "$(YELLOW)Setting up test database...$(NC)"
	@mysql -u root -e "CREATE DATABASE IF NOT EXISTS pcaedu_homologa_test;"
	@php artisan migrate --env=testing
	@echo "$(GREEN)✓ Test database ready$(NC)"

install-test-tools: ## Install additional testing tools
	@echo "$(YELLOW)Installing testing tools...$(NC)"
	@composer require --dev phpunit/phpunit-watcher brainmaestro/paratest
	@echo "$(GREEN)✓ Testing tools installed$(NC)"

lint: ## Check code style
	@echo "$(GREEN)Running PHP linter...$(NC)"
	@php -l app/ && echo "$(GREEN)✓ No syntax errors$(NC)"

stats: ## Show project statistics
	@echo "$(GREEN)Project Statistics$(NC)"
	@echo "$(YELLOW)===================$(NC)"
	@find app tests -name "*.php" -type f | wc -l | xargs -I {} echo "PHP Files: {}"
	@find app tests -name "*.php" -type f -exec wc -l {} + | tail -1 | awk '{print "Total Lines: " $$1}'
	@./vendor/bin/phpunit --version

.DEFAULT_GOAL := help
