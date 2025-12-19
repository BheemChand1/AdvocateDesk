-- Create database
CREATE DATABASE IF NOT EXISTS case_management;
USE case_management;

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Username: admin
-- Password: admin123
INSERT INTO admin_users (full_name, username, password, role, status) 
VALUES ('Administrator', 'admin', '$2y$10$8K1p/H4KJd8JVR8iAJKJK.xfZ9XJZcH3Z3r5rN0Y0Y0Y0Y0Y0Y0Yu', 'admin', 'active')
ON DUPLICATE KEY UPDATE password = '$2y$10$8K1p/H4KJd8JVR8iAJKJK.xfZ9XJZcH3Z3r5rN0Y0Y0Y0Y0Y0Y0Yu';

-- Note: The default password is 'admin123' - Please change it after first login
