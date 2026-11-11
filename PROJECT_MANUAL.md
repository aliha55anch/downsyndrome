# Little Steps Complete Project Manual

**Project:** Down Syndrome Support Platform  
**Stack:** Laravel 12, PHP 8.2+, MySQL, Blade, HTML/CSS/JavaScript  
**Last verification:** 11 November 2026

This standalone guide is the consolidated beginner-friendly reference for setting up, understanding, operating, testing, and handing over Little Steps. Module-specific notes have been consolidated here so the project source tree stays focused on application code.

## Current handover status

- **Handover:** Ready for review with the limitations in this manual.
- **Git push:** Not ready yet. The working tree has substantial modified and untracked project work; the owner must classify and stage only intended files and review that staged snapshot. Nothing was staged, committed, or pushed.
- **Deployment:** Not production-ready yet. Provider credentials and real delivery checks, operational services, full responsive/accessibility checks, backup, and deployment hardening remain.
- **Test evidence:** `php artisan test` completed with 60 tests and 559 assertions passing in 10.71 seconds. This is local automated evidence, not proof of external provider operation.
- **Route inventory:** `php artisan route:list --except-vendor` returned 178 routes.
- **Dependency check:** `composer validate --no-check-publish` passed with a missing-license warning; `composer audit --locked --no-interaction` reported no known PHP dependency advisories. The npm advisory endpoint was not checked.
- **Payment/email safety:** No live payment or external email was sent during verification. Safepay production remains disabled by default.

## 1. What the application does

Little Steps supports parents and children through learning activities and progress tracking, and provides a workflow for professional support through verified doctors and appointments. The existing public website is kept under `public/`; Laravel handles dynamic pages, access control, validation, database operations, and private files.

### Roles

| Role | Main capabilities |
| --- | --- |
| Guest | Browse public pages, activities, and published doctor profiles; create a parent account; submit a contact message. |
| Parent | Manage a profile and children, enroll in activities, track progress, browse doctors, book appointments, and view notifications and history. |
| Pending doctor | Register, verify email, submit professional profile and private documents, and wait for administrator review. |
| Approved doctor | Maintain a public profile and availability, handle permitted appointments, see patient/consultation details allowed by the workflow, and view revenue summaries. |
| Administrator | Manage users, doctors and verification, activities, messages, appointments, and send audited in-app notifications. |

The database stores roles as `parent`, `pending_doctor`, `approved_doctor`, or `admin`. Public registration creates a parent. Doctor access requires the separate verification process; changing an ordinary user role is not a substitute for doctor verification.

## 2. Requirements and first local setup

Install PHP 8.2 or newer with the Laravel-required extensions (including PDO for MySQL, OpenSSL, Mbstring, XML, Ctype, JSON, and Fileinfo), Composer 2, MySQL, Node.js, pnpm, Git, and a code editor. The PHP CLI and web server must use compatible PHP installations.

In PowerShell, open the project folder:

```powershell
cd 'D:\Down Syndrome'
php -v
composer --version
node --version
pnpm --version
```

Install dependencies and prepare the local environment:

```powershell
composer install
Copy-Item .env.example .env
```

Node packages are only needed for the optional bundled PowerPoint viewer build. The Laravel pages do not use a general Vite build in this project. If you need to rebuild that viewer, install the locked Node dependencies with `pnpm install --frozen-lockfile`, then run `pnpm run build:pptx-viewer` and review the generated files before staging them.

Only copy `.env.example` if `.env` does not already exist. Edit `.env` locally with the database name, username, and password for your local MySQL. Keep the file private. For a fresh install only, generate the encryption key if `APP_KEY` is empty:

```powershell
php artisan key:generate
```

Create the database in MySQL, then apply additive schema migrations and start the local server:

```powershell
php artisan migrate
php artisan serve
```

Open `http://127.0.0.1:8000`. Never run `migrate:fresh`, `db:wipe`, or database-reset commands against a database that contains data. Never regenerate an existing application's key casually; doing so can invalidate encrypted data and sessions.

