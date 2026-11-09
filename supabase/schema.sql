-- =====================================================================
-- KaziHub database for Supabase
-- Paste this whole file into Supabase -> SQL Editor -> New query -> Run.
-- Safe to run on a new, empty project.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Tables
-- ---------------------------------------------------------------------

create table public.profiles (
    id               uuid primary key references auth.users (id) on delete cascade,
    name             text not null check (char_length(name) between 2 and 120),
    role             text not null default 'worker' check (role in ('worker', 'employer', 'admin')),
    county           text check (char_length(county) <= 60),
    town             text check (char_length(town) <= 100),
    latitude         numeric(9, 6) check (latitude between -90 and 90),
    longitude        numeric(9, 6) check (longitude between -180 and 180),
    bio              text check (char_length(bio) <= 1500),
    skills           text check (char_length(skills) <= 600),
    experience_years int check (experience_years between 0 and 60),
    hourly_rate      numeric(10, 2) check (hourly_rate >= 0),
    company_name     text check (char_length(company_name) <= 150),
    avatar_url       text,
    is_verified      boolean not null default false,
    is_available     boolean not null default true,
    status           text not null default 'active' check (status in ('active', 'suspended')),
    last_seen        timestamptz default now(),
    created_at       timestamptz not null default now()
);

-- Phone numbers are kept apart so they are only visible to the owner,
-- admins, and employers who have accepted that worker for a job.
create table public.profile_private (
    id    uuid primary key references public.profiles (id) on delete cascade,
    phone text check (phone ~ '^\+?[0-9 ]{9,16}$')
);

create table public.services (
    id            bigint generated always as identity primary key,
    user_id       uuid not null default auth.uid() references public.profiles (id) on delete cascade,
    title         text not null check (char_length(title) between 10 and 150),
    category      text not null check (char_length(category) <= 60),
    description   text not null check (char_length(description) between 50 and 5000),
    price         numeric(10, 2) not null check (price between 100 and 10000000),
    delivery_days int not null default 3 check (delivery_days between 1 and 90),
    image_url     text,
    is_active     boolean not null default true,
    created_at    timestamptz not null default now()
);

create table public.orders (
    id           bigint generated always as identity primary key,
    service_id   bigint references public.services (id) on delete set null,
    buyer_id     uuid not null references public.profiles (id) on delete cascade,
    seller_id    uuid not null references public.profiles (id) on delete cascade,
    title        text not null,
    price        numeric(10, 2) not null,
    requirements text check (char_length(requirements) <= 3000),
    status       text not null default 'pending'
                 check (status in ('pending', 'in_progress', 'delivered', 'completed', 'cancelled')),
    created_at   timestamptz not null default now(),
    updated_at   timestamptz not null default now()
);

create table public.jobs (
    id              bigint generated always as identity primary key,
    employer_id     uuid not null default auth.uid() references public.profiles (id) on delete cascade,
    type            text not null check (type in ('juakali', 'uae')),
    title           text not null check (char_length(title) between 5 and 150),
    category        text not null check (char_length(category) <= 60),
    description     text not null check (char_length(description) between 30 and 5000),
    county          text not null check (char_length(county) <= 60),
    town            text check (char_length(town) <= 100),
    latitude        numeric(9, 6) check (latitude between -90 and 90),
    longitude       numeric(9, 6) check (longitude between -180 and 180),
    budget_min      numeric(12, 2) check (budget_min >= 0),
    budget_max      numeric(12, 2) check (budget_max >= 0),
    currency        text not null default 'KES',
    pay_period      text not null default 'fixed' check (pay_period in ('fixed', 'daily', 'monthly')),
    urgency         text not null default 'normal' check (urgency in ('normal', 'urgent')),
    company         text check (char_length(company) <= 150),
    visa_sponsored  boolean not null default false,
    accommodation   boolean not null default false,
    positions       int not null default 1 check (positions between 1 and 500),
    deadline        date,
    status          text not null default 'open' check (status in ('pending', 'open', 'closed', 'rejected')),
    applicant_count int not null default 0,
    created_at      timestamptz not null default now(),
    check (budget_max is null or budget_min is null or budget_max >= budget_min)
);

