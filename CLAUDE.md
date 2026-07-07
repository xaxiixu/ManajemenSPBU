# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel 12 / PHP 8.2 app for managing a single SPBU (Indonesian gas station): attendance (presensi/absensi), fuel sales (penjualan BBM), expenses (pengeluaran), and a small double-entry accounting module (chart of accounts, jurnal umum, buku besar, laporan laba rugi). Server-rendered Blade views, no SPA framework — Tailwind v4 + Vite for assets.

## Commands

```
composer run dev      # serve + queue listener + pail logs + vite, concurrently (primary way to run locally)
php artisan serve     # app server only
npm run dev           # vite only
composer test         # clears config cache, then runs the test suite
php artisan test                              # run all tests
php artisan test --filter=TestName            # run a single test
php artisan test tests/Feature/SomeTest.php   # run one file
vendor/bin/pint       # format PHP (Laravel Pint)
```

Tests run against an in-memory sqlite DB and array-based session/cache/queue drivers (see `phpunit.xml`), independent of whatever `DB_CONNECTION` is set in `.env`.

## Critical gotcha: schema is incomplete

`database/migrations/` only contains Laravel's stock tables (`users`, `cache`, `jobs`). There are **no migrations for the domain tables** — `coa`, `jurnal_umum`, `jurnal_detail`, `penjualan_bbm`, `pengeluarans`, `petugas`, `absensis` — even though the Eloquent models below fully reference them. `database/database.sqlite` also only has the stock tables. Before any feature touching these models can actually run against a real DB, migrations need to be authored for them, matching the `$fillable`/`$casts` already defined on each model. Don't assume the schema exists just because a model and controller do.

Table name notes when writing migrations: `Petugas` → `petugas`, `Absensis` → `absensis` (both singular-looking, no trailing `s` added by convention), `Pengeluaran` → `pengeluarans` (pluralized), `Coa`/`JurnalUmum`/`JurnalDetail`/`PenjualanBbm` → `coa`/`jurnal_umum`/`jurnal_detail`/`penjualan_bbm`.

`.env` currently points `DB_CONNECTION` at `mysql` (db `spbu`); that's separate from the sqlite file sitting in `database/`.

## Auth & roles

Auth is hand-rolled in `AuthController` (session-based via `Auth::attempt`), not Breeze/Fortify/Jetstream. Login also checks a `users.is_active` flag and logs the user back out if disabled.

Authorization is a custom `role` string column on `User` (`app/Models/User.php`), not Spatie permissions or Gates/Policies. Three roles: `it`, `manager`, `pengawas`.

- `App\Http\Middleware\RoleMiddleware` is aliased as `role` in `bootstrap/app.php`. Usage in routes: `Route::middleware('role:pengawas,manager')`.
- **`it` always bypasses the role check** — it's hardcoded in `RoleMiddleware::handle()` to pass through regardless of the roles listed on the route.
- Some controllers (e.g. `CoaController`) additionally self-check `auth()->user()->role !== 'it'` inline (`authorizeIT()`) for actions not covered by route middleware — there's no consistent Policy/Gate layer, so when adding a new mutating action, check both the route group's `role:` middleware *and* whether the controller expects an additional inline check.
- Route structure in `routes/web.php` groups endpoints by who can do what (operational input vs. read-only vs. accounting vs. IT-only settings) — read that file first when adding a route to place it in the right access tier.

## Accounting flow

`App\Services\JurnalService` is where double-entry bookkeeping happens — it is called explicitly from controllers after a `PenjualanBbm` or `Pengeluaran` record is created (see `PenjualanBbmController::store`), it is not a model event/observer. Each call creates one `JurnalUmum` header plus two `JurnalDetail` lines (one debit, one credit) referencing `Coa` accounts.

- The cash account is hardcoded by `kode_akun` lookup: `1-1100`.
- Revenue account per fuel type is resolved via `PenjualanBbm::coaByJenisBbm()`, which maps `Pertalite/Pertamax/Solar` → COA codes `4-1100/4-1200/4-1300`. If these seed COA rows don't exist, journal creation silently no-ops (`if (!$akunKas || !$akunPendapatan) return;`) rather than throwing.
- `JurnalUmum::generateNomor()` derives sequential journal numbers per day (`JRN-YYYYMMDD-####`) by parsing the max existing number for that date — not an autoincrement column.
- `PenjualanBbm` computes `liter_terjual`/`total_penjualan` itself in a `saving` model event (meter_akhir − meter_awal, × harga_per_liter) and throws if `meter_akhir <= meter_awal` — don't pass precomputed totals expecting them to stick, and expect an exception (not a validation error) on bad meter values from any path that bypasses form validation.

## File uploads

`penjualan-bbm` requires two meter-photo uploads (`foto_meter_awal`/`foto_meter_akhir`), stored on the `public` disk under `penjualan/meter`. `PenjualanBbmController::destroy` deletes both files from storage before deleting the DB row — follow that pattern for any other model that stores file paths, to avoid orphaned files in `storage/app/public`.
