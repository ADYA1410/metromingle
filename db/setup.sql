-- MetroMingle fresh-install schema (Phase 1).
-- For an EXISTING database do not run this file; run `php db/migrate.php` instead.
-- Usage: CREATE DATABASE metromingle CHARACTER SET utf8mb4; USE metromingle; then import this file.

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'owner', 'user') NOT NULL DEFAULT 'user',
  full_name VARCHAR(100),
  email VARCHAR(100),
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE spaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  location VARCHAR(150) NOT NULL,
  capacity INT NOT NULL,
  price_per_hour DECIMAL(10,2) NOT NULL,
  description TEXT,
  themes VARCHAR(255),
  amenities VARCHAR(255),
  photo VARCHAR(255) NULL DEFAULT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  space_id INT NOT NULL,
  event_type VARCHAR(100),
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  guests INT,
  notes TEXT NULL,
  theme VARCHAR(100),
  addons VARCHAR(255),
  budget ENUM('basic','prime','luxury') DEFAULT 'basic',
  total_amount DECIMAL(10,2),
  status ENUM('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (space_id) REFERENCES spaces(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo seed data (password for all demo accounts: 1234)
INSERT INTO users (username, password, role, full_name, email) VALUES
('admin', '$2y$10$l7x7z0sspKoG9s68OmWx6eQJ8IP7VWjAh6kp1vlhOtuKUM/SVv2hq', 'admin', 'Admin User', 'admin@metromingle.com'),
('owner1', '$2y$10$l7x7z0sspKoG9s68OmWx6eQJ8IP7VWjAh6kp1vlhOtuKUM/SVv2hq', 'owner', 'Ravi Kumar', 'ravi@metromingle.com'),
('user1', '$2y$10$l7x7z0sspKoG9s68OmWx6eQJ8IP7VWjAh6kp1vlhOtuKUM/SVv2hq', 'user', 'Priya Sharma', 'priya@metromingle.com');

INSERT INTO spaces (owner_id, name, location, capacity, price_per_hour, description, themes, amenities) VALUES
(2, 'The Grand Hall', 'Banjara Hills, Hyderabad', 500, 5000.00, 'A spacious hall perfect for large events and celebrations.', 'Royal,Modern,Rustic', 'Projector,Stage,Parking,AC'),
(2, 'Skyline Terrace', 'Jubilee Hills, Hyderabad', 150, 3000.00, 'Rooftop venue with a stunning city skyline view.', 'Outdoor,Bohemian,Minimalist', 'PA System,Catering,Wifi'),
(2, 'Crystal Ballroom', 'Gachibowli, Hyderabad', 300, 4000.00, 'Elegant ballroom ideal for weddings and anniversaries.', 'Classic,Glamour,Garden', 'Dance Floor,Lighting,Valet');

INSERT INTO bookings (user_id, space_id, event_type, booking_date, start_time, end_time, guests, theme, addons, budget, total_amount, status) VALUES
(3, 1, 'Birthday', '2026-05-10', '16:00:00', '21:00:00', 200, 'Royal', 'Cake,Decor', 'prime', 25000.00, 'confirmed'),
(3, 2, 'Anniversary', '2026-05-15', '19:00:00', '23:00:00', 80, 'Bohemian', 'Flowers,Violin', 'luxury', 12000.00, 'pending'),
(3, 3, 'Corporate Meet', '2026-05-20', '09:00:00', '17:00:00', 150, 'Minimalist', 'Projector,Lunch', 'basic', 32000.00, 'confirmed');
