# Cron Manager

A local web UI for managing Laravel scheduled tasks stored in a database. Instead of editing PHP files to add or remove cron jobs, you manage everything through a browser.

The system crontab runs one entry every minute:

```
* * * * * cd /path/to/cron-manager && php artisan schedule:run >> /dev/null 2>&1
```

This app provides the UI to control which tasks that scheduler actually runs.

## How it works

Tasks are stored in a `scheduled_tasks` database table. On each scheduler invocation, a service provider reads all enabled tasks from the database and registers them with Laravel's scheduler dynamically — no PHP changes required.

## Requirements

- PHP 8.4+
- Composer
- Node.js 20+ and npm
- SQLite (bundled with PHP on most systems)
- Write access to the system crontab (`crontab -e`)

## Installation

```bash
# Clone the repo
git clone https://github.com/your-username/cron-manager.git
cd cron-manager

# Install dependencies, set up .env, generate key, run migrations, build assets
composer setup
```

## Cron setup

Add one entry to your system crontab (`crontab -e`):

```
* * * * * cd /path/to/cron-manager && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/cron-manager` with the absolute path where you cloned the repo.

## Running locally

```bash
composer dev    # Starts server, queue, log tail, and Vite concurrently
```

Then open [http://localhost:8001](http://localhost:8001).

Or start just the web server:

```bash
php artisan serve
```

## Available commands

```bash
composer dev          # Start all dev services (server, queue, logs, vite)
composer test         # Run test suite
composer setup        # Fresh install: dependencies, .env, key, migrate, build

php artisan migrate   # Run pending migrations
php artisan tinker    # REPL

npm run dev           # Vite dev server (hot reload)
npm run build         # Build production assets

./vendor/bin/pint     # Fix code style (PSR-12)
```

## Stack

- Laravel 12 / PHP 8.4
- SQLite
- Tailwind CSS v4
- Vite

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT — see [LICENSE](LICENSE).
