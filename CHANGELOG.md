# CHANGELOG

This document tracks the security vulnerabilities, code quality improvements, and architectural modernizations resolved in the WAVSS repository. Each entry details the state before the fix and how the issue was remediated.

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
