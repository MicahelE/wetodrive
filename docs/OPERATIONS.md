# Operations

Operational runbook for the production WeToDrive deployment.

## Production environment

- Host: Azure VM, Apache + PHP-FPM, `ssh mikeserver@13.88.178.122`
- App path: `/var/www/wetodrive.com`
- Database: **MySQL** (the repo's `.env.example` default is SQLite; production is not)
- Deploy: push to `main` → GitHub Actions ("Deploy to Production") runs migrations
- Prod config is cached — run `php artisan config:cache` after any `.env` change

## Database backups

Backups live on the server at `/var/www/wetodrive.com/storage/backups/` as timestamped
`db-backup-YYYYMMDD-HHMMSS.sql.gz` files (full `mysqldump`, all tables, gzipped).

- **Current snapshot:** a manual pre-change backup was taken on **2026-07-16** before purging
  phantom renewal transactions — `storage/backups/db-backup-20260716-155025.sql.gz`. It is a
  one-off point-in-time dump, **not** an automated/recurring backup. Do not rely on it as a live
  backup strategy.
- These dumps contain **full customer data**. Keep them access-controlled; pull down and delete
  old ones rather than letting them accumulate in `storage/`.

### Take a fresh dump (on the server)

```bash
cd /var/www/wetodrive.com
mysqldump --single-transaction --quick "$DB_DATABASE" \
  | gzip > "storage/backups/db-backup-$(date -u +%Y%m%d-%H%M%S).sql.gz"
```

Credentials come from the prod `.env` `DB_*` keys (`DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`). The `mysqldump: ... PROCESS privilege ... tablespaces` line is a
harmless warning — table data still dumps.

### Restore

```bash
gunzip -c <file>.sql.gz | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p "$DB_DATABASE"
```

## Maintenance commands

- `php artisan polar:reconcile` — resync Polar subscriptions (safety net for missed webhooks;
  scheduled daily at 08:55)
- `php artisan polar:purge-phantom-renewals --dry-run` — list (or, without `--dry-run`, delete)
  duplicate renewal transactions recorded at subscription-creation time. Use `--window=<seconds>`
  to widen the creation-time detection window (default 120s; the 2026-07-16 cleanup used 86400).
