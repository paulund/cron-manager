# Cron Manager

A local web UI for managing Laravel scheduled tasks stored in a database. Instead of editing PHP files to add or remove cron jobs, you manage everything through a browser.

This app provides the UI to control which tasks the scheduler actually runs. A background trigger fires `php artisan schedule:run` every minute — the recommended method depends on your OS.

## How it works

Tasks are stored in a `scheduled_tasks` database table. On each scheduler invocation, a service provider reads all enabled tasks from the database and registers them with Laravel's scheduler dynamically — no PHP changes required.

## Requirements

- PHP 8.4+
- Composer
- Node.js 20+ and npm
- SQLite (bundled with PHP on most systems)

## Installation

```bash
# Clone the repo
git clone https://github.com/your-username/cron-manager.git
cd cron-manager

# Install dependencies, set up .env, generate key, run migrations, build assets
composer setup
```

## Scheduler setup

Run the install command and it will detect your OS and configure the right trigger automatically:

```bash
php artisan scheduler:install
```

| Platform | Method | Notes |
|---|---|---|
| **macOS** | LaunchAgent (`~/Library/LaunchAgents/`) | Runs in your login session with full Keychain access. Required for tools like `claude` that store credentials in the Keychain. |
| **Linux** | crontab entry | Appended to your user crontab automatically. |
| **Windows** | Windows Task Scheduler | Created via `schtasks`. |

To remove the trigger:

```bash
php artisan scheduler:uninstall
```

The **Scheduler Health** page (`/health`) shows whether the trigger is installed and whether the scheduler is actively running.

### macOS: manual crontab + LaunchAgent

If you previously added a crontab entry by hand, remove it after installing the LaunchAgent — otherwise `schedule:run` fires twice per minute. The health page will warn you if both are active.

### Linux: PATH issue

Cron runs with a minimal environment and does not inherit your shell's PATH. If a task fails with `sh: <command>: command not found`, add a `PATH` line at the top of your crontab (`crontab -e`):

```
PATH=/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin
```

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

php artisan migrate            # Run pending migrations
php artisan tinker             # REPL
php artisan scheduler:install  # Install the platform scheduler trigger
php artisan scheduler:uninstall # Remove the platform scheduler trigger

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