### Create the first local administrator

Set private `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` values in `.env`, then run:

```powershell
php artisan db:seed --class=AdminUserSeeder
```

The seeder is intended to be repeatable. Do not put admin credentials into source files, screenshots, chat, or Git.

## 3. Project map

| Path | Purpose |
| --- | --- |
| `app/Http/Controllers/` | Request handlers grouped by authentication, parent, doctor, and admin workflows. |
| `app/Models/` | Database records and relationships. |
| `app/Policies/`, `app/Http/Middleware/` | Record ownership and role/access enforcement. |
| `app/Services/`, `app/Contracts/`, `app/Jobs/` | Availability, booking, payment, notification delivery, and background work. |
| `routes/web.php` | Web routes and middleware grouping. |
| `routes/console.php` | Scheduled appointment expiry, reminder, and email-outbox commands. |
| `bootstrap/app.php` | Laravel middleware and exception setup. |
| `config/` | Laravel, database, mail, storage, auth, and provider settings. |
| `database/migrations/` | Additive database schema history. |
| `database/seeders/` | Administrator and starter data setup. |
| `resources/views/` | Laravel Blade pages, shared layouts, and email templates. |
| `public/` | Static HTML, CSS, JavaScript, and public assets. `assets/css/style.css` holds the established styles; `assets/css/interface.css` adds shared layout, navigation, form, and responsive refinements for every page. |
| `storage/app/private/` | Private uploaded material; must not be committed or served as a public directory. |
| `tests/Feature/` | Automated HTTP and workflow regression tests. |
| `google-apps-script/` | Optional Apps Script integration source and setup material. |

## 4. Modules and implementation map

1. **Foundation:** Laravel project, MySQL configuration, static public site, environment template, and logging.
2. **Accounts:** Parent registration, login/logout, email verification, password reset/change, account settings, and throttling.
3. **Roles and administration:** Protected admin area, user management, and contact-message workflow.
4. **Learning activities:** Activity and content management, publication states, private media, and public activity pages.
5. **Children and progress:** Multiple child records, ownership checks, activity enrollment, and progress history/summaries.
6. **Doctor registration and verification:** Pending onboarding, private document uploads, admin review, approval/rejection/suspension, and audit history.
7. **Doctor profiles and availability:** Public profile, consultation options, availability periods, breaks, holidays, leave, and publication.
8. **Appointments and payments:** Slot selection, appointment states, payment transaction records, Safepay checkout/webhook handling, cancellations/rescheduling, reminders, and email outbox.
9. **Notifications and operational workflows:** Database notifications, scheduled jobs, outbox processing, meeting-link fields, and status events.
10. **Final experience refinement:** Parent and doctor dashboards, appointment tabs and filters, slot preview, notification bell, and audited administrator notification sending.

The workflows below describe the current code and local evidence. A feature's presence in code does not mean its external account is configured or tested.

### Source tree at a glance

```text
app/                 Laravel controllers, models, policies, services, jobs and mail
bootstrap/           Laravel application bootstrapping and cached configuration
config/              Application and integration configuration
database/            Migrations, seeders and factories
google-apps-script/  Optional Apps Script email adapter source and instructions
public/              Entry point plus static public pages, CSS, JavaScript and images
  assets/css/        Base styles and shared interface system
resources/views/     Blade layouts and role-specific pages
routes/              Web and scheduled-command definitions
scripts/             Small build/support scripts
storage/             Logs, runtime cache, private uploads and generated files
tests/Feature/       Automated HTTP, authorization and workflow tests
vendor/              Composer-installed packages (generated; do not commit)
node_modules/        Node development packages (generated; do not commit)
```

Laravel requests generally flow from `routes/web.php` through middleware and a controller, then model/service/policy logic, and finally a Blade response or redirect. Keep authorization and validation on the server even when a page hides an action. The three authenticated workspaces share the same full-height sidebar structure on desktop and use an expandable menu on smaller screens. `public/` is web accessible; never store private uploads or credentials there.

## 5. Environment variables

