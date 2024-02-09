include .env

DC?=docker-compose
DM?=$(DC) exec -T db
DP?=$(DC) exec -T php-fpm

up:
	$(DC) up -d --remove-orphans
down:
	$(DC) down

init: db-init data-init

db-init:
	$(DM) /bin/sh -c "mariadb -u root -p${DATABASE_PASSWORD} -e 'DROP DATABASE IF EXISTS ${DATABASE_NAME};'"
	$(DM) /bin/sh -c "mariadb -u root -p${DATABASE_PASSWORD} -e 'CREATE DATABASE ${DATABASE_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"
	$(DC) cp sql/init.sql db:/tmp/init.sql
	$(DM) /bin/sh -c 'mariadb -u root -p${DATABASE_PASSWORD} -D${DATABASE_NAME} < /tmp/init.sql'

data-init:
	$(DP) php /var/www/html/script/data-init.php