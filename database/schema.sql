
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','member') NOT NULL DEFAULT 'member',
  phone VARCHAR(30) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  author VARCHAR(120) NOT NULL,
  category VARCHAR(60) DEFAULT '',
  copies INT NOT NULL DEFAULT 1
);

CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  user_id INT NOT NULL,
  borrowed_on DATE NOT NULL,
  due_on DATE NOT NULL,
  returned_on DATE NULL,
  FOREIGN KEY (book_id) REFERENCES books(id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password, role, phone) VALUES
('Librarian',      'admin@library.com',   '$2b$12$OGDPBS2nSVzDy509xBcOJu0GwIXdFT/3AJf2X/US78oWJVBTMTFWO', 'admin',  NULL),
('Juan Dela Cruz', 'member@library.com',  '$2b$12$OGDPBS2nSVzDy509xBcOJu0GwIXdFT/3AJf2X/US78oWJVBTMTFWO', 'member', '09171234567'),
('Maria Lopez',    'member2@library.com', '$2b$12$OGDPBS2nSVzDy509xBcOJu0GwIXdFT/3AJf2X/US78oWJVBTMTFWO', 'member', '09181234567'),
('Pedro Ramos',    'member3@library.com', '$2b$12$OGDPBS2nSVzDy509xBcOJu0GwIXdFT/3AJf2X/US78oWJVBTMTFWO', 'member', NULL);

INSERT INTO books (title, author, category, copies) VALUES
('Noli Me Tangere',                 'Jose Rizal',          'Filipino Classics', 3),
('El Filibusterismo',               'Jose Rizal',          'Filipino Classics', 2),
('Clean Code',                      'Robert C. Martin',    'Information Technology',  2),
('PHP and MySQL Web Development',   'Luke Welling',        'Information Technology',  1),
('The Teaching Profession',         'Bilbao and Corpuz',   'Education',         2),
('Strength of Materials',           'Ferdinand Singer',    'Engineering',       2);

INSERT INTO loans (book_id, user_id, borrowed_on, due_on, returned_on) VALUES
(1, 2, '2026-09-01', '2026-09-08', '2026-09-07'),
(3, 2, '2026-10-09', '2026-10-16', NULL),
(5, 3, '2026-09-15', '2026-09-22', NULL),
(6, 4, '2026-10-05', '2026-10-12', NULL);