create table public.applications (
    id           bigint generated always as identity primary key,
    job_id       bigint not null references public.jobs (id) on delete cascade,
    worker_id    uuid not null references public.profiles (id) on delete cascade,
    cover_letter text check (char_length(cover_letter) <= 3000),
    expected_pay numeric(12, 2) check (expected_pay >= 0),
    status       text not null default 'pending'
                 check (status in ('pending', 'shortlisted', 'accepted', 'rejected', 'withdrawn')),
    created_at   timestamptz not null default now(),
    unique (job_id, worker_id)
);

create table public.messages (
    id          bigint generated always as identity primary key,
    sender_id   uuid not null default auth.uid() references public.profiles (id) on delete cascade,
    receiver_id uuid not null references public.profiles (id) on delete cascade,
    body        text not null check (char_length(body) between 1 and 4000),
    is_read     boolean not null default false,
    created_at  timestamptz not null default now(),
    check (sender_id <> receiver_id)
);

create table public.reviews (
    id             bigint generated always as identity primary key,
    reviewer_id    uuid not null default auth.uid() references public.profiles (id) on delete cascade,
    reviewee_id    uuid not null references public.profiles (id) on delete cascade,
    order_id       bigint unique references public.orders (id) on delete cascade,
    application_id bigint unique references public.applications (id) on delete cascade,
    rating         int not null check (rating between 1 and 5),
    comment        text check (char_length(comment) <= 1000),
    created_at     timestamptz not null default now(),
    check ((order_id is null) <> (application_id is null))
);

create table public.reports (
    id          bigint generated always as identity primary key,
    reporter_id uuid not null default auth.uid() references public.profiles (id) on delete cascade,
    job_id      bigint not null references public.jobs (id) on delete cascade,
    reason      text not null check (char_length(reason) between 10 and 2000),
    status      text not null default 'new' check (status in ('new', 'reviewed')),
    created_at  timestamptz not null default now()
);

create table public.contact_messages (
    id         bigint generated always as identity primary key,
    name       text not null check (char_length(name) between 2 and 120),
    email      text not null check (email ~* '^[^@\s]+@[^@\s]+\.[^@\s]+$' and char_length(email) <= 190),
    subject    text not null check (char_length(subject) <= 120),
    message    text not null check (char_length(message) between 10 and 5000),
    status     text not null default 'new' check (status in ('new', 'reviewed')),
    created_at timestamptz not null default now()
);

create index on public.services (user_id);
create index on public.services (category);
create index on public.jobs (type, status);
create index on public.jobs (employer_id);
create index on public.orders (buyer_id);
create index on public.orders (seller_id);
create index on public.applications (worker_id);
create index on public.messages (receiver_id, is_read);
create index on public.messages (sender_id, receiver_id);
create index on public.reviews (reviewee_id);

