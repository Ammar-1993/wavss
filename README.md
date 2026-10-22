# WAVSS: Web Application Vulnerability Scanner System

## Overview

**WAVSS** is an automated, web-based **Dynamic Application Security Testing (DAST)** platform designed to help developers, cybersecurity professionals, and system administrators identify vulnerabilities in web applications. The system maps attack surfaces using an automated crawler, performs dynamic security testing across diverse attack vectors (including SQL Injection, Cross-Site Scripting, Missing Security Headers, and Sensitive File Exposure), and provides AI-assisted triage alongside multi-format reporting (PDF, JSON, HTML).

---

## Features

- **Automated Web Crawling:** Recursively maps the target domain to discover accessible endpoints, query parameters, and HTML forms.
- **Dynamic Vulnerability Testing:** Detects common web vulnerabilities including:
  - Error-based SQL Injection & Authentication Bypass
  - Reflected & Stored Cross-Site Scripting (XSS)
  - Missing HTTP Security Headers (`CSP`, `HSTS`, `X-Frame-Options`, `X-Content-Type-Options`)
  - Sensitive File Exposure (`.env`, `.git/HEAD`, `.DS_Store`, etc.)
  - Insecure Direct Object References (IDOR) & Directory Listing
  - HTTP Banner Disclosure, SSL Certificate validity, and Unvalidated Redirects
- **Asynchronous Job Processing:** Decoupled background worker daemon processes scans reliably via a database-backed job queue.
- **AI-Assisted Triage:** Integrates with the Google Gemini API to analyze discovered findings, calculate True-Positive confidence ratings, and generate plain-language explanations.
- **Domain Ownership Verification:** Mandatory DNS TXT record or HTTP token verification protects external sites from unauthorized scans.
- **Robust Security & Auth:** Argon2id password hashing, TOTP Two-Factor Authentication (2FA), CSRF protection across all forms, rate limiting, and scan concurrency locks.
- **Headless REST API:** Authenticated Bearer token API (`/api/v1/scan`, `/api/v1/status`, `/api/v1/report`) for automated CI/CD security gating.
- **Historical Trends & Analytics:** Interactive client-side dashboards powered by Chart.js to track vulnerability remediation over time.
- **Scheduled Scans:** Set up recurring daily or weekly scans driven by an integrated background scheduler.
- **Comprehensive Reporting:** Export actionable reports in PDF (TCPDF), structured JSON, or self-contained HTML formats.

---

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Chart.js
- **Backend:** PHP 8.2+
- **Database:** MariaDB 11 / MySQL
- **Containerization:** Docker & Docker Compose
- **Key PHP Packages & Libraries:**
  - `vlucas/phpdotenv` (Environment variable configuration)
  - `monolog/monolog` (Rotating logging infrastructure)
  - `pragmarx/google2fa` & `endroid/qr-code` (TOTP Two-Factor Authentication)
  - `tecnickcom/tcpdf` (PDF report generation)
  - `PHPCrawl` & `simplehtmldom` (Web crawling & HTML DOM parsing)
  - `phpunit/phpunit` (Automated unit testing)
  - `friendsofphp/php-cs-fixer` (PSR-12 coding standard enforcement)
- **External APIs:** Google Gemini API (AI-assisted vulnerability triage)

---

## System Architecture

