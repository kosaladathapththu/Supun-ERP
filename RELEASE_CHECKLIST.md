# Fuji Industries Release Checklist

## Automated gates

- All feature and accounting regression tests must pass.
- `php artisan migrate:status` must show every migration as run.
- The Control Center must show a live database connection.
- A checksum-verified backup must exist from the last 24 hours.
- Trial balance and balance-sheet reconciliation differences must be zero.
- Period close/reopen must require a different authorized reviewer.

## Required before production

- Upgrade XAMPP from PHP 8.0 and upgrade Laravel 9 to supported releases.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Replace the seeded administrator password and verify individual staff accounts.
- Configure HTTPS, trusted hosts, secure cookies and production mail delivery.
- Run a restore drill into an isolated database; never overwrite the live database for testing.
- Configure Windows Task Scheduler to run `php artisan schedule:run` every minute so the 23:30 backup schedule executes.
- Store an encrypted off-machine copy of verified backups and define retention.

## Restore policy

Restore is intentionally unavailable in the web interface. A restore must be performed by an authorized administrator into a newly created database, verified, and only then promoted under an approved maintenance window.
