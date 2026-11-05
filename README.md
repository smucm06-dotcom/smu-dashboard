# Clean SMU Dashboard Docker development environment

Uses Docker Desktop + VS Code on Windows. No separate PHP/Composer/MySQL/Node installation.

## BEFORE STARTING
If you already have SMU-Dashboard running, open terminal in OLD directory and run `docker compose down` (NOT `down -v`). Back up/rename your old SMU-Dashboard folder. Unzip this template as `C:\Projects\SMU-Dashboard` so `compose.yaml` is at that exact path. The `src` directory must be empty when installing Laravel. Old MySQL volume is NOT deleted by these steps. New database volume is named `smu_dashboard_mysql_fresh_v1`.

## Setup from PowerShell in C:\Projects\SMU-Dashboard

First copy `.env.example` to `.env` next to `compose.yaml` and set both passwords.

```powershell
docker compose config --quiet
docker compose build app
docker compose up -d db
# Verify that the database becomes healthy:
docker compose ps
# Fresh installation only, src must be empty:
docker compose run --rm --no-deps app composer create-project laravel/laravel . "^12.0"
```

Edit `src/.env` to:

```
APP_NAME="SMU Dashboard"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=smu_dashboard
DB_USERNAME=smu_dashboard
DB_PASSWORD=same_as_MYSQL_PASSWORD
ADMIN_PASSWORD=choose_an_admin_password
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Then execute:

```powershell
docker compose up -d --build
docker compose exec app php artisan config:clear
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose run --rm node install
docker compose run --rm node run build
docker compose restart app web
docker compose ps
```

Laravel: http://localhost:8000
phpMyAdmin: http://localhost:8080
phpMyAdmin username: `smu_dashboard`, password: the `MYSQL_PASSWORD` value in `.env`.
Database: `smu_dashboard`.

Verify:

```powershell
docker compose exec app php artisan --version
docker compose exec app php artisan migrate:status
docker compose exec db mysql -usmu_dashboard -p smu_dashboard
```

The MySQL password is the `MYSQL_PASSWORD` value in `.env`. If Windows-mounted directory permissions cause Laravel errors, inspect writable directories `storage` and `bootstrap/cache` before changing settings. These passwords are DEV ONLY. Do not publish ports or expose phpMyAdmin on a production server. Never use `docker compose down -v` unless you intend to delete database volumes.