```mermaid
flowchart TD
    subgraph Clients["1. Clients & Access Layer"]
        UI["Web Portal (Bootstrap 5 & Chart.js)"]
        API["Headless REST API (Bearer Token Auth)"]
        Cron["Scheduled Scanner (CLI Cron Daemon)"]
    end

    subgraph Gateway["2. Security & Guardrails Gateway"]
        Auth["Authentication (Argon2id + TOTP 2FA)"]
        CSRF["CSRF Verification (hash_equals)"]
        DomainCheck{"Domain Verification Check"}
        RateLimit{"Rate Limiting & Concurrency Lock"}
    end

    subgraph Orchestration["3. Execution & Asynchronous Queue"]
        AppServer["Apache / PHP 8.2 Application"]
        JobsDB[("MariaDB: jobs Queue")]
        Worker["CLI Worker Daemon (worker.php)"]
    end

    subgraph Engine["4. Surface Mapping & DAST Testing Engine"]
        Crawler["Crawler Engine (PHPCrawl)"]
        Surface["Surface Extractor (Forms, Inputs, Query Params)"]
        ScanManager["Scanner Orchestrator (begin_scan.php)"]
        
        subgraph TestSuite["Dynamic Vulnerability Modules"]
            direction LR
            SQLi["SQL Injection & Auth Bypass"]
            XSS["Cross-Site Scripting (Reflected & Stored)"]
            SecHeaders["Missing Security Headers"]
            FileExposure["Sensitive File Exposure"]
            Misc["IDOR, Directory Listing, Redirects, SSL"]
        end
    end

    subgraph Intelligence["5. AI-Assisted Triage Engine"]
        Gemini["Google Gemini API (aiTriage.php)"]
        Confidence["Confidence Scoring & Remediation Analysis"]
    end

    subgraph Storage["6. Persistence & Multi-Format Outputs"]
        Database[("MariaDB 11 Database")]
        PDF["Executive PDF Report (TCPDF)"]
        JSONOut["Machine-Readable JSON"]
        HTMLOut["Interactive HTML Report"]
        TrendsView["Historical Trends Dashboard"]
        Logs["Rotating Audit Logs (Monolog)"]
    end

    UI --> Auth
    Auth --> CSRF
    CSRF --> DomainCheck
    API --> DomainCheck
    Cron --> RateLimit

    DomainCheck -->|Verified Domain or Localhost| RateLimit
    DomainCheck -->|Unverified Domain| Blocked["Scan Blocked (HTTP 403 / Error)"]
    RateLimit -->|Permitted| AppServer
    RateLimit -->|Scan Active / In Cooldown| Throttle["Throttled / Scan Enqueued"]

    AppServer -->|Enqueue Job (status: pending)| JobsDB
    Worker -->|Poll & Claim Job (Row Lock)| JobsDB
    Worker -->|Execute Scan| ScanManager

    ScanManager --> Crawler
    Crawler --> Surface
    Surface --> TestSuite
    TestSuite -->|Discovered Findings| Gemini
    Gemini --> Confidence
    Confidence -->|Triaged Results & AI Notes| Database

    Database --> PDF
    Database --> JSONOut
    Database --> HTMLOut
    Database --> TrendsView
    ScanManager -.->|Audit Trail| Logs
```

---

## System Workflow

1. **Target Submission:** The user submits a target URL and selects desired vulnerability modules via the web UI or REST API.
2. **Safety Validation:** Domain ownership is verified (`domain_verifications`), and concurrency/cooldown rate limits are enforced.
3. **Queueing:** The scan request is persisted in the `jobs` table with a status of `pending`.
4. **Worker Pickup:** The CLI daemon (`scripts/worker.php`) claims the job and executes the core scanner engine (`scanner/begin_scan.php`).
5. **Surface Discovery:** The integrated crawler crawls the target host, harvesting links, endpoints, inputs, and forms.
6. **Active Vulnerability Testing:** The test suite probes discovered inputs with tailored payloads to detect vulnerabilities.
7. **AI Triage:** Discovered findings are optionally evaluated by the Gemini API for false-positive filtering and remediation notes.
8. **Reporting & Persistence:** Results are saved to `test_results`. PDF, JSON, and HTML reports are compiled, and metrics are rendered in the Trends dashboard.

---

## Installation & Setup

### Option 1: Docker Compose (Recommended)

The fastest and most reliable way to run WAVSS is via Docker Compose:

1. **Clone the repository:**
   ```bash
   git clone <repo-url>
   cd wavss
   ```
2. **Configure environment variables:**
   Copy `.env.docker.example` to `.env.docker` and adjust database credentials and your optional Gemini API key:
   ```bash
   cp .env.docker.example .env.docker
   ```