`.env.example` contains safe local defaults and blank provider credentials. `.env` is local/private and is excluded by `.gitignore`.

| Variable group | Purpose and safe guidance |
| --- | --- |
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Application identity, environment, encryption key, error detail, and base URL. Use `APP_DEBUG=false` and HTTPS for production. Keep `APP_KEY` private. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection. Use a dedicated least-privilege database user in production. |
| `SESSION_*` | Session storage and cookie settings. Production must use HTTPS and secure cookies. |
| `MAIL_*` | Laravel account mail. The template defaults to `MAIL_MAILER=log`, which writes messages to logs rather than delivering them. Configure a real supported SMTP/mail transport before relying on email. |
| `QUEUE_CONNECTION` | Background job driver. The example uses the database queue; run a worker in deployments that need queued delivery. |
| `GOOGLE_APPS_SCRIPT_WEB_APP_URL`, `GOOGLE_APPS_SCRIPT_SHARED_SECRET` | Optional signed appointment email-outbox adapter. Keep the shared secret private. Without both settings, messages remain pending. |
| `SAFEPAY_ENV`, `SAFEPAY_PRODUCTION_ENABLED`, `SAFEPAY_API_KEY`, `SAFEPAY_SECRET_KEY`, `SAFEPAY_WEBHOOK_SECRET` | Safepay configuration. Start with sandbox, keep production disabled, and never paste credentials into source or documentation. |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | First administrator seeding. Use local private values and clear or protect them according to the deployment process after seeding. |
| `APP_CURRENCY`, `MEETING_PROVIDER`, `APPOINTMENT_SELECTION_HOLD_MINUTES`, `SAFEPAY_HOLD_MINUTES` | Currency display and appointment workflow settings. Validate choices and timeouts before deployment. |

Do not share `.env` files. Rotate any credential that is accidentally exposed. Do not put real secrets into `.env.example`.

## 6. Database and data safety

Migrations create tables and add schema changes. Before deploying a migration to a database with real data, take a verified backup and inspect the migration. Use `php artisan migrate:status` to see what has run and `php artisan migrate` to apply pending migrations. Do not use destructive reset commands on the real database.

Private files live on Laravel's local private disk rooted at `storage/app/private`. Controllers check authorization and stream protected files. Back up private uploads with the database and restrict access to the backup. Do not place licenses, identity documents, or private patient data under `public/`.

### Backup and restore rehearsal

Use the hosting provider's supported backup tool or a dedicated least-privilege database account. For a local MySQL installation where `mysqldump` is on `PATH`, create a dated dump without putting a password in the command line:

```powershell
$backupPath = Join-Path $PWD 'database-backup-2026-11-11.sql'
mysqldump --host=127.0.0.1 --user=YOUR_DB_USER --password --single-transaction YOUR_DB_NAME > $backupPath
Get-Item $backupPath | Select-Object Name, Length, LastWriteTime
```

MySQL prompts for the password. Replace the example user/database values locally; never paste secrets into chat or a script. Back up private uploads separately using a protected destination. Test restore only into a separate empty disposable database, then confirm the application can read expected records and authorized files. Do not restore over production as a test.

## 7. Booking, appointment, and payment behavior

The public directory should show approved, published doctor profiles. A parent chooses a consultation option and an actual backend-generated slot. The server checks eligibility and availability again during booking, and slot locks/holds are used to reduce double booking. Do not trust client-provided time values or a browser return page as proof of payment.

Appointment status and payment status are separate records. The Safepay webhook is the authoritative payment confirmation path; webhook signatures and idempotency are tested with mocked requests. If payment confirmation arrives after a slot hold expires, the appointment may require administrator review. Cancellation is not the same as a refund; refund records and appointment status must be reviewed together.

