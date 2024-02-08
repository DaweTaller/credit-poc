# How to run

Feel free can edit constants of inserted data in `.env`.

1. Setup your `DEV_IP` in `.env`
1. `make up`
1. `make init`
   - To re-initializate only data use `make data-init`
1. Connect to db 
   - Adminer is available on your `DEV_IP` on port `8080`
   - Database user is `root` with passowrd in `.env` in `DATABASE_PASSWORD` variable
   - Or you can use any database manager, database runs on port `3306`