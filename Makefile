# ---- Leadinfo Reactions: Top-level Makefile ---------------------------------
# Usage highlights:
#   make init          # first-time setup (env files, deps)
#   make up            # build & start Docker (api+db), wait for DB, run seed
#   make down          # stop everything & remove volumes
#   make fe-dev        # start Vite dev server (frontend/.env must point to API)
#   make test-be       # run backend PHPUnit tests inside the api container
#   make api-logs      # tail API logs
#   make db-cli        # open MySQL client inside the db container
#   make sh-api        # shell into api container
#   make sh-db         # shell into db container

SHELL := /bin/sh
COMPOSE := docker compose

# Paths
BACKEND := backend
FRONTEND := frontend
SCHEMA := $(BACKEND)/src/Database/migrations/schema.sql

# Helpers
api_id = $$($(COMPOSE) ps -q api)
db_id  = $$($(COMPOSE) ps -q db)

.PHONY: init env up wait-db seed migrate restart-api rebuild-api \
        down down-clean ps api-logs db-logs sh-api sh-db db-cli \
        be-install fe-install fe-dev fe-build fe-preview \
        test-be test-be-warnings clean

# -----------------------------------------------------------------------------
# First-time setup: env files + dependencies
# -----------------------------------------------------------------------------
init: env be-install fe-install ## First-time setup: create .envs & install deps

env: ## Create missing .env files from examples (non-destructive)
	@test -f $(BACKEND)/.env || { \
		echo "Creating $(BACKEND)/.env from example (edit if needed)…"; \
		cp -n $(BACKEND)/.env.example $(BACKEND)/.env 2>/dev/null || true; \
	}
	@test -f $(FRONTEND)/.env || { \
		echo "Creating $(FRONTEND)/.env from example (edit if needed)…"; \
		cp -n $(FRONTEND)/.env.example $(FRONTEND)/.env 2>/dev/null || true; \
	}

be-install: ## Install backend composer deps inside api container image
	@echo "Installing backend dependencies (composer install)…"
	$(COMPOSE) run --rm api composer install --no-interaction --prefer-dist

fe-install: ## Install frontend npm deps on the host
	@echo "Installing frontend dependencies…"
	@cd "$(FRONTEND)" && \
	if [ -f package-lock.json ]; then \
		echo "→ using npm ci"; npm ci; \
	else \
		echo "→ no lockfile found, running npm install to create one"; npm install; \
	fi
	@echo "✅ Frontend deps ready."

# -----------------------------------------------------------------------------
# Docker lifecycle
# -----------------------------------------------------------------------------
up: ## Build containers, start stack, wait for DB, then seed schema
	$(COMPOSE) build api
	$(COMPOSE) up -d
	@$(MAKE) wait-db
	@$(MAKE) seed
	@echo "✅ Stack is ready."

wait-db: ## Wait until MySQL is accepting connections
	@echo "⏳ Waiting for MySQL to be ready…"
	@until docker exec -i $$( $(COMPOSE) ps -q db ) \
		sh -c 'mysqladmin ping -h 127.0.0.1 -uroot -psecret --silent'; do \
		sleep 2; \
	done
	@echo "✅ MySQL is ready."

seed: ## Run schema.sql against the leadinfo database
	@echo "🌱 Seeding database schema from $(SCHEMA)…"
	docker exec -i $(db_id) \
		sh -c 'exec mysql -h 127.0.0.1 -uroot -psecret leadinfo' < $(SCHEMA)
	@echo "✅ Seed complete."

restart-api: ## Restart only the api service (keeping volumes)
	$(COMPOSE) up -d --force-recreate api

rebuild-api: ## Rebuild the api image without cache and restart it
	$(COMPOSE) build --no-cache api
	$(COMPOSE) up -d api

down: ## Stop and remove containers + volumes
	$(COMPOSE) down -v
	@echo "🧹 Containers and volumes removed."

down-clean: ## Stop stack and also prune dangling images/volumes (destructive)
	$(COMPOSE) down -v --remove-orphans
	docker system prune -f
	@echo "🧹 Deep clean complete."

ps: ## Show running services
	$(COMPOSE) ps

# -----------------------------------------------------------------------------
# Logs & shells
# -----------------------------------------------------------------------------
api-logs: ## Tail API logs
	$(COMPOSE) logs -f api

db-logs: ## Tail DB logs
	$(COMPOSE) logs -f db

sh-api: ## Shell into the api container
	docker exec -it $(api_id) sh

sh-db: ## Shell into the db container
	docker exec -it $(db_id) sh

db-cli: ## Open mysql client inside the db container
	docker exec -it $(db_id) \
		mysql -h 127.0.0.1 -uroot -psecret leadinfo

# -----------------------------------------------------------------------------
# Backend / Frontend commands
# -----------------------------------------------------------------------------
test-be: ## Run backend PHPUnit tests (pretty)
	$(COMPOSE) exec api ./vendor/bin/phpunit --testdox

test-be-warnings: ## Run backend tests with warnings shown
	$(COMPOSE) exec api ./vendor/bin/phpunit --display-warnings --testdox

fe-dev: ## Start Vite dev server (runs in foreground)
	cd $(FRONTEND) && npm run dev

fe-build: ## Build frontend for production
	cd $(FRONTEND) && npm run build

fe-preview: ## Preview built frontend (after fe-build)
	cd $(FRONTEND) && npm run preview

# -----------------------------------------------------------------------------
# Misc
# -----------------------------------------------------------------------------
clean: ## Remove node_modules & dist in frontend (local cleanup)
	rm -rf $(FRONTEND)/node_modules $(FRONTEND)/dist
	@echo "🧽 Frontend artifacts removed."
