-- KaziHub database schema + sample data
-- Import this file in phpMyAdmin (InfinityFree: Control Panel > MySQL Databases > phpMyAdmin > Import)
-- Works on MySQL 5.7+ / MariaDB 10.3+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS saved_jobs;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('freelancer','client','job_seeker','admin') NOT NULL DEFAULT 'job_seeker',
  phone VARCHAR(30) DEFAULT NULL,
  location VARCHAR(150) DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  headline VARCHAR(150) DEFAULT NULL,
  bio TEXT,
  avatar VARCHAR(255) DEFAULT NULL,
  skills VARCHAR(500) DEFAULT NULL,
  experience VARCHAR(50) DEFAULT NULL,
  hourly_rate DECIMAL(10,2) DEFAULT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  company_size VARCHAR(30) DEFAULT NULL,
  industry VARCHAR(100) DEFAULT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_available TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  last_seen DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  delivery_days INT NOT NULL DEFAULT 3,
  image VARCHAR(255) DEFAULT NULL,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  type ENUM('freelance','juakali','uae') NOT NULL,
  title VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(150) DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  budget_min DECIMAL(12,2) DEFAULT NULL,
  budget_max DECIMAL(12,2) DEFAULT NULL,
  pay_period ENUM('fixed','hourly','daily','monthly') NOT NULL DEFAULT 'fixed',
  currency VARCHAR(3) NOT NULL DEFAULT 'KES',
  employer_name VARCHAR(150) DEFAULT NULL,
  visa_support TINYINT(1) NOT NULL DEFAULT 0,
  accommodation TINYINT(1) NOT NULL DEFAULT 0,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('open','closed','filled') NOT NULL DEFAULT 'open',
  deadline DATE DEFAULT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  cover_letter TEXT NOT NULL,
  proposed_rate DECIMAL(12,2) DEFAULT NULL,
  status ENUM('pending','shortlisted','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_application (job_id, user_id),
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE saved_jobs (
  user_id INT UNSIGNED NOT NULL,
  job_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, job_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id INT UNSIGNED NOT NULL,
  receiver_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reviewer_id INT UNSIGNED NOT NULL,
  reviewee_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_review (reviewer_id, reviewee_id),
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_jobs_type_status ON jobs (type, status);
CREATE INDEX idx_messages_receiver ON messages (receiver_id, is_read);
CREATE INDEX idx_services_category ON services (category);

-- ---------------------------------------------------------------
-- Sample data
-- Admin login:  admin@kazihub.test / Admin@123   (CHANGE THIS after first login)
-- Demo users:   any of the emails below / Password@123
-- ---------------------------------------------------------------

INSERT INTO users (id, name, email, password_hash, role, phone, location, latitude, longitude, headline, bio, skills, experience, hourly_rate, company_name, company_size, industry, is_verified, is_available, created_at) VALUES
(1, 'KaziHub Admin', 'admin@kazihub.test', '$2y$10$l75C2bA1vJu3kQU5jL61nuF92dg4o814vgQpes73qedju0HQ0f/c2', 'admin', NULL, 'Nairobi', NULL, NULL, 'Platform administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-01-01 08:00:00'),
(2, 'Amina Wanjiru', 'amina@kazihub.test', '$2y$10$00z7SFhOcXXxyetdCvrDmuZJfAN2he.hLvqdBde6m6nmFh9P9s59u', 'freelancer', '+254700111222', 'Westlands, Nairobi', -1.2676000, 36.8108000, 'Brand & logo designer', 'I help small businesses look professional with clean logos, brand kits and social media graphics.', 'Logo Design, Branding, Canva, Illustrator', '3-5 years', 1500.00, NULL, NULL, NULL, 1, 1, '2026-02-10 09:00:00'),
(3, 'Brian Otieno', 'brian@kazihub.test', '$2y$10$00z7SFhOcXXxyetdCvrDmuZJfAN2he.hLvqdBde6m6nmFh9P9s59u', 'freelancer', '+254711333444', 'Kisumu', -0.0917000, 34.7680000, 'Full-stack web developer', 'PHP, Laravel and JavaScript developer building fast websites and M-Pesa integrations.', 'PHP, JavaScript, MySQL, M-Pesa API, WordPress', '5+ years', 2500.00, NULL, NULL, NULL, 1, 1, '2026-02-15 10:00:00'),
(4, 'Joseph Kamau', 'joseph@kazihub.test', '$2y$10$00z7SFhOcXXxyetdCvrDmuZJfAN2he.hLvqdBde6m6nmFh9P9s59u', 'job_seeker', '+254722555666', 'Kariobangi, Nairobi', -1.2525000, 36.8800000, 'Fundi wa umeme (Electrician)', 'Certified electrician with 7 years of house wiring, solar installation and repairs.', 'Electrical Wiring, Solar Installation, Plumbing', '5+ years', 800.00, NULL, NULL, NULL, 1, 1, '2026-03-01 07:30:00'),
(5, 'Grace Achieng', 'grace@kazihub.test', '$2y$10$00z7SFhOcXXxyetdCvrDmuZJfAN2he.hLvqdBde6m6nmFh9P9s59u', 'job_seeker', '+254733777888', 'Rongai', -1.3960000, 36.7570000, 'Housekeeper & caregiver', 'Reliable housekeeper with experience in childcare and elderly care. Open to UAE opportunities.', 'Housekeeping, Childcare, Cooking, Elderly Care', '3-5 years', 500.00, NULL, NULL, NULL, 0, 1, '2026-03-05 11:00:00'),
(6, 'Savanna Tech Ltd', 'hr@savanna.test', '$2y$10$00z7SFhOcXXxyetdCvrDmuZJfAN2he.hLvqdBde6m6nmFh9P9s59u', 'client', '+254744999000', 'Upper Hill, Nairobi', -1.2990000, 36.8150000, 'Software & digital agency', 'We build digital products for East African businesses.', NULL, NULL, NULL, 'Savanna Tech Ltd', '11-50', 'Technology', 1, 0, '2026-01-20 12:00:00'),
(7, 'Gulf Careers Recruitment', 'jobs@gulfcareers.test', '$2y$10$00z7SFhOcXXxyetdCvrDmuZJfAN2he.hLvqdBde6m6nmFh9P9s59u', 'client', '+254755123123', 'Nairobi CBD', -1.2864000, 36.8172000, 'Licensed UAE recruitment agency', 'NEA-licensed agency placing Kenyan workers with verified UAE employers.', NULL, NULL, NULL, 'Gulf Careers Recruitment', '11-50', 'Recruitment', 1, 0, '2026-01-25 12:00:00');

INSERT INTO services (user_id, title, category, description, price, delivery_days, status, created_at) VALUES
(2, 'I will design a modern logo for your business', 'Graphics & Design', 'You get 3 logo concepts, unlimited revisions and all source files (PNG, SVG, PDF).', 3500.00, 3, 'active', '2026-02-11 09:00:00'),
(2, 'I will create 10 social media posts for your brand', 'Digital Marketing', 'Branded Instagram/Facebook post designs with captions, ready to schedule.', 4000.00, 4, 'active', '2026-02-12 09:00:00'),
(3, 'I will build a responsive business website', 'Programming & Tech', 'A fast 5-page website with contact form, WhatsApp button and basic SEO.', 25000.00, 10, 'active', '2026-02-16 09:00:00'),
(3, 'I will integrate M-Pesa STK Push into your site', 'Programming & Tech', 'Daraja API integration for PHP or WordPress, tested in sandbox and live.', 12000.00, 5, 'active', '2026-02-17 09:00:00');

INSERT INTO jobs (client_id, type, title, category, description, location, latitude, longitude, budget_min, budget_max, pay_period, currency, employer_name, visa_support, accommodation, is_verified, status, deadline, created_at) VALUES
(6, 'freelance', 'Redesign our company website', 'Programming & Tech', 'We need a modern, mobile-friendly redesign of our 8-page website. Bootstrap or Tailwind preferred.', 'Remote', NULL, NULL, 40000.00, 70000.00, 'fixed', 'KES', NULL, 0, 0, 1, 'open', '2026-12-15', '2026-10-20 10:00:00'),
(6, 'freelance', 'Social media manager (part-time)', 'Digital Marketing', 'Manage Instagram, TikTok and LinkedIn for our brand: 4 posts a week plus monthly reporting.', 'Remote', NULL, NULL, 20000.00, 30000.00, 'monthly', 'KES', NULL, 0, 0, 1, 'open', '2026-12-01', '2026-10-25 10:00:00'),
(6, 'juakali', 'Electrician needed for office rewiring', 'Electrical', 'Rewire a 3-room office and install 6 new sockets. Must bring own tools.', 'Upper Hill, Nairobi', -1.2990000, 36.8150000, 6000.00, 10000.00, 'fixed', 'KES', NULL, 0, 0, 1, 'open', '2026-11-20', '2026-10-28 08:00:00'),
(6, 'juakali', 'Plumber to fix leaking pipes', 'Plumbing', 'Kitchen and bathroom pipes leaking. Same-day job.', 'Kilimani, Nairobi', -1.2900000, 36.7850000, 1500.00, 3000.00, 'fixed', 'KES', NULL, 0, 0, 1, 'open', '2026-11-10', '2026-11-01 08:00:00'),
(6, 'juakali', 'Mama fua (laundry) twice a week', 'Cleaning', 'Laundry and ironing for a family of four, Tuesdays and Fridays.', 'Rongai', -1.3960000, 36.7600000, 800.00, 1000.00, 'daily', 'KES', NULL, 0, 0, 1, 'open', NULL, '2026-11-02 08:00:00'),
(7, 'uae', 'Housekeepers for Dubai hotels', 'Hospitality', '2-year contract with a 4-star hotel group. Training provided. Female and male candidates welcome.', 'Dubai, UAE', NULL, NULL, 1800.00, 2200.00, 'monthly', 'AED', 'Al Noor Hospitality Group', 1, 1, 1, 'open', '2026-12-31', '2026-10-15 09:00:00'),
(7, 'uae', 'Security guards - Abu Dhabi', 'Security', 'Licensed security guards for malls and residential towers. Minimum height 5ft 8in, good English.', 'Abu Dhabi, UAE', NULL, NULL, 2500.00, 3000.00, 'monthly', 'AED', 'Emirates Guard Services', 1, 1, 1, 'open', '2026-12-20', '2026-10-18 09:00:00'),
(7, 'uae', 'Heavy truck drivers', 'Driving', 'UAE or GCC heavy vehicle licence preferred; Kenyan licence holders will be trained to convert.', 'Sharjah, UAE', NULL, NULL, 3500.00, 4500.00, 'monthly', 'AED', 'Sharjah Logistics LLC', 1, 1, 0, 'open', '2026-12-30', '2026-11-01 09:00:00');

INSERT INTO applications (job_id, user_id, cover_letter, proposed_rate, status, created_at) VALUES
(1, 3, 'I have redesigned 20+ company websites with Bootstrap. Portfolio available on request.', 55000.00, 'shortlisted', '2026-10-21 12:00:00'),
(3, 4, 'Nimefanya kazi ya wiring kwa miaka 7. I can start tomorrow.', 8000.00, 'accepted', '2026-10-28 15:00:00'),
(6, 5, 'I have 4 years of housekeeping experience and a valid passport.', NULL, 'pending', '2026-10-16 10:00:00');

INSERT INTO messages (sender_id, receiver_id, body, is_read, created_at) VALUES
(6, 4, 'Hi Joseph, can you come on Saturday at 9am for the rewiring job?', 1, '2026-10-29 09:00:00'),
(4, 6, 'Yes, Saturday 9am works. I will bring all tools.', 0, '2026-10-29 09:20:00');

INSERT INTO reviews (reviewer_id, reviewee_id, rating, comment, created_at) VALUES
(6, 4, 5, 'Very professional, finished the wiring on time. Highly recommended.', '2026-11-02 18:00:00'),
(6, 2, 4, 'Great logo concepts, quick revisions.', '2026-03-01 18:00:00');