**Payment verification completed locally:** the automated suite exercises payment initialization, failure handling, signature validation, duplicate webhook behavior, checkout environment parameters, and state updates using fakes/mocks. Checkout returns now use a payment-specific page outside the dashboard shell, and the patient can refresh the auth token for the same pending tracker if the provider checkout was cancelled or failed to load. A local browser path was tested only through slot review/temporary hold. No live charge, real Safepay sandbox checkout, or reachable external webhook was performed. Before production, the owner must configure sandbox credentials and a public HTTPS webhook, run a controlled sandbox purchase, verify the webhook and refund/reconciliation path, and keep production disabled until separately approved.

### Doctor registration and review

1. A prospective professional uses `/register/doctor` and submits account details, license and qualification documents, and any required professional information. Public registration remains a parent registration flow; changing a submitted role cannot grant doctor access.
2. Verification documents are limited by server validation (PDF/JPG/PNG; up to 10 MB per file) and saved under the private local disk at `storage/app/private/doctor-verification`. The folder must be writable by PHP and outside the web server's public document root. Do not run `storage:link` for private documents.
3. The professional verifies their email and reviews the application at `/doctor`. An administrator reviews the files at `/admin/doctors`, approves the current required documents, and then approves the application. Rejection and suspension require a reason; changes to critical information can reopen review.
4. Only after approval can an administrator publish a professional profile. Profile publication and verification approval are separate controls. A profile appears publicly only while it is approved, active, and published.

For a public host, set PHP upload limits to at least `upload_max_filesize=10M` and `post_max_size=24M`, configure HTTPS and the correct `APP_URL`, and back up private storage. Locally, `MAIL_MAILER=log` means verification messages are written to `storage/logs/laravel.log`; use the latest local link for testing.

### Doctor profile and availability setup

An approved doctor edits their professional profile, timezone, language, qualifications, photo, consultation types, durations, and optional fees. Fees are displayed information and are not taken during Module 7 profile setup. They then create weekly work periods and breaks, and add holidays, leave, or unavailable periods. The backend rejects overlaps and breaks outside a working period. Times are displayed in the doctor's IANA timezone and stored as UTC instants.

An administrator manages specialty choices at `/admin/doctor-specialties` and reviews/publishes the public profile after checking what families will see. A profile photo uses private storage; public image routes check current publication eligibility. If no doctors appear in `/doctors`, confirm each doctor is approved and the administrator has published the profile.

## 8. Email and other integrations

There are two distinct email paths:

- **Laravel account mail** (such as verification/reset): defaults to the `log` mailer. In this mode the application does not deliver to an inbox; inspect the local log only for development. Configure and verify a real mail transport before launch.
- **Appointment email outbox:** messages are recorded idempotently. If both Google Apps Script URL and shared secret are configured, scheduled dispatch queues signed requests for the script. A queue worker must run. If configuration is missing, outbox records remain pending.

The repository does not include a Resend transport or Bravo integration. Do not assume those services work because older prompts or account settings mention them. No actual external message was sent in this verification. Google Apps Script deployment, URL, secret, sender permissions, and inbox delivery still require owner-side configuration and a controlled test.

Safepay sandbox and production status are described above. Meeting provider default is `manual`; real video meeting creation is not automatically guaranteed. Check the service account and provider configuration before promising a meeting link.

### Safepay sandbox configuration

1. Obtain sandbox credentials from the Safepay merchant account. Set `SAFEPAY_ENV=sandbox`, `SAFEPAY_API_KEY`, `SAFEPAY_SECRET_KEY`, and `SAFEPAY_WEBHOOK_SECRET` in a private `.env` or deployment secret manager. Keep `SAFEPAY_PRODUCTION_ENABLED=false`.
2. Register the webhook URL as `https://YOUR_DOMAIN/webhooks/safepay` in the Safepay sandbox settings and subscribe to payment success/failure events. Safepay must be able to reach that URL over HTTPS; local-only `127.0.0.1` is not reachable from the provider. Use a trusted temporary HTTPS tunnel for sandbox work only and remove it afterwards.
3. The app sends sandbox environment information to hosted checkout and verifies the webhook HMAC-SHA512 signature over the raw request body. Do not disable signature checks. After changing environment settings, run `php artisan config:clear` in the local environment.
4. Use only a payment method documented by Safepay for its sandbox. Check current test-card values and expiry instructions in Safepay's official test-card documentation; do not substitute real card details. Confirm a booking only after the signed callback has been processed, then inspect the appointment/payment history and Safepay event log. A browser success/return page alone is never proof of payment.
5. Exercise failed, cancelled, duplicate, mismatched amount/currency, expired, and late callback cases. Replayed signed callbacks must not create duplicate payment effects. Verify refund handling and reconciliation separately.

