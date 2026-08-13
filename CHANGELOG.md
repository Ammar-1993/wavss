# CHANGELOG

This document tracks the security vulnerabilities, code quality improvements, and architectural modernizations resolved in the WAVSS repository. Each entry details the state before the fix and how the issue was remediated.

**Summary of Recent Phases:**
* **Phase Three (CI/CD Stabilization):** Focused on securing the CI/CD pipeline, guaranteeing deterministic E2E regression tests, fixing Docker race conditions, and resolving legacy PHP 8 syntax crashes.
* **Phase Four (Feature Enhancements):** Delivered all major roadmap features including TOTP Two-Factor Authentication, a headless REST API layer, scheduled background scans, a historical trends dashboard, and a Gemini-powered AI-assisted vulnerability triage engine.

## Phase Zero: Security & Foundational Fixes

### 1. Hardcoded Database Credentials
* **Before:** Database credentials (username, password, etc.) were hardcoded directly in the source code in `databaseFunctions.php`.
* **After:** Extracted credentials to a `.env` file using the `phpdotenv` library and read them securely using `getenv()`.

### 2. SQL Injection Vulnerabilities
* **Before:** User inputs were concatenated directly into SQL queries in `session_control.php` (login form) and `begin_scan.php` (scan status updates), leaving the application vulnerable to SQL injection.
* **After:** Refactored SQL queries to use `mysqli::prepare()`, `bind_param()`, and `get_result()`. Integers (like `$testId`) are cast explicitly.

### 3. Weak Password Hashing
* **Before:** User passwords were saved using the weak and outdated `sha1()` hashing algorithm without salting.
* **After:** Migrated to the robust Argon2id algorithm using `password_hash()` and `password_verify()`. Also implemented a backward-compatible lazy migration strategy that seamlessly upgrades legacy `sha1` hashes to Argon2id hashes upon a successful login.

### 4. Reflected Cross-Site Scripting (XSS)
* **Before:** User-supplied inputs (`$urlToScan`, `$testId`, etc.) were echoed directly into inline JavaScript `<script>` blocks inside `scanner_form.php` and `crawler_form.php`.
* **After:** Inputs are now securely passed through `json_encode()` with `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP` flags, producing perfectly escaped JavaScript string literals and neutralizing XSS payloads.

### 5. Cross-Site Request Forgery (CSRF)
* **Before:** State-changing forms (login, register, start scan, start crawl) lacked anti-CSRF tokens, making them vulnerable to cross-site request forgery attacks.
* **After:** Created a `csrf.php` helper to generate and verify cryptographically secure tokens. Added hidden token inputs to all sensitive forms and enforced token validation (via `hash_equals()`) in their POST handlers.

### 6. Sensitive Data in Version Control
* **Before:** Full SQL dumps containing live usernames, emails, actual password hashes, and external site scan histories were committed directly to the root of the repository.
* **After:** Created a sanitized `database/seed.sql` containing only the schema and static vulnerabilities list. Excluded the sensitive SQL dump files via `.gitignore`. 

### 7. Missing Domain Ownership Verification
* **Before:** Any registered user could start a scan against any arbitrary URL without proving they owned the target domain.
* **After:** Implemented a mandatory domain verification flow. Users must now prove ownership of external domains via a DNS TXT record or an HTTP file upload before scans can commence (localhost and loopback addresses are cleanly exempted for local testing).

### 8. Lack of Rate Limiting
* **Before:** The scanner lacked restrictions on how frequently or concurrently users could initiate scans, opening the door to abuse or Denial of Service (DoS).
* **After:** Implemented rate limiting inside `scanner_form.php`. Users are now restricted to one active scan at a time, and a 5-minute cool-down period is enforced between successive scans of the identical URL.

### 9. Version Control Tracking of Generated Artifacts
* **Before:** Runtime files such as generated PDF reports, PHP-CS-Fixer caches, and local event logs were inadvertently tracked in Git.
* **After:** Finalized `.gitignore` to strictly exclude all `scanner/reports/*.pdf`, `scanner/logs/*`, `crawler/logs/*`, and `.php-cs-fixer.cache`, closing the remaining Phase Zero gaps.

## Phase One: Code Quality & Architecture Modernization

### 10. Dependency Management
* **Before:** Third-party libraries (`tcpdf`, `simplehtmldom`) were committed directly into the source tree.
* **After:** Migrated all libraries to Composer, added modern tooling (`phpunit/phpunit`, `monolog/monolog`, `friendsofphp/php-cs-fixer`), and strictly excluded the `vendor/` directory from version control.

### 11. Frontend Layout Duplication
* **Before:** HTML boilerplate (headers, navigation bars, footers) was copy-pasted across all 8 top-level PHP pages, making UI maintenance tedious and error-prone.
* **After:** Extracted shared layout structures into `templates/header.php` and `templates/footer.php`, leveraging a `$pageTitle` variable and active menu state logic to eliminate frontend code duplication entirely.

### 12. Logging Infrastructure
* **Before:** A custom, rigid `Logger` class wrote unformatted text lines to hardcoded log files, offering no log levels or retention management.
* **After:** Replaced the custom class with a thin wrapper around `Monolog`, leveraging `RotatingFileHandler` (14-day retention) and standard log levels without breaking existing call sites.

### 13. Coding Standards Enforcement
* **Before:** PHP files lacked consistent formatting and styling guidelines.
* **After:** Introduced a project-wide `.php-cs-fixer.php` configuration targeting PSR-12, explicitly ignoring third-party code to safely standardize code styling.

