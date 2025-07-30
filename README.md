
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

## Future Enhancements

- Integration of AI-powered vulnerability detection for improved accuracy.
- Scheduling functionality for automated periodic scans.
- Multi-factor authentication for enhanced security.
- API support for integration with external security tools.
- Mobile-friendly interface for scanning on-the-go.
- Real-time threat intelligence feed integration.

---

## Contributing

Contributions to WAVSS are welcome! Please fork the repository and submit pull requests with detailed descriptions. For major changes, please open an issue first to discuss your ideas.

---

## Project Contributors and Supervision

**Students:**  
- Remas Talal Alkhathami (442801844)  
- Aryam Abdullah Alkhathami (442801843)  
- Lamees Abdullah Alshahrani (442801829)  
- Nada Hamad Alshahrani (442801806)  
- Manar Mohammed Alzahrani (442801839)  
- Fai Mohameed Almaawi (442801842)  
- Ghaida Turki Aljahmi (442801834)  

**Supervisor:**  
- Dr. Muhammad Ayub Muhammad Khan  

**University:**  
- University of Bisha  
- College of Computers and Information Technology  
- Cybersecurity Department  

---

## License

This project is currently private. Please contact the maintainer for licensing inquiries.

---

## Contact

For any questions, feedback, or support, please contact:

**Ammar-1993**  
Email: [ammaralnggar@gmail.com]  
GitHub: [https://github.com/Ammar-1993](https://github.com/Ammar-1993)

---

Thank you for using WAVSS — helping you secure your web applications efficiently and effectively!
