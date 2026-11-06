# KaziHub

**Find work. Find workers. Find success.**

KaziHub is a job platform for Kenya with three parts:

- **Freelance marketplace.** Workers list services (design, web, writing and more). Clients order them and track each order from start to finish.
- **Juakali jobs.** Local skilled and casual work. Jobs and available workers are matched by GPS ("Near me"), and job sites appear on a map.
- **UAE jobs.** Overseas listings that the admin approves before they go live, with visa and accommodation details and a scam-report button.

Built with **PHP 8, MySQL, HTML, CSS, JavaScript and Bootstrap 5**, plus Bootstrap Icons and Leaflet maps (OpenStreetMap). There are no Composer packages and no build step, so it runs on free shared hosting such as InfinityFree.

---

## Features

| Area | What's included |
|---|---|
| Accounts | Register as a worker or an employer, sign in and out, change password, login rate limiting |
| Profiles | Photo upload, county/town, GPS location, bio, skills, experience, hourly rate, availability switch, profile-strength meter, public profile with reviews |
| Freelance | Create, edit, pause and delete services; browse with search, category and price filters and sorting; order flow **pending → in progress → delivered → completed** (with revision requests); buyer reviews |
| Juakali | Post jobs with GPS location and urgency; "Near me" sorting by distance; map view; "Available workers" list; apply with a cover letter and price |
| UAE jobs | Emirate, company, visa and accommodation fields; every new or edited listing waits for admin approval; safety warnings; report button |
| Hiring | Applicants list; shortlist, accept or reject; worker's phone shown only after acceptance; employer reviews worker |
| Messaging | Inbox with unread counts, live chat that updates every 5 seconds, automatic messages when orders or applications change |
| Dashboards | Separate worker and employer dashboards with stats, recent activity and recommended jobs that match the worker's skills, nearest first |
| Admin | Overview stats, approve or reject jobs, verify or suspend users, scam reports, contact-form inbox |
| Pages | Home, help centre (FAQ), safety tips, contact form |
| Security | PDO prepared statements, CSRF tokens on every form, password hashing, output escaping, upload checks, `.htaccess` protection for config, includes, database and uploads |

---

## Install on InfinityFree (or any PHP/MySQL host)

1. **Create a database.** In the InfinityFree control panel open **MySQL Databases** and create one. Note the **host** (e.g. `sql123.infinityfree.com`), **database name**, **username** and **password**.
2. **Upload the files.** Upload everything in this folder into `htdocs` (or into a sub-folder such as `htdocs/kazihub`) with the File Manager or FTP (FileZilla).
3. **Edit `config/config.php`** with your database details:
   ```php
   define('DB_HOST', getenv('KAZI_DB_HOST') ?: 'sql123.infinityfree.com');
   define('DB_NAME', getenv('KAZI_DB_NAME') ?: 'if0_12345678_kazihub');
   define('DB_USER', getenv('KAZI_DB_USER') ?: 'if0_12345678');
   define('DB_PASS', getenv('KAZI_DB_PASS') ?: 'your-vpanel-password');
   ```
4. **Create the tables.** Either:
   - open `https://your-site/setup.php` and click **Create tables** (tick the box to add demo data), **or**
   - in phpMyAdmin, import `database/schema.sql` (and optionally `database/demo_data.sql`).
5. **Create your admin account** on the second step of `setup.php`.
6. **Delete `setup.php`** from the server.
7. Turn on the **free SSL certificate** in InfinityFree. The "Use my location" and "Near me" buttons only work on `https://`.

### Demo accounts (only if you loaded demo data)

All demo accounts use the password `password123`.

| Role | Email |
|---|---|
| Worker (plumber) | `brian@demo.kazihub` |
| Worker (designer) | `grace@demo.kazihub` |
| Employer | `sarah@demo.kazihub` |
| Recruitment agency | `gulf@demo.kazihub` |

Remove the demo data before launch:

```sql
DELETE FROM users WHERE id BETWEEN 1001 AND 1999;
```

### Run locally (XAMPP / Laragon)

Copy the folder into `htdocs`, create a `kazihub` database in phpMyAdmin, keep `DB_HOST = localhost`, `DB_USER = root` and an empty password, then open `http://localhost/kazihub/setup.php`.

---

## Folder structure

```
kazihub/
├── index.php            Home page
├── services.php         Browse freelance services
├── service.php          Service details + order form
├── service-edit.php     Create / edit / pause / delete a service
├── my-services.php      Worker's services
├── orders.php           Order list and order details (status flow + reviews)
├── juakali.php          Juakali jobs + available workers + map
├── uae-jobs.php         UAE jobs
├── job.php              Job details, apply, applicants, reports
├── job-post.php         Post / edit / close / delete jobs
├── my-jobs.php          Employer's jobs
├── applications.php     Worker's applications
├── messages.php         Inbox + chat
├── messages-api.php     JSON endpoint for live chat
├── dashboard.php        Worker / employer dashboard
├── profile.php          Edit profile + change password
├── user.php             Public profile
├── login.php, register.php, logout.php
├── help.php, safety.php, contact.php, search.php
├── setup.php            One-time installer (delete after use)
├── admin/               Admin panel (overview, jobs, users, reports, inbox)
├── includes/            init, database, helpers, layout, shared HTML
├── config/config.php    Your settings
├── database/            schema.sql, demo_data.sql
├── assets/              css, js, favicon
└── uploads/             Profile photos and service images
```

---

## Not included yet

- **Payments.** Orders track status, but money is agreed between users for now. **M-Pesa Daraja (STK Push)** is the natural next step and needs a Safaricom developer account and an HTTPS callback URL.
- **Email.** There are no verification or password-reset emails, because free hosts block outgoing mail. Users request help through the contact form. With an SMTP service (for example Brevo) you could add PHPMailer.
- **Real-time notifications.** Chat refreshes every 5 seconds. Push notifications or WebSockets would need paid hosting.

---

## Customising

- Colours: change the variables at the top of `assets/css/style.css` (`--brand`, `--accent`).
- Categories, counties and emirates: edit the lists in `includes/data.php`.
- Site name and tagline: `config/config.php`.
