.PHONY: up down seed api-logs db-logs test-be test-fe

up:
	docker compose build api
	docker compose up -d
	@echo "⏳ Waiting for MySQL to be ready..."
	@until docker exec -i $$(docker compose ps -q db) \
		sh -c 'mysqladmin ping -h 127.0.0.1 -uroot -psecret --silent'; \
	do sleep 2; done
	@echo "✅ MySQL is ready."

seed:
	@echo "🌱 Seeding database..."
	docker exec -i $$(docker compose ps -q db) \
		sh -c 'exec mysql -h 127.0.0.1 -uroot -psecret leadinfo' < backend/Database/migrations/schema.sql
	@echo "✅ Database seeded."

down:
	docker compose down -v
	@echo "🧹 Containers and volumes removed."

api-logs:
	docker compose logs api -f

db-logs:
	docker compose logs db -f

restart-api:
	docker compose up -d --force-recreate api

test-be:
	docker compose exec api ./vendor/bin/phpunit --testdox

test-be-warnings:
	docker compose exec api ./vendor/bin/phpunit --display-warnings --testdox