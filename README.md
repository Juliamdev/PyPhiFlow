# PyPhiFlow | Intelligent Document Processing & Asynchronous OCR Pipeline

PyPhi Flow is an enterprise-grade hybrid backend system designed to automate the ingestion, processing, and extraction of financial metadata from invoices and receipts.

This project solves a real-world business bottleneck (manual data entry for accounting) by leveraging a scalable, Database-driven Asynchronous Queue Architecture that cleanly integrates a high-speed web gateway in PHP with an intelligent document processing worker in Python.

**🏛️ System Architecture & Design Decisions**

To ensure optimal performance and prevent blocking the client during intensive image analysis, the platform decouples the ingestion layer from the processing layer:

Ingestion Layer (Slim PHP 4 REST API): A lightweight API that handles user registration, secure session authentication (state-less JWT), and fast binary file uploads. It validates the file, stores it safely on disk, creates a pending job in MySQL, and immediately responds with a 202 Accepted status.

Persistence Layer (MySQL Database): Serves as our transactional ledger and database-as-a-queue. It contains indexed operational tables to facilitate lightning-fast lookups by the worker daemon.

Processing Layer (Python Background Worker): A standalone service running in background. It polls the database for pending documents, uses EasyOCR (deep-learning-based Optical Character Recognition) to parse the invoice image, applies Regex Natural Language Processing to locate financial details (CNPJ, Issue Date, Values), updates the database, and flags the transaction as PROCESSED.

**🛠️ Security Engineering Implementations**

Security is woven directly into the core design of PyPhi Ledger:

Strict Upload Sanitization: Prevented Remote Code Execution (RCE) and Path Traversal attacks by renaming files to unique UUID hexadecimals on storage and validating real binary signatures (MIME types via finfo) rather than trusting user-provided extensions.

Stateless Authorization: Secure endpoints protected under JWT (HMAC-SHA256 signature).

Cryptographic Hashing: User passwords are secured with the modern Argon2id/BCrypt algorithms.

SQL Injection Defenses: 100% of database queries utilize Prepared Statements both in PHP (PDO) and Python (MySQL Connector parametrization).

## 📁 Project Structure

```text
pyphi-flow/
├── database/
│   └── schema.sql              # Transactional MySQL migrations
├── api/                        # --- PHP API GATEWAY ---
│   ├── public/index.php        # Entry Point
│   ├── src/                    # Core Domain & Middlewares
│   ├── storage/uploads/        # Highly secured storage folder
│   └── composer.json           # PHP Dependency Manager
└── worker/                     # --- PYTHON AI WORKER ---
    ├── main.py                 # Queue Daemon
    ├── src/                    # OCR & NLP extraction algorithms
    └── requirements.txt        # Python Packages
```

**⚙️ Setting Up Locally**

The whole stack (MySQL + PHP API) runs with Docker Compose. Only [Docker Desktop](https://www.docker.com/products/docker-desktop/) is required.

1. Environment variables

```bash
cp .env.example .env   # then set your database passwords
```

2. Install PHP dependencies (Composer runs inside the container)

```bash
docker compose build
docker compose run --rm --no-deps api composer install
```

3. Start the stack

```bash
docker compose up -d
```

- API: http://localhost:8000
- MySQL (e.g. for MySQL Workbench): `127.0.0.1:3307`. The schema in `database/schema.sql` is applied automatically the first time the database is created.

Stop everything with `docker compose down`.

4. Worker Setup (Python) — *in development*

Ensure Python 3.10+ is installed.

cd worker
python -m venv venv
source venv/bin/activate  # On Windows use: venv\Scripts\activate
pip install -r requirements.txt
python main.py


**🤝 Let's Connect!**

This system demonstrates my ability to design complex, multi-language backend systems capable of processing real business tasks. Feel free to explore the code, open issues, or contact me for professional opportunities.

Developer: Julia Fialho (Systems Information student at PUC Minas)