## Phase Two: Testing & Orchestration

### 14. Unit Testing
* **Before:** Pure business logic (e.g., forms, vulnerability models, token generation) lacked any automated testing.
* **After:** Built a comprehensive PHPUnit test suite under `tests/Unit/`, successfully covering all core non-I/O classes and functions to explicitly guarantee their behavior.

### 15. Containerization (Docker)
* **Before:** The application required a manual LAMP/XAMPP stack setup with hardcoded database host assumptions.
* **After:** Introduced a full Docker ecosystem with a `Dockerfile` (`php:8.2-apache`) and a `docker-compose.yml` defining linked `app` and `db` (MariaDB) services, initialized automatically via `seed.sql`. Added a lightweight `healthz.php` endpoint to monitor container readiness.

### 16. Continuous Integration & E2E Testing
* **Before:** Code changes were merged without automated safety nets or regression checks against known vulnerable targets.
* **After:** Established a GitHub Actions pipeline (`ci.yml` and `e2e.yml`) that automatically runs PHP syntax checks (`php -l`), executes unit tests, and launches an end-to-end regression scan on every push/PR. The E2E script deploys a `dvwa` container, automatically registers a WAVSS user, bypasses domain verification via a targeted local exemption, initiates a scan against the DVWA login page, and rigorously asserts the successful detection of a SQL Injection vulnerability in the backend database.

## Phase Three: CI/CD Stabilization

### 17. Composer Platform Constraints
* **Before:** `composer.json` allowed flexible dependency resolution, causing it to lock `symfony/console` to version 8.x, which strictly requires PHP 8.4+ and subsequently crashed the CI pipeline on PHP 8.2 runners.
* **After:** Pinned the `config.platform.php` setting to `8.2.33`, forcing Composer to safely resolve and downgrade dependencies to PHP 8.2-compatible versions.

### 18. E2E Target Reliability
* **Before:** The E2E test utilized a DVWA container as a target, which unpredictably blocked the scanner's SQLi payloads because the scanner did not maintain session cookies to bypass DVWA's CSRF protections.
* **After:** Replaced DVWA with a dedicated, lightweight internal script (`tests/E2E/target.php`) intentionally vulnerable to SQL injection, ensuring fast, reliable, and deterministic validation of the core scanning engine.

### 19. Legacy PHP 8 Syntax Errors
* **Before:** The legacy `PHPCrawl_2024` library utilized deprecated `&new` (assignment by reference) operators, which triggered fatal syntax errors in PHP 8.
* **After:** Refactored the library to remove the deprecated `&` operator, achieving full PHP 8 syntax compliance across the codebase.

### 20. CI Race Conditions & Dynamic Directories
* **Before:** The E2E pipeline suffered from multiple hidden race conditions and crashes: (1) it executed before MariaDB finished seeding the database, causing silent login failures; (2) TCPDF fatally crashed when saving reports because Git ignored the empty `scanner/reports` directory; and (3) a bash script failed to interpolate the `$TEST_ID` variable in the final SQL assertion.
* **After:** Enhanced `healthz.php` to actively query the database table to guarantee readiness, updated the `Dockerfile` to explicitly create ignored runtime directories (`reports/`, `logs/`), and corrected the bash variable syntax in `e2e.yml` to `${TEST_ID}`, securing a 100% green and stable CI/CD pipeline.

## Phase Four: Feature Enhancements (Future Scope)

### 21. Two-Factor Authentication (TOTP)
* **Before:** User authentication relied solely on a standard username/password combination.
* **After:** Integrated `pragmarx/google2fa` to optionally enforce Time-Based One-Time Password (TOTP) verification. Created an enrollment flow (`enable_2fa.php`) and a robust login interceptor (`verify_2fa.php`) that pauses session creation until a valid code is provided.

### 22. REST API Layer
* **Before:** The scanning engine was tightly coupled to the web UI, preventing programmatic or headless execution.
* **After:** Extracted core logic into `initializeNewScan()` and built a suite of RESTful API endpoints (`scan.php`, `status.php`, `report.php`). Introduced Bearer Token authentication via securely generated keys stored in a new `api_keys` table.

### 23. Scheduled & Recurring Scans
* **Before:** Users had to manually log in and click "Start Scan" every time they wanted to assess their web applications.
* **After:** Added a `scheduled_scans` table and a frontend scheduling UI. Built a CLI-only cron script (`run_scheduled_scans.php`) that automatically triggers asynchronous scans at specific daily/weekly intervals while cleanly bypassing standard active-scan concurrency locks.

### 24. Historical Trends View
* **Before:** Vulnerability data was siloed inside individual scan reports with no way to track remediation progress over time.
* **After:** Created `trends.php` using advanced SQL JOINs to aggregate vulnerability totals across historic scans. Implemented Chart.js to render visually rich, responsive line graphs tracking the trajectory of each vulnerability category (XSS, SQLi, etc.) on a per-domain basis.

### 25. AI-Assisted Vulnerability Triage
* **Before:** The scanner relied exclusively on deterministic signatures, occasionally yielding false positives when normal page content matched an attack string.
* **After:** Introduced an optional, non-blocking AI triage step (`aiTriage.php`) utilizing the Gemini API. When configured, it silently analyzes findings at the end of a scan, returning a plain-language explanation and True Positive confidence score cleanly injected into both the Web and PDF reports.
