# KaziHub

**Find work. Find workers. Find success.**

KaziHub is a job platform for Kenya with three parts:

- **Freelance marketplace.** Workers list services (design, web, writing and more). Clients order them and track each order from start to finish.
- **Juakali jobs.** Local skilled and casual work. Jobs and available workers are matched by GPS ("Near me"), and job sites appear on a map.
- **UAE jobs.** Overseas listings that the admin approves before they go live, with visa and accommodation details and a scam-report button.

**Live site:** https://yomie-commits.github.io/kazihub/

## How it's built

| Part | Technology |
|---|---|
| Pages | HTML, CSS, JavaScript (ES modules), Bootstrap 5, Bootstrap Icons |
| Maps | Leaflet with OpenStreetMap |
| Database, sign-in, photo storage, live chat | [Supabase](https://supabase.com) (free plan), using supabase-js |
| Hosting | GitHub Pages (free; updates on every push) |

There is no server code and no build step. The pages talk to Supabase directly from the browser. Every permission is enforced inside the database by Row Level Security rules in `supabase/schema.sql`, so the public "anon" key in `config.js` is safe to publish.

## Features

| Area | What's included |
|---|---|
| Accounts | Sign up as a worker or an employer, sign in and out, email confirmation, forgot/reset password, change password |
| Profiles | Photo upload, county/town, GPS location (saved to about 1 km), bio, skills, experience, hourly rate, availability switch, profile strength, public profile with reviews |
| Privacy | Phone numbers are visible only to the owner, admins, and employers who accepted that worker |
| Freelance | Create, edit, pause and delete services; search, filter and sort; order flow **pending → in progress → delivered → completed** (with revisions); buyer reviews |
| Juakali | Post jobs with GPS location and urgency; "Near me" sorting by distance; map view; "Available workers" list; apply with a cover letter and price |
| UAE jobs | Emirate, company, visa and accommodation fields; new and edited listings wait for admin approval; safety warnings; report button |
| Hiring | Applicants list; shortlist, accept or reject; worker's phone revealed on acceptance; employer reviews worker |
| Messaging | Inbox with unread counts and **live** chat (Supabase Realtime); automatic messages when orders or applications change |
| Dashboards | Separate worker and employer dashboards with stats, recent activity and recommended jobs (skills first, nearest first) |
| Admin | Stats, approve or reject jobs, verify or suspend users, scam reports, contact-form inbox |
| Pages | Home, help centre, safety tips, contact form, 404 page |

---

## Setup (about 15 minutes, all free)

### 1. Create the Supabase project
1. Sign up at **https://supabase.com** ("Continue with GitHub" is easiest).
2. Click **New project**. Name it `kazihub`, choose a strong database password, and pick a region close to Kenya (for example **Frankfurt** or **South Africa** if offered).

### 2. Create the database
1. In your project, open **SQL Editor → New query**.
2. Paste the whole of [`supabase/schema.sql`](supabase/schema.sql) and click **Run**.
3. Optional demo data: open a new query, paste [`supabase/demo_data.sql`](supabase/demo_data.sql) and click **Run**. Every demo account uses the password `password123` (for example `brian@demo.kazihub` or `sarah@demo.kazihub`).

### 3. Connect the website
1. In Supabase, open **Project Settings → API** (or click **Connect**). Copy the **Project URL** and the **anon public** key.
2. Put them in [`assets/js/config.js`](assets/js/config.js) and commit.

### 4. Allow sign-in links back to your site
In Supabase, open **Authentication → URL Configuration**:
- **Site URL:** `https://yomie-commits.github.io/kazihub/`
- **Redirect URLs:** add `https://yomie-commits.github.io/kazihub/**`

Supabase sends confirmation and password-reset emails itself. The free plan sends only a few per hour; if that's too slow while testing, turn off **Authentication → Sign In / Providers → Email → Confirm email**.

### 5. Turn on GitHub Pages
In this repo, open **Settings → Pages**. Under **Source**, choose **Deploy from a branch**, then **main** and **/ (root)**. The site appears at the address above within a minute or two.

### 6. Make yourself admin
Sign up on the live site, then run this in the Supabase SQL Editor, using your own email:

```sql
update public.profiles set role = 'admin', is_verified = true
where id = (select id from auth.users where email = 'you@example.com');
```

### Removing the demo data before launch

```sql
delete from auth.users where id::text like '00000000-0000-4000-8000-%';
```

---

## Folder structure

```
├── *.html                 One file per page (index, services, juakali, job, messages, admin…)
├── assets/
│   ├── css/style.css      KaziHub theme on top of Bootstrap
│   ├── js/config.js       Your Supabase URL + anon key
│   ├── js/app.js          Shared code: Supabase client, header/footer, sign-in, helpers, cards
│   ├── js/data.js         Categories, counties, emirates
│   └── js/pages/*.js      One script per page
└── supabase/
    ├── schema.sql         Tables, security rules, functions, storage, realtime
    └── demo_data.sql      Optional demo users, services and jobs
```

## Free-plan notes

- On the free plan, Supabase **pauses a project after a week with no visits**. Open the Supabase dashboard and click **Restore** to wake it.
- Free limits (500 MB database, 1 GB file storage, 50,000 monthly users) are far more than KaziHub needs to start.

## Not included yet

- **Payments.** Orders track status, but money is agreed between users. **M-Pesa (Daraja STK Push)** would be the next step, using a Supabase Edge Function.
- **Email or SMS notifications** for new messages. Supabase Edge Functions can add these.

## Older version

The earlier PHP/MySQL version (for hosts like InfinityFree) is saved in the [`php-version`](../../tree/php-version) branch.
