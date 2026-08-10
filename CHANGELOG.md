# CHANGELOG

This document tracks the security vulnerabilities and code quality issues resolved in the WAVSS repository. Each entry details the state before the fix and how the issue was remediated.

## 1. Hardcoded Database Credentials
* **Before:** Database credentials (username, password, etc.) were hardcoded directly in the source code in `databaseFunctions.php`.
* **After:** Extracted credentials to a `.env` file using the `phpdotenv` library and read them securely using `getenv()`.

## 2. SQL Injection Vulnerabilities
* **Before:** User inputs were concatenated directly into SQL queries in `session_control.php` (login form) and `begin_scan.php` (scan status updates), leaving the application vulnerable to SQL injection.
* **After:** Refactored SQL queries to use `mysqli::prepare()`, `bind_param()`, and `get_result()`. Integers (like `$testId`) are cast explicitly.

## 3. Weak Password Hashing
* **Before:** User passwords were saved using the weak and outdated `sha1()` hashing algorithm without salting.
* **After:** Migrated to the robust Argon2id algorithm using `password_hash()` and `password_verify()`. Also implemented a backward-compatible lazy migration strategy that seamlessly upgrades legacy `sha1` hashes to Argon2id hashes upon a successful login.

## 4. Reflected Cross-Site Scripting (XSS)
* **Before:** User-supplied inputs (`$urlToScan`, `$testId`, etc.) were echoed directly into inline JavaScript `<script>` blocks inside `scanner_form.php` and `crawler_form.php`.
* **After:** Inputs are now securely passed through `json_encode()` with `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP` flags, producing perfectly escaped JavaScript string literals and neutralizing XSS payloads.

## 5. Cross-Site Request Forgery (CSRF)
* **Before:** State-changing forms (login, register, start scan, start crawl) lacked anti-CSRF tokens, making them vulnerable to cross-site request forgery attacks.
* **After:** Created a `csrf.php` helper to generate and verify cryptographically secure tokens. Added hidden token inputs to all sensitive forms and enforced token validation (via `hash_equals()`) in their POST handlers.

## 6. Sensitive Data in Version Control
* **Before:** Full SQL dumps containing live usernames, emails, actual password hashes, and external site scan histories were committed directly to the root of the repository.
* **After:** Created a sanitized `database/seed.sql` containing only the schema and static vulnerabilities list. Excluded the sensitive SQL dump files via `.gitignore`. 

## 7. Missing Domain Ownership Verification
* **Before:** Any registered user could start a scan against any arbitrary URL without proving they owned the target domain.
* **After:** Implemented a mandatory domain verification flow. Users must now prove ownership of external domains via a DNS TXT record or an HTTP file upload before scans can commence (localhost and loopback addresses are cleanly exempted for local testing).

## 8. Lack of Rate Limiting
* **Before:** The scanner lacked restrictions on how frequently or concurrently users could initiate scans, opening the door to abuse or Denial of Service (DoS).
* **After:** Implemented rate limiting inside `scanner_form.php`. Users are now restricted to one active scan at a time, and a 5-minute cool-down period is enforced between successive scans of the identical URL.
