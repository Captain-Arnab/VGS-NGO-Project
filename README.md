# NGO Admin Panel

A self-hosted admin dashboard for NGO operations: donors, donations, volunteers, campaigns, events, beneficiaries, reports, public documents, and blog posts. Built with core PHP and MySQL — no framework required.

---

## Features

| Area | Capabilities |
|------|----------------|
| **Dashboard** | KPI cards, Chart.js trends, campaign progress |
| **Donors & donations** | CRUD, filters, pagination (15/page), receipt-style views |
| **Volunteers** | Registration, status, skills |
| **Campaigns & events** | Goals, raised amounts, schedules, linked beneficiaries |
| **Beneficiaries** | Aid categories, campaign/event links, document uploads |
| **Reports** | Charts and CSV export |
| **Documents & blog** | File uploads (PDF/images), rich text (Quill) |
| **Admin** | Login, session auth, profile (name, email, avatar, password), SweetAlert feedback |
| **UI** | Bootstrap 5.3, responsive sidebar (desktop / tablet / mobile drawer) |

---

## Requirements

| Component | Version |
|-----------|---------|
| PHP | 8.0+ (uses `str_starts_with`, `str_ends_with`) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Web server | Apache (XAMPP, WAMP, or shared hosting) |
| Extensions | `pdo_mysql`, `mbstring`, `fileinfo` (recommended) |

**Frontend (CDN):** Bootstrap 5.3, jQuery, Chart.js, Flatpickr, Select2, Quill.js, DataTables, Font Awesome.

---

## Quick start (XAMPP / local)

### 1. Copy the project

Place the `admin` folder under your web root, for example:

```
C:\xampp\htdocs\VGS\NGO\admin\
```

The app **auto-detects** its URL path — you do not hardcode `/VGS/NGO/admin` in code.

### 2. Create the database

