CREATE DATABASE BigBrother;

USE BigBrother;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    semester VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_classes_teacher
        FOREIGN KEY (teacher_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE enrollments (
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id, student_id),
    CONSTRAINT fk_enrollments_class
        FOREIGN KEY (class_id) REFERENCES classes(class_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_student
        FOREIGN KEY (student_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE meetings (
    meeting_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    meeting_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meetings_class
        FOREIGN KEY (class_id) REFERENCES classes(class_id)
        ON DELETE CASCADE,
    CONSTRAINT uq_meeting_per_day
        UNIQUE (class_id, meeting_date)
);

CREATE TABLE attendance (
    meeting_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'late', 'absent') NOT NULL,
    recorded_by INT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (meeting_id, student_id),
    CONSTRAINT fk_attendance_meeting
        FOREIGN KEY (meeting_id) REFERENCES meetings(meeting_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_student
        FOREIGN KEY (student_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_recorded_by
        FOREIGN KEY (recorded_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- One-time seed example:
-- Create a class for George Washington (teacher user_id = 1)
-- and enroll Jimmy Johns (student user_id = 4)
INSERT INTO classes (teacher_id, name, period, year) VALUES (1, 'Civics & Government', '2nd', '2025-2026');
SET @new_class_id = LAST_INSERT_ID();
INSERT INTO enrollments (class_id, student_id) VALUES (@new_class_id, 4);
