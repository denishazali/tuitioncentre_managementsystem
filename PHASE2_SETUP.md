# Phase 2 database setup

The default database connection must target `bijak_tuition_laravel`. The
legacy connection must target the original `bijak_tuition` database.

Add this to `.env`:

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=bijak_tuition
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=
```

Confirm that `DB_DATABASE=bijak_tuition_laravel`, then run:

```powershell
php artisan optimize:clear
php artisan migrate:fresh
php artisan legacy:import
php artisan legacy:verify
```

`migrate:fresh` deletes tables from the database selected by `DB_DATABASE`, so
verify the database name before running it. It does not modify `bijak_tuition`
when the `.env` values above are correct.

The importer preserves legacy primary keys where practical and converts each
legacy payment into an enrollment plus a payment. It can be rerun using
`php artisan legacy:import --force`.

The legacy `grades` rows are not automatically imported because that table has
neither a subject ID nor a numeric score. The richer `student_exam` records are
imported into `exam_results`.