No Safepay account settings were changed and no real or sandbox transaction was processed during this handover. Do not enable production payment until sandbox checkout, webhook, expiry, cancellation, refund, and reconciliation checks are complete and the owner has explicitly approved live mode.

### Google Apps Script appointment email path

Appointment email records are created in an idempotent outbox. The optional adapter in `google-apps-script/Code.gs` can deliver those messages when `GOOGLE_APPS_SCRIPT_WEB_APP_URL` and `GOOGLE_APPS_SCRIPT_SHARED_SECRET` are both set. Deploy the script as a web app under the account and access policy chosen by the owner; configure the deployment URL and a strong shared secret in private server configuration. The request is signed with HMAC-SHA256. Never place a real secret in the source tree or chat.

Run a persistent worker with `php artisan queue:work --tries=4` and ensure the Laravel scheduler is active. For local development use `php artisan schedule:work`. On production Linux the scheduler is typically called every minute with `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`; use the host's scheduler on other platforms. Scheduled tasks release expired holds, queue appointment reminders, and dispatch pending outbox messages. Check failed jobs, outbox records, protected application logs, and the Apps Script execution log.

There is no Bravo or Resend integration configured in this project. Laravel account verification and password-reset email use the `MAIL_*` mailer, which defaults to `log` in `.env.example`; this does not deliver to an inbox. Choose and configure a supported mail transport privately before relying on those messages. Test to an authorized mailbox and verify intended patient and doctor recipients without using real patient data.

## 9. Automated testing and manual checks

Run the automated feature suite:

```powershell
php artisan test --compact
```

The latest run passed 60 tests and 559 assertions in 10.71 seconds. PHPUnit uses an in-memory SQLite database, an array mailer, synchronous queue, and synthetic test data. It does not modify the normal application database, prove real provider credentials, or prove production deployment behavior.

The suite covers doctor verification/availability, appointment booking and care workflows, payment/webhook behavior with mocked HTTP, email signing/outbox behavior with fakes, and patient/admin regressions. Coverage is not a guarantee that every UI path or authorization edge has a test.

Additional checks completed in this audit:

- `php artisan route:list --except-vendor`: 178 routes.
- `php artisan view:cache`: passed in the earlier Module 10 verification.
- Targeted PHP syntax checks for changed route/controller files: passed in the earlier Module 10 verification.
- `composer validate --no-check-publish`: passed with the optional missing `license` metadata warning.
- `composer audit --locked --no-interaction`: no known advisories were reported for locked PHP dependencies.
- `git diff --check`: passed; Git emitted line-ending normalization warnings for existing mixed-line-ending files.
- A local browser smoke on 11 November 2026 opened the public homepage and confirmed the navigation, appointment/professional-consultancy switcher, hero illustration, and public footer. This was a visual smoke check only, not a full responsive or authenticated-workflow review.
- Earlier Module 10 browser smoke used a separate temporary SQLite database and synthetic accounts to check patient navigation/dashboard/bell, doctor appointments/availability, and admin dashboard/composer. Booking reached the review/hold page without confirmation or charge. Those authenticated flows were not repeated this final turn.

For a manual release rehearsal, use a disposable database and sandbox accounts. Test guest and parent login/logout, invalid login, password reset/verification in the configured mail mode, parent ownership boundaries, doctor verification and private documents, availability changes, slot conflict/expiry, admin permission boundaries, notification send review, and all payment states. Do not use real patient data or live payment credentials for a local test.

### Test technique guide