3. **Build and start containers:**
   ```bash
   docker compose up -d --build
   ```
   This will spin up:
   - `app`: Apache + PHP 8.2 web server exposed at `http://localhost:8084`
   - `worker`: Dedicated background daemon processing the scan queue
   - `db`: MariaDB 11 instance automatically seeded from `database/seed.sql`

4. **Verify health:**
   Navigate to `http://localhost:8084/healthz.php` to confirm database connectivity and application status.

---

### Option 2: Manual / Local Setup

If running directly on a local LAMP/XAMPP stack:

1. **Prerequisites:** PHP 8.2+ (with `pdo`, `mysqli`, `gd`, `curl` extensions), MariaDB/MySQL, and Composer.
2. **Install dependencies:**
   ```bash
   composer install
   ```
3. **Initialize Database:**
   Create a database (e.g. `wavssv3_db`) and import the clean seed schema:
   ```bash
   mysql -u root -p wavssv3_db < database/seed.sql
   ```
4. **Configure Environment:**
   Copy `.env.example` to `.env` and set your database connection parameters:
   ```bash
   cp .env.example .env
   ```
5. **Start the Background Queue Worker:**
   In a separate terminal or background process manager, start the worker daemon:
   ```bash
   php scripts/worker.php
   ```
6. **Serve the Application:**
   Point your web server document root to the project directory, or use PHP's development server:
   ```bash
   php -S 0.0.0.0:8084
   ```

---

## Usage Guide

1. **Account Registration & 2FA:**
   - Register a new account at `/register.php` (passwords are hashed with Argon2id).
   - Log in at `/login.php`.
   - Optionally enable Two-Factor Authentication via **Enable 2FA** in the top navigation bar.
2. **Domain Ownership Verification:**
   - Navigate to **Verify Domain** (`/verify_domain.php`) before scanning any external domain.
   - Follow instructions to add a DNS `TXT` record or upload a verification file to your target domain.
   - *Note:* Loopback targets (`localhost`, `127.0.0.1`, `dvwa`) are automatically exempt for testing.
3. **Initiate a Scan:**
   - Go to **Scanner** (`/scanner.php`), enter the target URL, select vulnerability modules, and submit.
   - Monitor real-time status updates via the interface.
4. **Review Findings & Reports:**
   - View past scans and active job statuses in **Scan History** (`/history.php`).
   - Click **View Findings** to inspect identified vulnerabilities and AI triage notes.
   - Download the generated **PDF Report** directly from the history dashboard.
5. **Historical Trends:**
   - Visit **Trends** (`/trends.php`) to view longitudinal charts tracking vulnerability categories across consecutive scan runs.
6. **Scheduled / Recurring Scans:**
   - Configure recurring scans in `/schedule_scan.php`.
   - Trigger scheduled jobs via cron by invoking:
     ```bash
     php scripts/run_scheduled_scans.php
     ```
7. **REST API Usage:**
   - Provision an API key at `/api/generate_key.php`.
   - Launch a scan programmatically:
     ```bash
     curl -X POST http://localhost:8084/api/v1/scan.php \
          -H "Authorization: Bearer <YOUR_API_KEY>" \
          -H "Content-Type: application/json" \
          -d '{"url": "http://localhost", "testCases": "rxss sqli secheaders fileexposure"}'
     ```
   - Check scan status:
     ```bash
     curl -X GET "http://localhost:8084/api/v1/status.php?testId=<TEST_ID>" \
          -H "Authorization: Bearer <YOUR_API_KEY>"
     ```
   - Retrieve reports in JSON or HTML:
     ```bash
     curl -X GET "http://localhost:8084/api/v1/report.php?testId=<TEST_ID>&format=json" \
          -H "Authorization: Bearer <YOUR_API_KEY>"
     ```

---

## Implemented Features (Phases 0 - 6)

The WAVSS platform has undergone a massive modernization and security overhaul across seven iterative phases:

### Phase 0 & 1: Security Foundation & Code Quality
- **Robust Authentication:** Argon2id password hashing with backward-compatible legacy upgrades, plus TOTP-based Two-Factor Authentication (2FA).
- **Core App Security:** Comprehensive eradication of SQL Injection via strict Prepared Statements, XSS sanitization, and cryptographically secure CSRF tokens on all state-changing endpoints.
- **Abuse Prevention:** Rate limiting, active-scan concurrency locks, and mandatory Domain Ownership verification via DNS/TXT to prevent unauthorized external scanning.
- **Modern Standards:** Complete integration of Composer dependency management, Monolog rotating logs, and PHP-CS-Fixer styling enforcement.

### Phase 2 & 3: Testing & CI/CD Stability
- **Automated Validation:** Full PHPUnit test coverage for core business logic.
- **Dockerization:** Fully containerized ecosystem (Apache/PHP 8.2 + MariaDB) with automated schema seeding and health checks.
- **Continuous Integration:** GitHub Actions pipeline running strict PHP syntax linting, unit tests, and deterministic End-to-End (E2E) automated regression scans against an internal vulnerable target on every commit.

### Phase 4 & 5: Modern Architecture & Core Modules
- **True Background Queue:** Asynchronous job processing powered by a dedicated CLI worker daemon, replacing fragile HTTP self-triggering loops.
- **Headless REST API:** Fully authenticated endpoints (`/scan`, `/status`, `/report`) supporting automated CI/CD triggering and dynamic output formatting (`JSON`, `HTML`, `PDF`).
- **AI-Assisted Triage:** Optional Gemini LLM integration to analyze scan findings, evaluate confidence scores, and append plain-language explanations.
- **Data Visualization & Scheduling:** Client-side Chart.js dashboards for tracking historical vulnerability trends, alongside an integrated cron-driven scheduler for recurring scans.
- **Expanded Test Suite:** Added active detection for Missing Security Headers and Sensitive File Exposure (e.g., `.env`, `.git`), featuring strict content signature verification.

### Phase 6: Final Review & Integration
- **Module Activation:** Fully activated the previously orphaned Security Headers and Sensitive File Exposure detection modules across the Web UI, Cron Scheduler, and REST API.
- **2FA Stability:** Hardened the TOTP Two-Factor Authentication enrollment flow by ensuring all necessary cryptographic and image-generation libraries (PHP GD) are strictly enforced at the container level.

## Future Enhancements

- Real-time threat intelligence feed integration.
- *Note on Blind SQL Injection:* While the scanner detects error-based SQL Injection, automated detection of Blind/Time-based SQL Injection was intentionally excluded from manual implementation in this tool to prevent the generation of functional exploitation payloads (like `SLEEP()` commands) that can cause unintended denial-of-service on target servers. For testing Blind SQLi, we recommend using specialized open-source tools such as [sqlmap](https://sqlmap.org/) under controlled conditions.

---

## 👤 Author

<div align="center">
  <p>Architected & Engineered with ❤️ by <b>Eng. Ammar Al-Najjar (م. عمار النجار)</b></p>

<p>
  <a href="mailto:ammaralnggar@gmail.com">
    <img src="https://img.shields.io/badge/Drop_me_an_Email-EA4335?style=for-the-badge&logo=gmail&logoColor=white" alt="Email">
  </a>
  <a href="https://wa.me/967714294340">
    <img src="https://img.shields.io/badge/Chat_on_WhatsApp-25D366?style=for-the-badge&logo=whatsapp&logoColor=white" alt="WhatsApp">
  </a>
  <a href="https://ammar1993.vercel.app/">
    <img src="https://img.shields.io/badge/Visit_My_Portfolio-3E7FFF?style=for-the-badge&logo=google-chrome&logoColor=white" alt="Portfolio">
  </a>
  </p>
  <div align="center">
   <sub>All rights reserved © 2026 Engineer Ammar Al-Najjar</sub>
  </div>
</div>

---

## License

This project is currently private. Please contact the maintainer for licensing inquiries.

---

Thank you for using WAVSS — helping you secure your web applications efficiently and effectively!



