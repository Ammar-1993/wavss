
# WAVSS: Web Application Vulnerability Scanner System

## Overview

WAVSS is a comprehensive, automated web-based vulnerability scanner designed to help developers, cybersecurity professionals, and website owners identify common security vulnerabilities in web applications. The system performs web crawling, scans for vulnerabilities such as SQL Injection and Cross-Site Scripting (XSS), and generates detailed, actionable PDF reports. These reports are delivered directly to users via email to facilitate proactive security management.

---

## Features

- **Automated Web Crawling:** Efficiently maps the target website by collecting all accessible URLs for thorough scanning.
- **Multi-Vulnerability Scanning:** Detects a variety of common web vulnerabilities including SQL Injection, XSS, Directory Listing, and more.
- **Real-Time Scan Updates:** Provides live progress updates during crawling and scanning processes.
- **User Management:** Supports guest users with limited access and registered users with full scanning capabilities.
- **Detailed Reporting:** Generates comprehensive PDF reports summarizing findings, vulnerability descriptions, remediation steps, and scan metrics.
- **Email Delivery:** Automatically sends generated reports to the registered user’s email for easy access and record keeping.
- **Scan History:** Maintains a history of scans allowing users to review past results and download previous reports.

---

## Technology Stack

- **Frontend:** HTML, CSS, JavaScript (for dynamic, user-friendly interfaces)
- **Backend:** PHP (handles crawling, scanning logic, and report generation)
- **Database:** MySQL (stores user data, scan results, and vulnerability details)
- **Libraries:** TCPDF (PDF generation), SimpleHTMLDOM (HTML parsing)

---

## System Workflow

1. **User inputs target URL** to be scanned.
2. **Crawler module** collects all URLs within the target domain.
3. **User selects scan options** to specify which vulnerabilities to test.
4. **Scanner module** performs dynamic vulnerability testing on collected URLs.
5. **System aggregates scan results** and generates a detailed PDF report.
6. **Report is emailed** automatically to the user.
7. **Scan history is saved** for future review and report download.

---

## Installation & Setup

1. Ensure you have a web server with PHP and MySQL support (e.g., XAMPP).
2. Import the provided SQL database schema (`wavssv3_db.sql`) into your MySQL server.
3. Place the project files in your web server’s root directory.
4. Configure database connection parameters in the relevant PHP configuration file (`test_db.php` or similar).
5. Access the application via your browser to register and start scanning.

---

## Usage

- Register a new account or log in if you already have one.
- Navigate to the crawler interface to input the target website URL.
- Start crawling to collect URLs and wait for the process to complete.
- Choose specific vulnerabilities to scan or proceed with the default selection.
- Initiate the scanning process and monitor progress in real-time.
- After completion, view the generated report online or download it.
- Reports are automatically sent to your registered email for convenience.
- Review past scans and reports in the scan history section.

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

## License

This project is currently private. Please contact the maintainer for licensing inquiries.

---

## Contact

For any questions, feedback, or support, please contact:

**Ammar-1993**  
- WhatsApp: [Click here](https://wa.me/967714294340)  
- Gmail: [Click here](mailto:ammaralnggar@gmail.com) 

---

Thank you for using WAVSS — helping you secure your web applications efficiently and effectively!



