CREATE DATABASE ashesi_lms;
USE ashesi_lms;

-- Users Table (Handles Students, Faculty, Interns)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty') NOT NULL -- 'faculty' covers Teachers/Interns
);

-- Courses Table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) NOT NULL
);

-- Sessions Table (created by Faculty)
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

-- Attendance Log (Students marking present)
CREATE TABLE attendance_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    UNIQUE KEY unique_checkin (session_id, student_id) 
);

--  Storing Course Materials
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

-- Table for Faculty to create assignments
CREATE TABLE assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME,
    max_score INT DEFAULT 100,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- Table for Students to submit work and receive grades
CREATE TABLE submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255),
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score INT DEFAULT NULL,
    feedback TEXT,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id)
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
('Kwame Attrams', 'kwame.attrams@ashesi.edu.gh', '$2y$10$wS.x..2l..p..passwordhash..', 'student');