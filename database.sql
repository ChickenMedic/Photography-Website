-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS personal_portfolio;
USE personal_portfolio;

-- Table for locations (albums)
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for uploaded photos
CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Table for projects
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for admin users (we'll just use one simple admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- Insert a default admin user (password: 'admin123')
-- NOTE: In a real production scenario, change this password immediately!
INSERT IGNORE INTO users (username, password_hash) VALUES ('admin', '$2y$10$wOqR.Z/Z8v8zM30D1s.yO.p4yP2oXlYv/T/G97yv1a/KjZ.uLpOKu');