| Technique | Safe use for this application | Evidence in this handover |
| --- | --- | --- |
| Black-box and boundary-value | Submit valid/invalid forms, empty/malformed fields, dates at schedule limits, and expired holds. | Booking, verification, availability and validation feature tests cover selected cases. |
| Unit and white-box | Exercise service rules and branches directly; inspect role and ownership enforcement. | The checked-in suite is under `tests/Feature`; do not describe it as a separate unit suite. |
| Integration and API | Verify routes, database transitions, signed webhook handling, jobs and email adapters with fakes. | Covered in feature tests with in-memory SQLite, mocked HTTP and array mail. |
| State-transition and decision-table | Try pending/paid/failed/refunded states, duplicate callbacks, cancellation, expiry and permissions. | Appointment/payment state workflows and signed webhook idempotency are covered in tests. |
| System, regression and smoke | Run the whole suite and load public/role workflows in a browser using synthetic accounts. | Full suite passed; this turn visually checked the desktop homepage only. |
| Exploratory and safe monkey | Navigate unusual but harmless sequences and use malformed inputs on a disposable test database. | No dedicated random-input or full exploratory session was performed. |
| Load, stress, spike and volume | Run only against an isolated seeded environment with synthetic data and agreed limits. | Not run; no load generator/isolated production-like environment was available. |

Re-run current automated checks in PowerShell:

```powershell
php artisan test
php artisan route:list --except-vendor
composer validate --no-check-publish
composer audit --locked --no-interaction
git diff --check
```

The package audit needs network access to Packagist. `git diff --check` can report line-ending notices separately from whitespace errors.

## 10. Performance, responsive design, accessibility, reliability

No page-level load timings, browser Network/Performance profile, Lighthouse score, load test, production database profile, or production asset timing is claimed in this handover. The measured local automated test-suite duration was 10.71 seconds, and the route inventory was 178 routes; neither is a page-speed metric. A future profile should use a seeded disposable database, record cold and repeat page/API response times, inspect query counts and network assets, and state machine/browser/viewport details.

Earlier browser checks verified the booking experience at 390px, 768px, and 1366px. Current changes to all patient/doctor/admin views have not been comprehensively rechecked at each width. The final-turn visual check covered the homepage at the current desktop browser viewport only. Keyboard-only navigation, screen-reader semantics, contrast audits, and a full browser matrix remain to be completed before claiming accessibility or compatibility sign-off.

Reliability work includes database-backed jobs/outbox, idempotency keys, webhook event handling, slot-hold expiry, reminder scheduling, and audit records. Production still needs monitored queue workers, a scheduler invocation at least once per minute, backups with restore rehearsal, error monitoring, log rotation, and incident ownership.

## 11. Security and privacy summary

Reviewed configuration and application patterns include role middleware, ownership policies/authorization, form request validation, login/registration throttles, private local storage, signed payment webhooks, and production-payment-off-by-default configuration. Automated regression tests cover selected access and workflow boundaries.

The scoped audit identified no confirmed critical or high-severity issue. One medium privacy/Git risk was fixed: private uploads were not excluded from Git, so an accidental broad stage could include them. One low-severity privacy issue was fixed: the sample mail sender used a personal address; it now uses a neutral example address. Composer's advisory service reported no known issues for the locked PHP packages. The Node/npm advisory database was not checked. These findings do not amount to a penetration test or a claim that the application has no vulnerabilities.

A full penetration test, external vulnerability scan, database least-privilege review, and account-level provider review were not performed. Composer's package advisory audit completed with no known PHP dependency advisories; the npm advisory database was not checked. The working tree includes substantial changes and local artifacts; `.gitignore` excludes private upload storage and local secrets, but review the final Git status and every intended file before staging. Never stage private uploads or `.env`.

Security checklist before deployment:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, and secure session cookies.
2. Use private credentials and least-privilege service/database accounts.
3. Configure private storage, backups, and retention/access policy for uploaded documents.
4. Confirm signed webhook secrets, provider allowlists, and sandbox-to-production separation.
5. Review dependencies with network access and update only through a tested change.
6. Run a role/IDOR review with separate synthetic users, plus an external security assessment appropriate to the data handled.