1. Start **Apache** and **MySQL** in XAMPP.
2. Open [phpMyAdmin](http://localhost/phpmyadmin) or use the CLI.
3. Import the SQL dump (schema + optional demo data):

**phpMyAdmin:** Import → choose `database/ngo_admin.sql`

**CLI (Windows):**

```powershell
cd C:\xampp\htdocs\VGS\NGO\admin
C:\xampp\mysql\bin\mysql.exe -u root -p < database\ngo_admin.sql
```

**CLI (Linux / macOS):**

```bash
mysql -u root -p < database/ngo_admin.sql
```

This creates the `ngo_admin` database, all tables, a default admin user, and sample records (donors, donations, campaigns, etc.).

> **Note:** `database/*.sql` files are **not committed to Git** (they may contain PII and password hashes). Keep `ngo_admin.sql` on your machine or share it securely with your team — do not publish it in a public repository.

### 3. Configure the database (local)

Default settings in `includes/config.php` match a typical XAMPP install:

| Constant | Default (local) |
|----------|-----------------|
| `DB_HOST` | `localhost` |
| `DB_NAME` | `ngo_admin` |
| `DB_USER` | `root` |
| `DB_PASS` | *(empty)* |

Change these in `config.php` only for local development if needed.

### 4. Writable uploads folder

Ensure the web server can write to `uploads/` (avatars, campaign banners, documents):

- Windows (XAMPP): usually works as-is under `htdocs`.
- Linux: `chmod -R 775 uploads` and correct owner (`www-data` or your vhost user).

### 5. Log in

Open in the browser (adjust the path to match your folder):

```
http://localhost/VGS/NGO/admin/login.php
```

| Field | Default (after import) |
|-------|-------------------------|
| Email | `admin@ngo.local` |
| Password | `admin123` |

**Change this password immediately** via **My Profile** (top-right menu).

---

## Configuration

### Base URL

Links and assets use an auto-detected base path from `DOCUMENT_ROOT`. No manual path is required for most hosts.

If links break on a specific server (unusual), create a local override file:

**Windows:**

```powershell
copy includes\config.local.php.example includes\config.local.php
```

**Linux / macOS:**

```bash
cp includes/config.local.php.example includes/config.local.php
```

Edit `includes/config.local.php` and uncomment:

```php
define('BASE_URL_OVERRIDE', '/your-subfolder-name');
```

### Production database credentials

**Do not put live passwords in `config.php` if that file is tracked in Git.**

Use `includes/config.local.php` (gitignored) for production:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ngo_admin');
define('DB_USER', 'ngo_app_user');
define('DB_PASS', 'strong_random_password_here');
```

`config.php` loads `config.local.php` automatically when the file exists.

### Other settings (`includes/config.php`)

| Constant | Purpose |
|----------|---------|
| `PER_PAGE` | List pagination (default `15`) |
| `MAX_UPLOAD_SIZE` | Upload limit in bytes (default `10 MB`) |

---

## Project structure

```
admin/
├── index.php                 # Dashboard
├── login.php / logout.php
├── profile.php               # Admin account settings
├── donors/                   # Donor list, create, view
├── donations/
├── volunteers/
├── campaigns/
├── events/
├── beneficiaries/
├── reports/                  # Charts + CSV export
├── documents/
├── blogs/
├── includes/
│   ├── config.php            # App settings (committed)
│   ├── config.local.php      # Local/production overrides (NOT in Git)
│   ├── config.local.php.example
│   ├── db.php                # PDO connection
│   ├── auth.php              # Session login guards
│   ├── header.php / footer.php / sidebar.php
│   └── helpers.php           # URLs, uploads, flash messages
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── uploads/                  # User files (NOT in Git; .gitkeep only)
└── database/
    └── ngo_admin.sql         # Import locally (NOT in Git)
```

---

## Git & security

This repository uses a **security-focused** `.gitignore`. The following are **never** committed:

- `includes/config.local.php` — production secrets
- `database/*.sql` — schema dumps, donor/PII data, password hashes
- `uploads/**` — uploaded files and documents
- `.env`, keys, logs, backups, archives

**Recommended workflow**

1. Clone the repo on the server or your PC.
2. Import `database/ngo_admin.sql` from a **secure copy** (USB, private drive, encrypted share).
3. Create `config.local.php` on the server with real DB credentials.
4. Change the default admin password after first login.
5. Use a dedicated MySQL user with only the privileges this app needs.

---

## Production deployment checklist

- [ ] Upload application files (exclude `uploads` content unless migrating).
- [ ] Create MySQL database and a **limited** DB user (not `root`).
- [ ] Import `ngo_admin.sql` or a production-only schema dump **without** demo PII if preferred.
- [ ] Create `includes/config.local.php` with `DB_*` and optional `BASE_URL_OVERRIDE`.
- [ ] Set `uploads/` permissions (e.g. `755` / `775`; not world-writable).
- [ ] Enable **HTTPS** on the admin URL.
- [ ] Log in, change default admin password, update admin email if needed.
- [ ] Confirm `database/` is not web-accessible (no public listing of `.sql` files).
- [ ] Do not commit or expose `ngo_admin.sql`, backups, or `.env` files.

---

## Demo data

The bundled `ngo_admin.sql` import includes sample data, for example:

- Donors, donations, volunteers, campaigns, events, beneficiaries
- Blog posts and document metadata
- Placeholder PDFs under `uploads/documents/` (if present on disk)

For a **clean production** install, import only the structure and admin row, or delete demo rows after testing.

---

## Troubleshooting

| Problem | What to check |
|---------|----------------|
| *Database connection failed* | MySQL running; database imported; `DB_*` in `config.php` or `config.local.php` |
| Broken CSS / images / links | Wrong base path → set `BASE_URL_OVERRIDE` in `config.local.php` |
| Upload errors | `uploads/` exists and is writable; file under `MAX_UPLOAD_SIZE` |
| *Invalid email or password* | Import completed; use `admin@ngo.local` / `admin123` then change password |
| 404 on subpages | Apache `mod_rewrite` not required; use URLs that include `index.php` in module folders |

---

## License & support

Internal / project use — adjust license and contact details for your organisation as needed.
