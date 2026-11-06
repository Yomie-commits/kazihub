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

## Why GitHub Pages shows only HTML

GitHub Pages can only serve static files. It cannot run PHP or MySQL, so KaziHub will never work there. Use GitHub to store the code and a PHP host such as InfinityFree to run the site. You can switch Pages off under **Settings → Pages**.

## Install on InfinityFree with automatic deploys (recommended)

Once this is set up, every push to `main` uploads the site to InfinityFree automatically, through GitHub Actions (`.github/workflows/deploy.yml`).

1. **Create the hosting account and database.** In InfinityFree, create an account (for example `kazihub.great-site.net`), then open **MySQL Databases** and create a database. Note:
   - the **FTP details** (Account → FTP Details): hostname `ftpupload.net`, username `if0_…`, password
   - the **MySQL details**: host `sql…infinityfree.com`, database name, username, password
2. **Add the details to GitHub as secrets.** In the repo, open **Settings → Secrets and variables → Actions → New repository secret** and add:

   | Secret | Example |
   |---|---|
   | `FTP_SERVER` | `ftpupload.net` |
   | `FTP_USERNAME` | `if0_12345678` |
   | `FTP_PASSWORD` | your InfinityFree account password |
   | `DB_HOST` | `sql123.infinityfree.com` |
   | `DB_NAME` | `if0_12345678_kazihub` |
   | `DB_USER` | `if0_12345678` |
   | `DB_PASS` | your database password |

   Passwords stay in GitHub's encrypted secrets. They are written to `config/config.local.php` on the server only, never into the public code.
3. **Deploy.** Go to **Actions → Deploy to hosting → Run workflow**, or just push a change. The first upload takes a few minutes.
4. **Create the tables and the admin account.** Open `https://your-site/setup.php`, click **Create tables** (tick the box for demo data if you want it), then create your admin account. After that, `setup.php` locks itself.
5. **Turn on the free SSL certificate** in InfinityFree. The "Use my location" and "Near me" buttons only work on `https://`.

## Install by hand (FileZilla / File Manager)

1. Create the database as in step 1 above.
2. Upload everything into `htdocs`.
3. Copy `config/config.local.example.php` to `config/config.local.php` and fill in your database details.
4. Open `setup.php` as in step 4 above.

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

Copy the folder into `htdocs` and create a `kazihub` database in phpMyAdmin. The default settings (`localhost`, `root`, no password) already match XAMPP. Then open `http://localhost/kazihub/setup.php`.

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
├── config/config.php    App settings (no passwords)
├── config/config.local.php  Database passwords (created on the server, not in git)
├── .github/workflows/   Automatic deploy to InfinityFree
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
