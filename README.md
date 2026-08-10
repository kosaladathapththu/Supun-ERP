# Supun Group ERP

Local electronics retail/wholesale ERP using Laravel, MariaDB, Blade and Bootstrap. Implemented modules include master data/imports, purchase orders and GRN, weighted-average inventory, sales/POS, customer receipts, allocations, advances, customer ledgers and receivables aging.

## Current environment

- Project: `D:\Local Repository\Supun-ERP`
- Detected XAMPP: `D:\Software\Xammp`
- PHP 8.0.30, MariaDB 10.4.32
- Laravel 9.52 (compatibility choice)

> Security: PHP 8.0 and Laravel 9 are end-of-life, and the locked compatibility dependencies have reported advisories. Do not expose this build to a network or use it for live data. Upgrade XAMPP/PHP and Laravel before production.

## XAMPP installation

1. Start Apache and MySQL from the XAMPP control panel.
2. Create a UTF-8 database: `CREATE DATABASE supun_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`.
3. Copy `.env.example` to `.env` and set `APP_NAME="Supun Group ERP"`, `APP_URL=http://localhost/Supun-ERP/public`, database name `supun_erp`, user and password.
4. Run Composer using XAMPP PHP (commands below), generate the key, then migrate/seed.
5. Either place/copy the project under XAMPP `htdocs`, create a directory junction, or configure an Apache virtual host whose `DocumentRoot` is this project's `public` folder. Enable `mod_rewrite` and allow overrides for the public directory.

Example virtual host:

```apache
<VirtualHost *:80>
    ServerName supun-erp.local
    DocumentRoot "D:/Local Repository/Supun-ERP/public"
    <Directory "D:/Local Repository/Supun-ERP/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add `127.0.0.1 supun-erp.local` to the Windows hosts file as Administrator, then restart Apache.

### This computer's port 3306 conflict

The Windows service `MySQL80` currently owns port 3306, so XAMPP MariaDB cannot use its default port. Before running MariaDB migrations, choose one configuration: stop/disable `MySQL80` and start XAMPP MariaDB on 3306, or configure XAMPP MariaDB for another port (for example 3307) and set `DB_PORT=3307` in `.env`. Do not run two servers on the same port. The foundation migrations were verified against an isolated SQLite database because the existing MySQL 8 root account uses `caching_sha2_password`, unsupported by the old XAMPP MariaDB client.

## Commands

```powershell
& 'D:\Software\Xammp\php\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install
& 'D:\Software\Xammp\php\php.exe' artisan key:generate
& 'D:\Software\Xammp\php\php.exe' artisan migrate --seed
& 'D:\Software\Xammp\php\php.exe' artisan test
```

Development login: `admin@supun-erp.local` / `ChangeMe!2026`. Change it immediately; it is a temporary seed password.

## Backups

Until the in-app backup module is built, schedule `mysqldump` daily to a local backup directory and copy a daily/weekly encrypted backup to another device or approved off-PC destination. Test restoration regularly; never rely on one disk.

See [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md), [DATABASE_DESIGN.md](DATABASE_DESIGN.md), and [MODULES.md](MODULES.md).
