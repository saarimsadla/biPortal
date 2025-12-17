# 📊 BI Portal — HERS Reporting & QA Systems

**Repository:** [https://github.com/saarimsadla/biPortal](https://github.com/saarimsadla/biPortal)

The **BI Portal** is a collection of web applications that power the **Live Metrics Reporting Portal** and the **Online QA System**. Together, these systems provide real‑time process reporting, secure QA sample access, user‑based authorization, scheduling, and integrations across multiple data sources.

---

## 🚀 Project Overview

### 🔹 HERS Live Reporting Portal

A web‑based portal designed to support operational transparency and control for HERS processing across multiple clients and databases.

**Key Capabilities**

* Real‑time reporting of process execution status for stakeholders
* Manual and scheduled execution of monthly processes
* Login‑based hierarchy with role‑specific access and permissions
* Scalable backend processing and notification framework

**Core Responsibilities Implemented**

* Requirements elicitation and transformation into detailed technical design
* Backend development for process orchestration and monitoring
* Data modeling and architecture for efficient storage and processing
* Batch processing via Bash scripting with UI‑driven controls
* Backend scripting using **Go**, **PHP**, and **SQL**
* Program control logic for:

  * Email and Microsoft Teams notifications
  * Process initialization via predefined parameters
  * Process execution via scheduling tools
  * Status tracking and error reporting

**Future‑Ready Design**

* Operational reporting data currently stored in **MySQL**
* Architecture designed to support future migration to **MongoDB**
* Code structured to abstract data access and allow seamless MySQL → Mongo transitions

**Security & Deployment**

* Encrypted data transfer between internal databases and external HERS analysis teams
* Design documentation shared with senior leadership and external partners
* Tested and deployed to production environments
* Rolled out across multiple company clients

---

### 🔹 Online QA System

A secure internal web application built for QA teams to access and review HERS samples stored in AWS S3.

**Highlights**

* Centralized and secure access to QA samples through a single controlled server
* Eliminated manual sample delivery workflows
* Improved QA efficiency by ~20%
* Reduced delivery timelines by ~45%

**Technical Implementation**

* Requirements gathering and secure system design
* Backend development using **PHP** and **Python**
* Integration with **AWS S3** for secure sample storage and retrieval
* Frontend interface built with **JavaScript** for ease of use
* Server‑side access controls to restrict unauthorized access
* Automated sample retrieval to streamline QA workflows

---

## 🧠 System Architecture

### 🗄️ Data Sources

* **MySQL**

  * Primary data source for HERS operational and reporting data
* **MongoDB**

  * Authentication and authorization metadata
  * User rights and role descriptions
  * QA sampling portal storage
  * Salesforce campaign‑related metadata
* **Salesforce**

  * Campaign data consumed by reporting and analytics workflows
* **AWS S3**

  * Secure storage for QA sample PDFs

---

## 🛠️ Technology Stack

### Languages

GitHub repository language breakdown reflects the project’s **multi‑stack architecture**, combining frontend UI, backend services, automation, and data processing.

**Primary Languages & Responsibilities**

* **JavaScript (~42%)** — Frontend UI, client‑side logic, and interactive portal behavior
* **PHP (~20%)** — Core backend services, process orchestration, authentication hooks, and API endpoints
* **CSS / SCSS (~19%)** — Styling, layout, and responsive UI presentation
* **Python (~16%)** — Automation, QA workflows, AWS S3 integrations, and background processing
* **Go (~1.4%)** — High‑performance backend utilities and supporting automation scripts
* **SQL / Other files** — Data access, reporting queries, and configuration assets

This blend reflects a production‑grade system designed for scalability, operational reliability, and future extensibility.

---

## 📁 Repository Structure

```
configs/            # Application configuration files
css/                # Stylesheets
examplePdfs/        # Sample PDF artifacts
goScripts/          # Go‑based backend and automation scripts
images/             # UI assets
js/                 # Frontend JavaScript
phpRunners/         # PHP backend runners and services
pythonScripts/      # Python automation and QA scripts
vendor/             # PHP dependencies (Composer)
*.php / *.html      # Core application pages
```

---

## 🔐 Authentication & Authorization

* Role‑based access control with login hierarchy
* Authentication and permission metadata stored in MongoDB
* Secure session handling across applications
* QA and reporting access restricted by user role

---

## 📦 Dependency Management

* **PHP dependencies** managed using **Composer** (`composer.json`, `composer.lock`)
* JavaScript and Python dependencies managed within their respective modules/scripts

---

## ⚙️ Getting Started

### Prerequisites

* PHP 7.x+
* Python 3.x+
* Go 1.15+
* MySQL instance
* MongoDB instance
* AWS credentials (for S3 access)

### Setup

```bash
git clone https://github.com/saarimsadla/biPortal.git
cd biPortal
composer install
```

Configure environment files and database connections inside the `configs/` directory before running services.

---

## 🔒 Security Considerations

* Encrypted communication between systems and external teams
* No direct public access to S3 QA samples
* Server‑controlled access endpoints only

---

## 📈 Future Enhancements

* Full migration of operational datasets from MySQL to MongoDB
* Enhanced reporting dashboards
* Expanded Salesforce data ingestion
* Additional role‑specific UI views

---

## 👤 Author

**Saarim Sadla**
Solutions Architect | Cloud & Platform Engineer | Data Engineer

---

## 📄 License

This repository does not currently include an explicit license. Add a license file if open‑source distribution is intended.
