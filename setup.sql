CREATE DATABASE ashesi_lms;
USE ashesi_lms;

-- 1. Users Table (Handles Students, Faculty, Interns)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty') NOT NULL -- 'faculty' covers Teachers/Interns
);

-- 2. Courses Table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) NOT NULL
);

-- 3. Sessions Table (created by Faculty)
CREATE TABLE sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    created_by INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME,
    access_code VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 4. Attendance Log (Students marking present)
CREATE TABLE attendance_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    UNIQUE KEY unique_checkin (session_id, student_id) 
);

-- 5. Storing Course Materials
CREATE TABLE materials (
    material_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- DUMMY DATA FOR TESTING
INSERT INTO courses (course_name, course_code) VALUES 
('Web Technologies', 'CS341'), 
('Database Systems', 'CS313');

-- Default Users (Password is '1234')
-- Faculty User
INSERT INTO users (full_name, email, password, role) VALUES 
('Dr. Sampah', 'faculty@ashesi.edu.gh', '$2y$10$wS.x..2l..p..passwordhash..', 'faculty');
-- Student User
INSERT INTO users (full_name, email, password, role) VALUES 
('Kofi Student', 'student@ashesi.edu.gh', '$2y$10$wS.x..2l..p..passwordhash..', 'student');