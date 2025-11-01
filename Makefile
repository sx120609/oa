.PHONY: up down stop build test stan fix

up:
	docker-compose up -d --build

stop:
	docker-compose stop

down:
	docker-compose down -v

build:
	docker-compose build

test:
	php artisan test

stan:
	./vendor/bin/phpstan analyse --memory-limit=1G

fix:
	./vendor/bin/pint