-- Average rating per user (respects the reviews table's permissions)
create view public.user_ratings with (security_invoker = on) as
    select reviewee_id, round(avg(rating)::numeric, 1) as rating, count(*)::int as review_count
    from public.reviews group by reviewee_id;

-- ---------------------------------------------------------------------
-- 2. Helper functions
-- ---------------------------------------------------------------------

create function public.is_admin() returns boolean
language sql stable security definer set search_path = public as $$
    select exists (select 1 from profiles where id = auth.uid() and role = 'admin' and status = 'active');
$$;

create function public.is_active_user() returns boolean
language sql stable security definer set search_path = public as $$
    select exists (select 1 from profiles where id = auth.uid() and status = 'active');
$$;

create function public.my_role() returns text
language sql stable security definer set search_path = public as $$
    select role from profiles where id = auth.uid();
$$;

-- Cross-table checks used by the security rules (avoids policy loops)
create function public.i_applied(p_job bigint) returns boolean
language sql stable security definer set search_path = public as $$
    select exists (select 1 from applications where job_id = p_job and worker_id = auth.uid());
$$;

create function public.i_own_job(p_job bigint) returns boolean
language sql stable security definer set search_path = public as $$
    select exists (select 1 from jobs where id = p_job and employer_id = auth.uid());
$$;

create function public.i_hired(p_worker uuid) returns boolean
language sql stable security definer set search_path = public as $$
    select exists (select 1 from applications a join jobs j on j.id = a.job_id
                   where a.worker_id = p_worker and a.status = 'accepted' and j.employer_id = auth.uid());
$$;

create function public.can_review(p_order bigint, p_application bigint, p_reviewee uuid) returns boolean
language sql stable security definer set search_path = public as $$
    select case
        when p_order is not null then exists (
            select 1 from orders o where o.id = p_order and o.buyer_id = auth.uid()
              and o.seller_id = p_reviewee and o.status = 'completed')
        else exists (
            select 1 from applications a join jobs j on j.id = a.job_id
            where a.id = p_application and a.status = 'accepted'
              and a.worker_id = p_reviewee and j.employer_id = auth.uid())
    end;
$$;

-- Internal: send an automatic message (not callable from the website)
create function public.notify_user(p_from uuid, p_to uuid, p_body text) returns void
language plpgsql security definer set search_path = public as $$
begin
    if p_from is not null and p_to is not null and p_from <> p_to then
        insert into messages (sender_id, receiver_id, body) values (p_from, p_to, left(p_body, 4000));
    end if;
end $$;

-- ---------------------------------------------------------------------
-- 3. Triggers
-- ---------------------------------------------------------------------

-- Create a profile automatically when someone signs up
create function public.handle_new_user() returns trigger
language plpgsql security definer set search_path = public as $$
declare
    v_role  text := coalesce(new.raw_user_meta_data ->> 'role', 'worker');
    v_name  text := coalesce(nullif(trim(new.raw_user_meta_data ->> 'name'), ''), split_part(new.email, '@', 1));
    v_phone text := nullif(trim(new.raw_user_meta_data ->> 'phone'), '');
begin
    if v_role not in ('worker', 'employer') then
        v_role := 'worker';   -- nobody can sign up as admin
    end if;
    if char_length(v_name) < 2 then
        v_name := 'KaziHub user';
    end if;
    insert into profiles (id, name, role) values (new.id, left(v_name, 120), v_role);
    insert into profile_private (id, phone)
        values (new.id, case when v_phone ~ '^\+?[0-9 ]{9,16}$' then v_phone end);
    return new;
end $$;

create trigger on_auth_user_created
    after insert on auth.users
    for each row execute function public.handle_new_user();

-- Users may edit their own profile, but not their role, verification or status.
-- Location is rounded to about 1 km so exact homes are never exposed.
create function public.profiles_guard() returns trigger
language plpgsql security definer set search_path = public as $$
begin
    if auth.uid() is not null and not is_admin() then
        new.id := old.id;
        new.role := old.role;
        new.is_verified := old.is_verified;
        new.status := old.status;
        new.created_at := old.created_at;
    end if;
    new.latitude := round(new.latitude, 2);
    new.longitude := round(new.longitude, 2);
    return new;
end $$;

create trigger profiles_guard before update on public.profiles
    for each row execute function public.profiles_guard();

-- Jobs: UAE jobs always need admin approval (also after edits).
create function public.jobs_guard() returns trigger
language plpgsql security definer set search_path = public as $$
declare
    v_admin boolean := auth.uid() is null or is_admin()
                       or coalesce(current_setting('kazihub.internal', true), '') = 'on';
begin
    if tg_op = 'UPDATE' then
        new.employer_id := old.employer_id;
        new.type := old.type;
        new.created_at := old.created_at;
        if not v_admin then
            new.applicant_count := old.applicant_count;
        end if;
    end if;

    if new.type = 'uae' then
        new.currency := 'AED';
        new.latitude := null;
        new.longitude := null;
        new.urgency := 'normal';
    else
        new.currency := 'KES';
        new.visa_sponsored := false;
        new.accommodation := false;
        new.latitude := round(new.latitude, 3);
        new.longitude := round(new.longitude, 3);
    end if;

    if not v_admin then
        if tg_op = 'INSERT' then
            new.status := case when new.type = 'uae' then 'pending' else 'open' end;
            new.applicant_count := 0;
        elsif new.status is distinct from old.status then
            if new.status = 'closed' then
                null;                                            -- closing is always allowed
            elsif old.status = 'closed' then
                new.status := case when new.type = 'uae' then 'pending' else 'open' end;  -- reopen
            else
                new.status := old.status;                        -- no self-approval
            end if;
        elsif new.type = 'uae' and old.status <> 'closed' and
              (new.title, new.category, new.description, new.county, new.town, new.budget_min, new.budget_max,
               new.pay_period, new.company, new.visa_sponsored, new.accommodation, new.positions, new.deadline)
              is distinct from
              (old.title, old.category, old.description, old.county, old.town, old.budget_min, old.budget_max,
               old.pay_period, old.company, old.visa_sponsored, old.accommodation, old.positions, old.deadline) then
            new.status := 'pending';                             -- edited UAE jobs are reviewed again
        end if;
    end if;
    return new;
end $$;

create trigger jobs_guard before insert or update on public.jobs
    for each row execute function public.jobs_guard();

-- Keep jobs.applicant_count up to date
create function public.refresh_applicant_count() returns trigger
language plpgsql security definer set search_path = public as $$
declare
    v_job bigint := coalesce(new.job_id, old.job_id);
begin
    perform set_config('kazihub.internal', 'on', true);
    update jobs set applicant_count =
        (select count(*) from applications where job_id = v_job and status <> 'withdrawn')
    where id = v_job;
    perform set_config('kazihub.internal', 'off', true);
    return null;
end $$;

create trigger applications_count after insert or update or delete on public.applications
    for each row execute function public.refresh_applicant_count();

-- ---------------------------------------------------------------------
-- 4. Row Level Security (who can read and change what)
-- ---------------------------------------------------------------------

alter table public.profiles         enable row level security;
alter table public.profile_private  enable row level security;
alter table public.services         enable row level security;
alter table public.orders           enable row level security;
alter table public.jobs             enable row level security;
alter table public.applications     enable row level security;
alter table public.messages         enable row level security;
alter table public.reviews          enable row level security;
alter table public.reports          enable row level security;
alter table public.contact_messages enable row level security;

-- profiles
create policy "profiles are public" on public.profiles for select
    using (status = 'active' or id = auth.uid() or is_admin());
create policy "users edit own profile" on public.profiles for update
    using (id = auth.uid()) with check (id = auth.uid());

-- profile_private (phone)
create policy "phone visible to owner, admins and hiring employers" on public.profile_private for select
    using (id = auth.uid() or is_admin() or i_hired(id));
create policy "users add own phone" on public.profile_private for insert with check (id = auth.uid());
create policy "users edit own phone" on public.profile_private for update
    using (id = auth.uid()) with check (id = auth.uid());

-- services
create policy "active services are public" on public.services for select
    using (is_active or user_id = auth.uid() or is_admin());
create policy "workers create services" on public.services for insert
    with check (user_id = auth.uid() and my_role() = 'worker' and is_active_user());
create policy "owners edit services" on public.services for update
    using (user_id = auth.uid() or is_admin()) with check (user_id = auth.uid() or is_admin());
create policy "owners delete services" on public.services for delete
    using (user_id = auth.uid() or is_admin());

-- orders (created and updated only through the functions below)
create policy "buyer and seller see orders" on public.orders for select
    using (buyer_id = auth.uid() or seller_id = auth.uid() or is_admin());

-- jobs
create policy "open jobs are public" on public.jobs for select
    using (status = 'open' or employer_id = auth.uid() or is_admin() or i_applied(id));
create policy "employers post jobs" on public.jobs for insert
    with check (employer_id = auth.uid() and my_role() in ('employer', 'admin') and is_active_user());
create policy "employers edit own jobs" on public.jobs for update
    using (employer_id = auth.uid() or is_admin()) with check (employer_id = auth.uid() or is_admin());
create policy "employers delete own jobs" on public.jobs for delete
    using (employer_id = auth.uid() or is_admin());

-- applications (created and updated only through the functions below)
create policy "worker, employer and admin see applications" on public.applications for select
    using (worker_id = auth.uid() or is_admin() or i_own_job(job_id));

-- messages
create policy "people see their own messages" on public.messages for select
    using (sender_id = auth.uid() or receiver_id = auth.uid());
create policy "active users send messages" on public.messages for insert
    with check (
        sender_id = auth.uid() and is_active_user()
        and exists (select 1 from profiles p where p.id = receiver_id and p.status = 'active')
    );

-- reviews
create policy "reviews are public" on public.reviews for select using (true);
create policy "buyers and employers leave reviews" on public.reviews for insert
    with check (reviewer_id = auth.uid() and can_review(order_id, application_id, reviewee_id));

-- reports
create policy "users report jobs" on public.reports for insert
    with check (reporter_id = auth.uid() and is_active_user());
create policy "admins read reports" on public.reports for select using (is_admin());
create policy "admins update reports" on public.reports for update using (is_admin());

-- contact form
create policy "anyone can send the contact form" on public.contact_messages for insert
    with check (status = 'new');
create policy "admins read contact form" on public.contact_messages for select using (is_admin());
create policy "admins update contact form" on public.contact_messages for update using (is_admin());
create policy "admins delete contact form" on public.contact_messages for delete using (is_admin());

-- ---------------------------------------------------------------------
-- 5. Functions the website calls
-- ---------------------------------------------------------------------

-- Home page numbers
create function public.public_stats() returns json
language sql stable security definer set search_path = public as $$
    select json_build_object(
        'workers',   (select count(*) from profiles where role = 'worker' and status = 'active'),
        'employers', (select count(*) from profiles where role = 'employer' and status = 'active'),
        'jobs',      (select count(*) from jobs where status = 'open'),
        'services',  (select count(*) from services where is_active)
    );
$$;

-- Order a service
create function public.place_order(p_service_id bigint, p_requirements text) returns bigint
language plpgsql security definer set search_path = public as $$
declare
    v_service services;
    v_id bigint;
begin
    if not is_active_user() then raise exception 'Please sign in to order.'; end if;
    select * into v_service from services where id = p_service_id and is_active;
    if not found then raise exception 'This service is not available.'; end if;
    if v_service.user_id = auth.uid() then raise exception 'You cannot order your own service.'; end if;
    if char_length(coalesce(trim(p_requirements), '')) < 10 then
        raise exception 'Please describe what you need (at least 10 characters).';
    end if;
    insert into orders (service_id, buyer_id, seller_id, title, price, requirements)
        values (v_service.id, auth.uid(), v_service.user_id, v_service.title, v_service.price, left(trim(p_requirements), 3000))
        returning id into v_id;
    perform notify_user(auth.uid(), v_service.user_id,
        format('New order #%s for "%s":' || chr(10) || chr(10) || '%s', v_id, v_service.title, left(trim(p_requirements), 3000)));
    return v_id;
end $$;

-- Move an order forward: accept, decline, deliver, complete, revision, cancel
create function public.order_action(p_order_id bigint, p_action text) returns text
language plpgsql security definer set search_path = public as $$
declare
    v_order orders;
    v_role text;
    v_from text[];
    v_to text;
    v_who text;
begin
    select * into v_order from orders where id = p_order_id;
    if not found or auth.uid() not in (v_order.buyer_id, v_order.seller_id) then
        raise exception 'Order not found.';
    end if;
    v_role := case when auth.uid() = v_order.buyer_id then 'buyer' else 'seller' end;
    case p_action
        when 'accept'   then v_who := 'seller'; v_from := array['pending'];     v_to := 'in_progress';
        when 'decline'  then v_who := 'seller'; v_from := array['pending'];     v_to := 'cancelled';
        when 'deliver'  then v_who := 'seller'; v_from := array['in_progress']; v_to := 'delivered';
        when 'complete' then v_who := 'buyer';  v_from := array['delivered'];   v_to := 'completed';
        when 'revision' then v_who := 'buyer';  v_from := array['delivered'];   v_to := 'in_progress';
        when 'cancel'   then v_who := 'buyer';  v_from := array['pending'];     v_to := 'cancelled';
        else raise exception 'Unknown action.';
    end case;
    if v_role <> v_who or not (v_order.status = any (v_from)) then
        raise exception 'That action is not allowed right now.';
    end if;
    update orders set status = v_to, updated_at = now() where id = p_order_id;
    perform notify_user(auth.uid(),
        case when v_role = 'buyer' then v_order.seller_id else v_order.buyer_id end,
        format('Order #%s ("%s") is now: %s.', v_order.id, v_order.title, replace(v_to, '_', ' ')));
    return v_to;
end $$;

-- Apply for a job
create function public.apply_job(p_job_id bigint, p_cover text, p_expected_pay numeric) returns bigint
language plpgsql security definer set search_path = public as $$
declare
    v_job jobs;
    v_id bigint;
begin
    if not is_active_user() or my_role() <> 'worker' then
        raise exception 'Only worker accounts can apply for jobs.';
    end if;
    select * into v_job from jobs where id = p_job_id;
    if not found or v_job.status <> 'open' or (v_job.deadline is not null and v_job.deadline < current_date) then
        raise exception 'This job is no longer accepting applications.';
    end if;
    if exists (select 1 from applications where job_id = p_job_id and worker_id = auth.uid()) then
        raise exception 'You have already applied for this job.';
    end if;
    if char_length(coalesce(trim(p_cover), '')) < 20 then
        raise exception 'Tell the employer why you are a good fit (at least 20 characters).';
    end if;
    insert into applications (job_id, worker_id, cover_letter, expected_pay)
        values (p_job_id, auth.uid(), left(trim(p_cover), 3000), case when p_expected_pay >= 0 then p_expected_pay end)
        returning id into v_id;
    perform notify_user(auth.uid(), v_job.employer_id,
        format('I just applied for your job "%s".' || chr(10) || chr(10) || '%s', v_job.title, left(trim(p_cover), 3000)));
    return v_id;
end $$;

-- Change an application: withdraw (worker) or shortlist / accept / reject / pending (employer, admin)
create function public.application_action(p_application_id bigint, p_action text) returns text
language plpgsql security definer set search_path = public as $$
declare
    v_app applications;
    v_job jobs;
begin
    select * into v_app from applications where id = p_application_id;
    if not found then raise exception 'Application not found.'; end if;
    select * into v_job from jobs where id = v_app.job_id;

    if p_action = 'withdraw' then
        if v_app.worker_id <> auth.uid() or v_app.status not in ('pending', 'shortlisted') then
            raise exception 'This application can no longer be withdrawn.';
        end if;
        update applications set status = 'withdrawn' where id = v_app.id;
        return 'withdrawn';
    end if;

    if not (v_job.employer_id = auth.uid() or is_admin()) then
        raise exception 'Application not found.';
    end if;
    if p_action not in ('shortlisted', 'accepted', 'rejected', 'pending') or v_app.status = 'withdrawn' then
        raise exception 'That action is not allowed.';
    end if;
    update applications set status = p_action where id = v_app.id;
    perform notify_user(auth.uid(), v_app.worker_id, case p_action
        when 'shortlisted' then format('Good news! You have been shortlisted for "%s".', v_job.title)
        when 'accepted' then format('Congratulations! Your application for "%s" has been accepted. Let''s agree on the next steps here.', v_job.title)
        when 'rejected' then format('Thank you for applying for "%s". The employer has chosen other candidates this time.', v_job.title)
        else null end);
    return p_action;
end $$;

-- Mark a conversation as read
create function public.mark_read(p_partner uuid) returns void
language sql security definer set search_path = public as $$
    update messages set is_read = true
    where sender_id = p_partner and receiver_id = auth.uid() and not is_read;
$$;

-- Conversation list for the inbox
create function public.my_conversations()
returns table (partner_id uuid, name text, avatar_url text, last_seen timestamptz,
               last_body text, last_at timestamptz, last_from_me boolean, unread int)
language sql stable security definer set search_path = public as $$
    with mine as (
        select m.*, case when m.sender_id = auth.uid() then m.receiver_id else m.sender_id end as partner
        from messages m where m.sender_id = auth.uid() or m.receiver_id = auth.uid()
    ), last_msg as (
        select distinct on (partner) partner, body, created_at, sender_id = auth.uid() as from_me
        from mine order by partner, id desc
    )
    select l.partner, p.name, p.avatar_url, p.last_seen, l.body, l.created_at, l.from_me,
           (select count(*)::int from mine u where u.partner = l.partner and u.receiver_id = auth.uid() and not u.is_read)
    from last_msg l join profiles p on p.id = l.partner
    order by l.created_at desc
    limit 100;
$$;

-- ----- Admin -----

create function public.admin_stats() returns json
language plpgsql stable security definer set search_path = public as $$
begin
    if not is_admin() then raise exception 'Admins only.'; end if;
    return json_build_object(
        'workers',       (select count(*) from profiles where role = 'worker'),
        'employers',     (select count(*) from profiles where role = 'employer'),
        'open_jobs',     (select count(*) from jobs where status = 'open'),
        'pending_jobs',  (select count(*) from jobs where status = 'pending'),
        'services',      (select count(*) from services where is_active),
        'orders',        (select count(*) from orders),
        'applications',  (select count(*) from applications),
        'new_reports',   (select count(*) from reports where status = 'new'),
        'new_contacts',  (select count(*) from contact_messages where status = 'new')
    );
end $$;

create function public.admin_list_users(p_search text default '', p_role text default '')
returns table (id uuid, name text, email text, role text, county text, is_verified boolean,
               status text, avatar_url text, created_at timestamptz, phone text)
language plpgsql stable security definer set search_path = public as $$
begin
    if not is_admin() then raise exception 'Admins only.'; end if;
    return query
        select p.id, p.name, u.email::text, p.role, p.county, p.is_verified, p.status, p.avatar_url, p.created_at, pp.phone
        from profiles p
        join auth.users u on u.id = p.id
        left join profile_private pp on pp.id = p.id
        where (coalesce(p_role, '') = '' or p.role = p_role)
          and (coalesce(p_search, '') = '' or p.name ilike '%' || p_search || '%'
               or u.email ilike '%' || p_search || '%' or pp.phone ilike '%' || p_search || '%')
        order by p.created_at desc
        limit 200;
end $$;

create function public.admin_set_user(p_user uuid, p_action text) returns void
language plpgsql security definer set search_path = public as $$
declare
    v profiles;
begin
    if not is_admin() then raise exception 'Admins only.'; end if;
    if p_user = auth.uid() then raise exception 'You cannot change your own account here.'; end if;
    select * into v from profiles where id = p_user;
    if not found then raise exception 'User not found.'; end if;
    if p_action = 'verify' then
        update profiles set is_verified = not v.is_verified where id = p_user;
        if not v.is_verified then
            perform notify_user(auth.uid(), p_user, 'Your KaziHub account is now verified. A verified badge will show on your profile.');
        end if;
    elsif p_action = 'suspend' then
        if v.status = 'suspended' then
            update profiles set status = 'active' where id = p_user;
        else
            update profiles set status = 'suspended' where id = p_user;
            update jobs set status = 'closed' where employer_id = p_user and status in ('open', 'pending');
            update services set is_active = false where user_id = p_user;
        end if;
    else
        raise exception 'Unknown action.';
    end if;
end $$;

create function public.admin_set_job_status(p_job bigint, p_action text) returns void
language plpgsql security definer set search_path = public as $$
declare
    v jobs;
    v_status text;
begin
    if not is_admin() then raise exception 'Admins only.'; end if;
    select * into v from jobs where id = p_job;
    if not found then raise exception 'Job not found.'; end if;
    v_status := case p_action when 'approve' then 'open' when 'reject' then 'rejected' when 'close' then 'closed' end;
    if v_status is null then raise exception 'Unknown action.'; end if;
    update jobs set status = v_status where id = p_job;
    perform notify_user(auth.uid(), v.employer_id, case p_action
        when 'approve' then format('Your job "%s" has been approved and is now live on KaziHub.', v.title)
        when 'reject' then format('Your job "%s" was not approved. Please make sure the company details are correct and that workers are not asked to pay any fees, then edit and resubmit.', v.title)
        else format('Your job "%s" was closed by the KaziHub team.', v.title) end);
end $$;

-- ---------------------------------------------------------------------
-- 6. Who may call what
-- ---------------------------------------------------------------------

revoke execute on function public.notify_user(uuid, uuid, text) from public, anon, authenticated;
revoke execute on function public.handle_new_user() from public, anon, authenticated;
revoke execute on function public.profiles_guard() from public, anon, authenticated;
revoke execute on function public.jobs_guard() from public, anon, authenticated;
revoke execute on function public.refresh_applicant_count() from public, anon, authenticated;

revoke execute on function public.place_order(bigint, text) from public, anon;
revoke execute on function public.order_action(bigint, text) from public, anon;
revoke execute on function public.apply_job(bigint, text, numeric) from public, anon;
revoke execute on function public.application_action(bigint, text) from public, anon;
revoke execute on function public.mark_read(uuid) from public, anon;
revoke execute on function public.my_conversations() from public, anon;
revoke execute on function public.admin_stats() from public, anon;
revoke execute on function public.admin_list_users(text, text) from public, anon;
revoke execute on function public.admin_set_user(uuid, text) from public, anon;
revoke execute on function public.admin_set_job_status(bigint, text) from public, anon;

grant execute on function public.public_stats() to anon, authenticated;
grant execute on function public.place_order(bigint, text), public.order_action(bigint, text),
    public.apply_job(bigint, text, numeric), public.application_action(bigint, text),
    public.mark_read(uuid), public.my_conversations(), public.admin_stats(),
    public.admin_list_users(text, text), public.admin_set_user(uuid, text),
    public.admin_set_job_status(bigint, text) to authenticated;

-- Direct table writes are limited to what the policies above allow;
-- orders and applications can only change through the functions.
revoke insert, update, delete on public.orders, public.applications from anon, authenticated;
revoke insert, update, delete on public.profiles from anon;
revoke insert, delete on public.profiles from authenticated;

-- ---------------------------------------------------------------------
-- 7. Live chat and photo storage
-- ---------------------------------------------------------------------

alter publication supabase_realtime add table public.messages;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types) values
    ('avatars',  'avatars',  true, 2097152, array['image/jpeg', 'image/png', 'image/webp', 'image/gif']),
    ('services', 'services', true, 2097152, array['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
on conflict (id) do nothing;

create policy "users upload own images" on storage.objects for insert to authenticated
    with check (bucket_id in ('avatars', 'services') and (storage.foldername(name))[1] = auth.uid()::text);
create policy "users replace own images" on storage.objects for update to authenticated
    using (bucket_id in ('avatars', 'services') and (storage.foldername(name))[1] = auth.uid()::text);
create policy "users delete own images" on storage.objects for delete to authenticated
    using (bucket_id in ('avatars', 'services') and (storage.foldername(name))[1] = auth.uid()::text);

-- =====================================================================
-- Done! To make yourself admin, sign up on the website first, then run:
--   update public.profiles set role = 'admin', is_verified = true
--   where id = (select id from auth.users where email = 'you@example.com');
-- =====================================================================