## 12. Dependencies and build

PHP dependencies are declared in `composer.json` and locked by `composer.lock`. Frontend package metadata is in `package.json` and `pnpm-lock.yaml`; `pnpm-workspace.yaml` declares the workspace. The project has a small Node build script for the bundled PowerPoint viewer. The Laravel app does not have a general Vite build script in `package.json`.

`composer audit --locked --no-interaction` completed with no known advisories for the locked PHP dependencies. A pnpm/npm advisory audit was not run. No dependency changes were made during this audit.

## 13. Git, ignored files, and contributor workflow

The current branch is `codex/zain`; an `origin` remote is configured. The working tree contains substantial changes and untracked project files and local artifacts. Nothing was staged, committed, or pushed. Do not use `git add .` without reviewing every path.

The `.gitignore` excludes `.env`, dependencies, generated framework cache, logs, private uploads, pnpm store, and PHPUnit result cache. `.env` is ignored and must remain untracked. Before staging, inspect:

```powershell
git status --short
git diff --check
git diff -- .gitignore .env.example
git ls-files
```

Add only intentional source, migration, tests, configuration examples, and documentation. Never add `.env`, private uploads, user documents, local databases, logs, dependency directories, or generated cache. Review ZIP checkpoints and Word documents individually; they currently sit in the project root and are not automatically part of the handover source. A contributor should receive repository access only through the repository owner, and must never receive private payment, email, hosting, or personal account credentials.

No commit or push was performed. Before pushing, inspect staged changes, run tests, verify there are no secrets or private files in the staged diff, and ask the repository owner to review the branch and remote.

## 14. Deployment and launch checklist

1. Choose hosting that supports the required PHP version/extensions, Laravel scheduler, queue worker, HTTPS, MySQL, and private persistent storage.
2. Back up the database and private uploads; verify restoration on a separate environment.
3. Provision a least-privilege database and private production `.env`; set production/debug/session settings safely.
4. Apply reviewed migrations with `php artisan migrate --force` only after backup and staging verification.
5. Configure a real account-mail transport and, if used, Google Apps Script plus the queue worker. Send a controlled test to an authorized mailbox.
6. Configure Safepay sandbox and a public HTTPS webhook; verify callbacks, idempotency, expiry, cancellation, and refund reconciliation. Keep live mode disabled until the owner explicitly approves it.
7. Start a supervised queue worker and run Laravel's scheduler every minute. Monitor failures and outbox backlog.
8. Configure backups, log retention, error monitoring, privacy/retention policy, and incident contact.
9. Run a release smoke test using separate parent, doctor, and administrator accounts; review permissions and mobile layout.
10. Only then decide whether the owner is ready to enable production payment and launch publicly.

## 15. Troubleshooting

- **Database connection error:** confirm MySQL is running, the database exists, credentials are correct, and PHP has `pdo_mysql`; then use `php artisan migrate:status`.
- **Blank page or detailed exception in production:** keep debug off; inspect protected server logs and correct the configuration.
- **Verification email is not in the inbox:** with `MAIL_MAILER=log`, it is not sent; configure a real mail transport and check queue/mail logs.
- **Appointment messages remain pending:** confirm both Apps Script settings, scheduler, queue worker, shared-secret agreement, and Apps Script deployment permissions.
- **Checkout does not complete:** stay in sandbox, inspect safe application logs, verify API settings and webhook reachability/signature; never treat a redirect as payment confirmation.
- **Private image/document missing:** verify the database path and private storage file exist; do not move it into `public/` to bypass access checks.
- **Changes do not appear:** clear only appropriate Laravel caches (`php artisan optimize:clear`) and reload. Do not delete user data or change the database to fix a cache issue.

## 16. Where this manual lives

This consolidated manual lives at the project root as `PROJECT_MANUAL.md`. The project keeps this single full guide and a concise `README.md`; older module-by-module manuals were removed to avoid duplicate instructions. A downloadable DOCX copy is delivered separately from the Laravel source tree.
