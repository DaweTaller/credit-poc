include .env

DC?=docker-compose
DM?=$(DC) exec -T db

up:
	$(DC) up -d --remove-orphans
down:
	$(DC) down

db-init:
	$(DM) /bin/sh -c "mariadb -u root -p${DATABASE_PASSWORD} -e 'DROP DATABASE IF EXISTS ${DATABASE_NAME};'"
	$(DM) /bin/sh -c "mariadb -u root -p${DATABASE_PASSWORD} -e 'CREATE DATABASE ${DATABASE_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"
	$(DC) cp sql/_init.sql db:/tmp/init.sql
	$(DM) /bin/sh -c 'mariadb -u root -p${DATABASE_PASSWORD} -D${DATABASE_NAME} < /tmp/init.sql'

db-data-init:
	$(DC) cp sql/data_init.sql db:/tmp/data_init.sql
	$(DM) /bin/sh -c 'mariadb -u root -p${DATABASE_PASSWORD} -D${DATABASE_NAME} < /tmp/data_init.sql'