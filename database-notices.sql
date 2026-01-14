-- Notices Database Schema
-- Run this SQL file to create tables for notice management

-- Main notices table
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_notice_id VARCHAR(50) UNIQUE,
    client_id INT NOT NULL,
    notice_date DATE NOT NULL,
    section VARCHAR(200),
    act VARCHAR(200),
    post_date DATE,
    days_reminder INT DEFAULT 15,
    due_date DATE NOT NULL,
    input_data TEXT,
    addressee TEXT,
    status VARCHAR(50) DEFAULT 'open',
    closed_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(255),
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_due_date (due_date),
    INDEX idx_notice_date (notice_date),
    INDEX idx_status (status),
    INDEX idx_unique_notice_id (unique_notice_id)
);

-- Notice remarks/history tracking
CREATE TABLE IF NOT EXISTS notice_remarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notice_id INT NOT NULL,
    remark TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(255),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    INDEX idx_notice_id (notice_id),
    INDEX idx_created_at (created_at)
);
