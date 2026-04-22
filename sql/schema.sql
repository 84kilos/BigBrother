-- ============================================================
--  Attendance Management System — Database Schema
--  Database: attendance_system
-- ============================================================

CREATE DATABASE IF NOT EXISTS attendance_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE attendance_system;

-- ------------------------------------------------------------
--  1. USERS
--     Stores both teachers and students.
--     role: 'teacher' | 'student'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)    NOT NULL,          -- bcrypt via PHP password_hash()
    role          ENUM('teacher','student') NOT NULL DEFAULT 'student',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB;


-- ------------------------------------------------------------
--  2. SUBJECTS
--     A subject/course that belongs to one teacher.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    teacher_id  INT UNSIGNED  NOT NULL,
    name        VARCHAR(100)  NOT NULL,
    code        VARCHAR(20)   NOT NULL UNIQUE,       -- e.g. "CS101"
    description TEXT,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_subjects_teacher (teacher_id),
    CONSTRAINT fk_subjects_teacher
        FOREIGN KEY (teacher_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ------------------------------------------------------------
--  3. ENROLLMENTS
--     Many-to-many: which students are in which subject.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollments (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    student_id  INT UNSIGNED  NOT NULL,
    subject_id  INT UNSIGNED  NOT NULL,
    enrolled_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_enrollment (student_id, subject_id),
    INDEX idx_enroll_subject (subject_id),
    CONSTRAINT fk_enroll_student
        FOREIGN KEY (student_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_enroll_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ------------------------------------------------------------
--  4. SESSIONS
--     A single class meeting / lecture for a subject.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    subject_id   INT UNSIGNED  NOT NULL,
    session_date DATE          NOT NULL,
    session_label VARCHAR(100),                      -- e.g. "Week 3 Lecture", optional
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sessions_subject (subject_id),
    INDEX idx_sessions_date    (session_date),
    CONSTRAINT fk_sessions_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ------------------------------------------------------------
--  5. ATTENDANCE
--     One row per student per session.
--     status: 'present' | 'absent' | 'late' | 'excused'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    session_id  INT UNSIGNED  NOT NULL,
    student_id  INT UNSIGNED  NOT NULL,
    status      ENUM('present','absent','late','excused')
                              NOT NULL DEFAULT 'absent',
    marked_by   INT UNSIGNED  NOT NULL,              -- teacher user id
    marked_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes       VARCHAR(255),                        -- optional teacher note
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance (session_id, student_id),
    INDEX idx_att_student  (student_id),
    INDEX idx_att_session  (session_id),
    CONSTRAINT fk_att_session
        FOREIGN KEY (session_id)  REFERENCES sessions (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_att_student
        FOREIGN KEY (student_id)  REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_att_marker
        FOREIGN KEY (marked_by)   REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
--  VIEWS  (handy for reports — no extra PHP query complexity)
-- ============================================================

-- Per-student attendance summary per subject
CREATE OR REPLACE VIEW vw_student_attendance_summary AS
SELECT
    u.id                                        AS student_id,
    u.full_name                                 AS student_name,
    sub.id                                      AS subject_id,
    sub.name                                    AS subject_name,
    sub.code                                    AS subject_code,
    COUNT(a.id)                                 AS total_sessions,
    SUM(a.status = 'present')                   AS present_count,
    SUM(a.status = 'late')                      AS late_count,
    SUM(a.status = 'absent')                    AS absent_count,
    SUM(a.status = 'excused')                   AS excused_count,
    ROUND(
        (SUM(a.status = 'present') + SUM(a.status = 'late'))
        / NULLIF(COUNT(a.id), 0) * 100
    , 2)                                        AS attendance_pct
FROM users u
JOIN enrollments  e   ON e.student_id  = u.id
JOIN subjects     sub ON sub.id        = e.subject_id
JOIN sessions     ses ON ses.subject_id = sub.id
LEFT JOIN attendance a
       ON a.session_id = ses.id AND a.student_id = u.id
WHERE u.role = 'student'
GROUP BY u.id, sub.id;


-- Full attendance log with human-readable names (useful for reports page)
CREATE OR REPLACE VIEW vw_attendance_log AS
SELECT
    a.id                    AS attendance_id,
    u_s.full_name           AS student_name,
    u_s.email               AS student_email,
    sub.name                AS subject_name,
    sub.code                AS subject_code,
    ses.session_date,
    ses.session_label,
    a.status,
    a.notes,
    u_t.full_name           AS marked_by_teacher,
    a.marked_at
FROM attendance a
JOIN users    u_s ON u_s.id       = a.student_id
JOIN sessions ses ON ses.id       = a.session_id
JOIN subjects sub ON sub.id       = ses.subject_id
JOIN users    u_t ON u_t.id       = a.marked_by
ORDER BY ses.session_date DESC, u_s.full_name;


-- ============================================================
--  SAMPLE SEED DATA  (remove before production)
-- ============================================================

-- Teacher account  (password: Teacher@123)
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Prof. Jane Smith',
 'teacher@school.edu',
 '$2y$12$YourHashedPasswordHere111111111111111111111111111111111u',
 'teacher');

-- Student accounts  (password: Student@123)
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Alice Johnson', 'alice@school.edu',  '$2y$12$YourHashedPasswordHere111111111111111111111111111111111u', 'student'),
('Bob Martinez',  'bob@school.edu',   '$2y$12$YourHashedPasswordHere111111111111111111111111111111111u', 'student'),
('Carol Lee',     'carol@school.edu', '$2y$12$YourHashedPasswordHere111111111111111111111111111111111u', 'student');

-- NOTE: Replace the password_hash values above by running this in PHP:
--   echo password_hash('YourPassword', PASSWORD_BCRYPT, ['cost' => 12]);
