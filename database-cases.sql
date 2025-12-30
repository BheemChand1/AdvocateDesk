-- Cases Database Schema - Approach 2: Normalized Structure
-- Run this SQL file to create all necessary tables

-- Main cases table with common fields
CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    case_type VARCHAR(50) NOT NULL,
    cnr_number VARCHAR(100),
    loan_number VARCHAR(100),
    product VARCHAR(100),
    branch_name VARCHAR(200),
    location VARCHAR(200),
    region VARCHAR(200),
    complainant_authorised_person VARCHAR(200),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_client_id (client_id),
    INDEX idx_case_type (case_type),
    INDEX idx_cnr_number (cnr_number)
);

-- Case parties (complainants, defendants, accused, plaintiffs, decree holders)
CREATE TABLE IF NOT EXISTS case_parties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    party_type ENUM('complainant', 'defendant', 'accused', 'plaintiff', 'decree_holder') NOT NULL,
    name VARCHAR(300) NOT NULL,
    address TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id)
);

-- Fee grid entries
CREATE TABLE IF NOT EXISTS case_fee_grid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    fee_name VARCHAR(200) NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id)
);

-- NI/PASSA case specific details
CREATE TABLE IF NOT EXISTS case_ni_passa_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL UNIQUE,
    accused_authorised_person VARCHAR(200),
    cheque_no VARCHAR(100),
    cheque_date DATE,
    total_no_of_chq INT,
    cheque_amount DECIMAL(15,2),
    filing_amount DECIMAL(15,2),
    bank_name_address VARCHAR(300),
    cheque_holder_name VARCHAR(200),
    cheque_status VARCHAR(50),
    bounce_date DATE,
    bounce_reason VARCHAR(300),
    notice_date DATE,
    notice_sent_date DATE,
    filing_date DATE,
    filing_location VARCHAR(300),
    case_no VARCHAR(100),
    court_no VARCHAR(100),
    court_name VARCHAR(300),
    section VARCHAR(200),
    act VARCHAR(200),
    poa_date DATE,
    last_date_update DATE,
    current_stage VARCHAR(200),
    remarks TEXT,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

-- Criminal case specific details
CREATE TABLE IF NOT EXISTS case_criminal_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL UNIQUE,
    case_type_specific VARCHAR(100),
    section VARCHAR(200),
    act VARCHAR(200),
    police_station_with_district VARCHAR(300),
    crime_no_fir_no VARCHAR(100),
    fir_date DATE,
    charge_sheet_date DATE,
    notice_date DATE,
    poa_date DATE,
    filing_date DATE,
    filing_location VARCHAR(300),
    case_no VARCHAR(100),
    court_no VARCHAR(100),
    court_name VARCHAR(300),
    remarks TEXT,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

-- Consumer/Civil case specific details
CREATE TABLE IF NOT EXISTS case_consumer_civil_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL UNIQUE,
    case_type_specific VARCHAR(100),
    case_filling_date DATE,
    legal_notice_date DATE,
    case_vs_law_act VARCHAR(300),
    swt_value VARCHAR(100),
    filing_location VARCHAR(300),
    court_name VARCHAR(300),
    case_no VARCHAR(100),
    court_no VARCHAR(100),
    advocate VARCHAR(200),
    poa VARCHAR(200),
    remarks TEXT,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

-- EP/Arbitration case specific details
CREATE TABLE IF NOT EXISTS case_ep_arbitration_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL UNIQUE,
    filing_location VARCHAR(300),
    case_no VARCHAR(100),
    court_no VARCHAR(100),
    advocate VARCHAR(200),
    poa VARCHAR(200),
    date_of_filing DATE,
    customer_office_address TEXT,
    award_date DATE,
    arbitrator_name VARCHAR(200),
    arbitrator_address TEXT,
    arbitration_case_no VARCHAR(100),
    interest_start_date DATE,
    interest_end_date DATE,
    total_days INT,
    award_amount DECIMAL(15,2),
    rate_of_interest DECIMAL(5,2),
    interest_amount DECIMAL(15,2),
    cost DECIMAL(15,2),
    recovery_amount DECIMAL(15,2),
    claim_amount DECIMAL(15,2),
    vehicle_1_classification VARCHAR(200),
    vehicle_2_asset_description VARCHAR(300),
    vehicle_3_registration_number VARCHAR(100),
    vehicle_4_engine_no VARCHAR(100),
    vehicle_5_chasis_no VARCHAR(100),
    immoveable_property_detail_1 TEXT,
    immoveable_property_detail_2 TEXT,
    immoveable_property_detail_3 TEXT,
    remarks_feedback_trails TEXT,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

-- Arbitration Other case specific details
CREATE TABLE IF NOT EXISTS case_arbitration_other_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL UNIQUE,
    customer_name VARCHAR(200),
    filing_amount DECIMAL(15,2),
    filing_date DATE,
    filing_location VARCHAR(300),
    case_no VARCHAR(100),
    court_no VARCHAR(100),
    advocate VARCHAR(200),
    poa VARCHAR(200),
    remarks_feedback_trails TEXT,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);
