
-- Database: music_lesson_app

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('student', 'mentor', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Dummy data for users
INSERT INTO users (username, password_hash, email, role) VALUES
('student1', '$2y$10$Q7.iN9v/G3aR7Q.h0Y7T.uzG4.r.W0.S.p.1.r.Q.m.K.d.L.e.s.s.p.a.s.s.w.o.r.d.h.a.s.h.f.o.r.s.t.u.d.e.n.t.1', 'student1@example.com', 'student'),
('mentor1', '$2y$10$Q7.iN9v/G3aR7Q.h0Y7T.uzG4.r.W0.S.p.1.r.Q.m.K.d.L.e.s.s.p.a.s.s.w.o.r.d.h.a.s.h.f.o.r.m.e.n.t.o.r.1', 'mentor1@example.com', 'mentor'),
('admin1', '$2y$10$Q7.iN9v/G3aR7Q.h0Y7T.uzG4.r.W0.S.p.1.r.Q.m.K.d.L.e.s.s.p.a.s.s.w.o.r.d.h.a.s.h.f.o.r.a.d.m.i.n.1', 'admin1@example.com', 'admin');


-- Table: instruments
CREATE TABLE IF NOT EXISTS instruments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon_class VARCHAR(50) NULL
);

-- Dummy data for instruments
INSERT INTO instruments (name, icon_class) VALUES
('Guitar', 'fas fa-guitar'),
('Piano', 'fas fa-piano'),
('Drums', 'fas fa-drum'),
('Violin', 'fas fa-violin'),
('Flute', 'fas fa-flute');

-- Table: mentors
CREATE TABLE IF NOT EXISTS mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    bio TEXT,
    profile_picture VARCHAR(255),
    hourly_rate DECIMAL(10, 2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: rooms (for virtual lessons, e.g., video conference links)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    join_link VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
);

-- Table: bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    mentor_id INT NOT NULL,
    instrument_id INT NOT NULL,
    room_id INT NULL, -- Can be NULL if not a live session yet
    schedule_time DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- Table: courses
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    instrument_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE CASCADE
);
    
-- Table: lessons (individual lessons within a course or standalone)
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NULL, -- NULL for standalone lessons
    mentor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(255),
    materials_url VARCHAR(255),
    lesson_order INT NULL, -- For ordering within a course
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
);
