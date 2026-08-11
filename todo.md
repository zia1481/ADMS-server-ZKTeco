# ADMS Multi-Company Implementation — Todo

## Current Status
Feature: multi-company (tenant) support for the Attendance Device Management System.
Implementation files (models, controllers, views, routes, jobs, middleware) are written but uncommitted.
Database was corrupted by an interrupted migration (000010) on the `attendances` table — data recovery was
abandoned by user decision; schema was rebuilt from scratch.

## Tasks

### Database recovery / rebuild — DONE
- [x] Fixed broken `attendances` table (error 1932: frm/ibd mismatch after interrupted ALTER)
  - [x] Dropped broken `attendances` table; removed orphaned `.ibd` file (stopped mysqld, deleted, restarted)
  - [x] Recreated `attendances` with original schema (2024_07_29_022209)
- [x] Ran pending migrations `2026_08_11_000010_modify_attendances_table` and `000011_modify_employees_table` via `php artisan migrate` (both ~2.5s, OK)
- [x] Seeded: `php artisan db:seed` (CompanySeeder: DEFAULT company id=1; UserSeeder: john.doe + john.koye)
- [x] Fixed user 2 (john.koye) to `company_admin` + company_id=1 (seeder `insertOrIgnore` didn't update existing row)
- [x] Verified all tables readable, no 1932 errors; all 23 migrations recorded

### Implementation fixes — DONE
- [x] Fixed `app/Helpers/CompanyHelper.php`: `current_company_id()` was defined inside `namespace App\Helpers`
      instead of the global namespace → `Call to undefined function ... current_company_id()` across 27 callers
      (controllers, models, blades). Rewrote file with bracketed namespaces so the function is global;
      re-ran `composer dump-autoload -o` and `php artisan view:clear`
- [x] Created `public/logo.svg` (layout referenced `/logo.svg`, caused 404s)
- [x] Fixed `iclockController@test`: inserted into `finger_log` without required `url` column → SQL 1364;
      now inserts `data` + `url` and returns "OK"

### Verification — DONE
- [x] `php artisan route:list` — 65 routes, no errors
- [x] All 15 web pages return HTTP 200 (login as super_admin; /, /devices, /employees, /companies,
      /departments, /areas, /shifts, /schedules, /users, /devices-pending, /attendance, /daily, /monthly,
      /finger-log, /devices-log)
- [x] API auth: POST /api/login works for super_admin (john.doe) and company_admin (john.koye,
      company object attached); GET /api/me with Bearer token works
- [x] Device endpoints: iclock/cdata handshake 200, iclock/getrequest 200, iclock/test 200 (GET)
- [x] PHP lint: 24/24 new/modified files pass
- [x] Log clean after fixes (no more `current_company_id` or finger_log errors)
- [x] Single mysqld instance (PID owns port 3306), no orphan `#sql*` files remain in datadir

### Cleanup / remaining
- [ ] Remove orphan/backup artifacts from `E:\xampp\mysql\data_backup_20260811\` and
      `C:\Users\ICP\AppData\Local\Temp\opencode\adms_data_backup\` once confident DB is stable
- [ ] Commit implementation when verified by user

## Notes
- `attendances` data (5477 rows) NOT recovered per user instruction — recreated empty (0 rows).
- MySQL: `E:\xampp\mysql\bin\mysqld.exe --defaults-file=E:\xampp\mysql\bin\my.ini`
- App URL: http://localhost:8080 (Apache, XAMPP)
- Logs use UTC timestamps (local is +5h)
