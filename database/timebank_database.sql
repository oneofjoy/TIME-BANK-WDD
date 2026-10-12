-- TimeBank | Database schema for MySQL 8+ / MariaDB (XAMPP)
-- Frontend: HTML, CSS, JavaScript | Backend: PHP (planned)
-- One member account can BOTH learn and teach. Admin is a separate access role.
-- 1 hour teaching = 1 time credit (application rule).
-- Safe to import in phpMyAdmin. Does NOT insert sample users/passwords.

CREATE DATABASE IF NOT EXISTS timebank_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE timebank_db;

-- 1. Accounts (member is both learner and teacher)
CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
    account_status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Member public profile
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    location VARCHAR(120) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Skill categories
CREATE TABLE IF NOT EXISTS skill_categories (
    category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 4. Master catalog of skills
CREATE TABLE IF NOT EXISTS skills (
    skill_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    CONSTRAINT fk_skill_category
        FOREIGN KEY (category_id) REFERENCES skill_categories(category_id)
        ON DELETE RESTRICT,
    INDEX idx_skills_category (category_id)
) ENGINE=InnoDB;

-- 5. Offers posted by members who want to teach
CREATE TABLE IF NOT EXISTS skill_offerings (
    offering_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    skill_level ENUM('beginner', 'intermediate', 'advanced', 'all_levels')
        NOT NULL DEFAULT 'all_levels',
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    credit_cost DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    verification_status ENUM('unverified', 'pending', 'verified', 'rejected')
        NOT NULL DEFAULT 'unverified',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_offering_tutor
        FOREIGN KEY (tutor_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_offering_skill
        FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE RESTRICT,
    CONSTRAINT chk_offering_duration CHECK (duration_minutes > 0),
    CONSTRAINT chk_offering_credit CHECK (credit_cost > 0),
    INDEX idx_offerings_tutor (tutor_id),
    INDEX idx_offerings_skill_active (skill_id, is_active)
) ENGINE=InnoDB;

-- 6. Available tutoring time slots
CREATE TABLE IF NOT EXISTS availability (
    availability_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id BIGINT UNSIGNED NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    slot_status ENUM('available', 'reserved', 'blocked')
        NOT NULL DEFAULT 'available',
    CONSTRAINT fk_availability_offering
        FOREIGN KEY (offering_id) REFERENCES skill_offerings(offering_id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_availability_time CHECK (end_at > start_at),
    UNIQUE KEY uq_offering_slot (offering_id, start_at, end_at),
    INDEX idx_availability_status (slot_status, start_at)
) ENGINE=InnoDB;

-- 7. Booking requests (teacher is derived from skill_offerings.tutor_id)
CREATE TABLE IF NOT EXISTS bookings (
    booking_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id BIGINT UNSIGNED NOT NULL,
    availability_id BIGINT UNSIGNED NOT NULL,
    learner_id BIGINT UNSIGNED NOT NULL,
    credits_used DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    booking_status ENUM('pending', 'accepted', 'rejected', 'cancelled', 'completed')
        NOT NULL DEFAULT 'pending',
    escrow_status ENUM('not_held', 'held', 'released', 'refunded')
        NOT NULL DEFAULT 'not_held',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_offering
        FOREIGN KEY (offering_id) REFERENCES skill_offerings(offering_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_booking_availability
        FOREIGN KEY (availability_id) REFERENCES availability(availability_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_booking_learner
        FOREIGN KEY (learner_id) REFERENCES users(user_id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_booking_credits CHECK (credits_used > 0),
    INDEX idx_bookings_learner (learner_id, booking_status),
    INDEX idx_bookings_offering (offering_id, booking_status),
    INDEX idx_bookings_availability (availability_id)
) ENGINE=InnoDB;

-- 8. Actual learning sessions (one session per approved booking)
CREATE TABLE IF NOT EXISTS sessions (
    session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL UNIQUE,
    meeting_url VARCHAR(500) NULL,
    session_status ENUM('scheduled', 'in_progress', 'completed', 'cancelled')
        NOT NULL DEFAULT 'scheduled',
    started_at DATETIME NULL,
    ended_at DATETIME NULL,
    learner_confirmed_at DATETIME NULL,
    tutor_confirmed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_session_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 9. Rating/review after a session
CREATE TABLE IF NOT EXISTS reviews (
    review_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    reviewee_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_session
        FOREIGN KEY (session_id) REFERENCES sessions(session_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_review_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_review_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_session_reviewer (session_id, reviewer_id),
    INDEX idx_reviews_reviewee (reviewee_id)
) ENGINE=InnoDB;

-- 10. Smart Wallet per member. Default 0 until a credit policy is decided.
CREATE TABLE IF NOT EXISTS wallets (
    wallet_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    available_credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    held_credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT chk_wallet_available CHECK (available_credits >= 0),
    CONSTRAINT chk_wallet_held CHECK (held_credits >= 0)
) ENGINE=InnoDB;

-- 11. Wallet ledger: signed deltas make escrow and transfers auditable.
-- Example hold: (-1 available, +1 held); refund: (+1 available, -1 held)
-- On completion: learner (0 available, -1 held), tutor (+1 available, 0 held)
CREATE TABLE IF NOT EXISTS credit_transactions (
    transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NULL,
    transaction_type ENUM('hold', 'settle', 'earn', 'refund', 'bonus', 'adjustment')
        NOT NULL,
    available_delta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    held_delta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaction_wallet
        FOREIGN KEY (wallet_id) REFERENCES wallets(wallet_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_transaction_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
        ON DELETE RESTRICT,
    INDEX idx_transactions_wallet_date (wallet_id, created_at),
    INDEX idx_transactions_booking (booking_id)
) ENGINE=InnoDB;

-- 12. Session learning materials (multiple uploads per session)
CREATE TABLE IF NOT EXISTS session_materials (
    material_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_session
        FOREIGN KEY (session_id) REFERENCES sessions(session_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_material_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
        ON DELETE RESTRICT,
    INDEX idx_materials_session (session_id)
) ENGINE=InnoDB;

-- Initial categories for the Homepage / Explore Skills UI
INSERT IGNORE INTO skill_categories (name, slug) VALUES
('Programming', 'programming'),
('Design', 'design'),
('Languages', 'languages'),
('Data Analysis', 'data-analysis'),
('Business', 'business'),
('Other Skills', 'other-skills');

-- Initial skill examples; no fake tutors or users
INSERT IGNORE INTO skills (category_id, name, slug, description)
SELECT category_id, 'HTML & CSS', 'html-css', 'Basic web page development'
FROM skill_categories WHERE slug = 'programming';

INSERT IGNORE INTO skills (category_id, name, slug, description)
SELECT category_id, 'JavaScript', 'javascript', 'Frontend JavaScript fundamentals'
FROM skill_categories WHERE slug = 'programming';

INSERT IGNORE INTO skills (category_id, name, slug, description)
SELECT category_id, 'UI/UX Design', 'ui-ux-design', 'Designing user-friendly interfaces'
FROM skill_categories WHERE slug = 'design';

INSERT IGNORE INTO skills (category_id, name, slug, description)
SELECT category_id, 'English Conversation', 'english-conversation', 'English speaking practice'
FROM skill_categories WHERE slug = 'languages';

INSERT IGNORE INTO skills (category_id, name, slug, description)
SELECT category_id, 'Python for Data Analysis', 'python-data-analysis', 'Data analysis with Python'
FROM skill_categories WHERE slug = 'data-analysis';

INSERT IGNORE INTO skills (category_id, name, slug, description)
SELECT category_id, 'Digital Marketing', 'digital-marketing', 'Fundamentals of online marketing'
FROM skill_categories WHERE slug = 'business';
