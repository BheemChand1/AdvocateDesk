-- Case Stages/Positions Database Schema
-- Run this SQL file to create tables for case stages and position tracking

-- Case stages master table
CREATE TABLE IF NOT EXISTS case_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_name VARCHAR(200) NOT NULL,
    case_type VARCHAR(50),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_case_type (case_type)
);

-- Case position updates tracking table
CREATE TABLE IF NOT EXISTS case_position_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    update_date DATE NOT NULL,
    position VARCHAR(300) NOT NULL,
    remarks TEXT,
    is_end_position BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id),
    INDEX idx_update_date (update_date)
);

-- Insert default case stages/positions
INSERT INTO case_stages (stage_name, case_type, display_order) VALUES
-- Common stages for all case types
('Notice Issued', NULL, 1),
('Reply Received', NULL, 2),
('First Hearing', NULL, 3),
('Evidence Stage', NULL, 4),
('Arguments', NULL, 5),
('Reserved for Orders', NULL, 6),
('Judgment Pronounced', NULL, 7),
('Case Disposed', NULL, 8),

-- NI/PASSA specific stages
('Complaint Filed', 'ni-passa', 1),
('Summons Issued', 'ni-passa', 2),
('Appearance of Accused', 'ni-passa', 3),
('Examination of Complainant', 'ni-passa', 4),
('Defence Evidence', 'ni-passa', 5),
('Final Arguments', 'ni-passa', 6),

-- Criminal case specific stages
('FIR Filed', 'criminal', 1),
('Charge Sheet Filed', 'criminal', 2),
('Cognizance Taken', 'criminal', 3),
('Bail Application', 'criminal, 4),
('Framing of Charges', 'criminal', 5),

-- Civil/Consumer case specific stages
('Suit Filed', 'consumer-civil', 1),
('Written Statement', 'consumer-civil', 2),
('Framing of Issues', 'consumer-civil', 3),
('Plaintiff Evidence', 'consumer-civil', 4),
('Defendant Evidence', 'consumer-civil', 5),

-- EP/Arbitration specific stages
('Petition Filed', 'ep-arbitration', 1),
('Service of Notice', 'ep-arbitration', 2),
('Objections Filed', 'ep-arbitration', 3),
('Decree Passed', 'ep-arbitration', 4),
('Execution Initiated', 'ep-arbitration', 5);
