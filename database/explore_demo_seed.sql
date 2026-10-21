-- OPTIONAL DEMONSTRATION DATA ONLY. This file is never executed by the app.
-- Run manually in phpMyAdmin after backing up timebank_db.
-- Choose an existing active Member who agrees to own these DEMO offerings.
-- No account, credentials, wallet, table, or existing offering is replaced.
-- Replace NULL below with that Member's user_id. Leaving NULL inserts nothing.
-- Re-running this script for the same tutor skips the same demo offerings.
USE timebank_db;
SET @timebank_demo_tutor_id = NULL;

START TRANSACTION;

-- The existing reference catalog has no master skill in Other Skills.
INSERT INTO skills (category_id, name, slug, description)
SELECT c.category_id, 'Study Planning', 'timebank-demo-study-planning', 'Plan a practical weekly study routine.'
FROM skill_categories c
WHERE c.slug = 'other-skills'
  AND EXISTS (SELECT 1 FROM users WHERE user_id = @timebank_demo_tutor_id AND role = 'member' AND account_status = 'active')
  AND NOT EXISTS (SELECT 1 FROM skills WHERE slug = 'timebank-demo-study-planning');

INSERT INTO skill_offerings (tutor_id, skill_id, title, description, skill_level, duration_minutes, credit_cost, verification_status, is_active)
SELECT u.user_id, s.skill_id, sample.title, sample.description, 'beginner', sample.duration_minutes, sample.credit_cost, 'unverified', 1
FROM (
    SELECT 'html-css' AS skill_slug, 'Web Development Basics (Demo)' AS title,
           'Learn HTML and CSS by building your first simple web page.' AS description, 60 AS duration_minutes, 1.00 AS credit_cost
    UNION ALL SELECT 'ui-ux-design', 'Introduction to UI Design (Demo)',
           'Explore layout, typography, and a clear interface for a mobile app.', 90, 1.50
    UNION ALL SELECT 'english-conversation', 'Everyday English Conversation (Demo)',
           'Practice introductions and everyday conversations in a friendly session.', 60, 1.00
    UNION ALL SELECT 'python-data-analysis', 'Python for Data Analysis (Demo)',
           'Read a small dataset and create your first useful chart with Python.', 90, 1.50
    UNION ALL SELECT 'digital-marketing', 'Digital Marketing Basics (Demo)',
           'Plan a simple campaign and understand your audience and message.', 60, 1.00
    UNION ALL SELECT 'timebank-demo-study-planning', 'Study Planning (Demo)',
           'Build a realistic weekly study plan and a routine you can keep.', 30, 0.50
) sample
JOIN skills s ON s.slug = sample.skill_slug
JOIN users u ON u.user_id = @timebank_demo_tutor_id AND u.role = 'member' AND u.account_status = 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM skill_offerings existing
    WHERE existing.tutor_id = u.user_id AND existing.title = sample.title
);

COMMIT;
SELECT CASE WHEN EXISTS (SELECT 1 FROM users WHERE user_id = @timebank_demo_tutor_id AND role = 'member' AND account_status = 'active')
       THEN 'Demo offerings added where missing. Existing data preserved.'
       ELSE 'Nothing inserted: set an existing active Member user_id first.' END AS demo_seed_status;
