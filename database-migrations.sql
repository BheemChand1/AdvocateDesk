-- Database Migration: Consolidate Billing into case_position_updates Table

-- Add bill tracking columns to case_position_updates table
ALTER TABLE case_position_updates 
ADD COLUMN IF NOT EXISTS bill_number VARCHAR(100),
ADD COLUMN IF NOT EXISTS bill_date DATE,
ADD COLUMN IF NOT EXISTS completed_date DATE,
ADD COLUMN IF NOT EXISTS fee_amount DECIMAL(10,2) DEFAULT 0;

-- Create index for faster queries
ALTER TABLE case_position_updates 
ADD INDEX IF NOT EXISTS idx_bill_number (bill_number),
ADD INDEX IF NOT EXISTS idx_completed_date (completed_date);

-- Add priority status and remark columns to cases table
ALTER TABLE cases
ADD COLUMN IF NOT EXISTS priority_status TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS remark TEXT;

