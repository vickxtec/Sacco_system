-- SACCO System Database Schema
CREATE DATABASE IF NOT EXISTS sacco_db;
USE sacco_db;

-- Users table for login
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'officer', 'teller') DEFAULT 'officer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('default_interest_rate', '12.00', 'Default loan interest rate in percent'),
('max_loan_multiplier', '3', 'Maximum loan amount expressed as a multiple of savings balance'),
('min_savings_ratio', '0.33', 'Minimum savings ratio required to qualify for a loan'),
('max_loan_term_months', '60', 'Maximum allowed loan term in months'),
('loan_policy', 'Loans are approved based on member savings, guarantor validation and available collateral.', 'Default loan policy description');

-- Members table
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_no VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    id_number VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    joined_date DATE NOT NULL,
    photo VARCHAR(255),
    id_front VARCHAR(255),
    id_back VARCHAR(255),
    signature VARCHAR(255),
    share_capital DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Savings accounts
CREATE TABLE IF NOT EXISTS savings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    account_no VARCHAR(20) UNIQUE NOT NULL,
    account_type ENUM('Regular', 'Fixed', 'Holiday', 'Junior') DEFAULT 'Regular',
    balance DECIMAL(15,2) DEFAULT 0.00,
    interest_rate DECIMAL(5,2) DEFAULT 3.50,
    status ENUM('Active', 'Dormant', 'Closed') DEFAULT 'Active',
    opened_date DATE NOT NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Savings transactions
CREATE TABLE IF NOT EXISTS savings_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    savings_id INT NOT NULL,
    transaction_type ENUM('Deposit', 'Withdrawal', 'Interest') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    reference VARCHAR(50),
    notes TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (savings_id) REFERENCES savings(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Loans table
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    loan_no VARCHAR(20) UNIQUE NOT NULL,
    loan_type ENUM('Personal', 'Business', 'Emergency', 'Development', 'School Fees') DEFAULT 'Personal',
    principal DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    term_months INT NOT NULL,
    monthly_payment DECIMAL(15,2) NOT NULL,
    total_payable DECIMAL(15,2) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('Pending', 'Approved', 'Active', 'Completed', 'Defaulted', 'Rejected') DEFAULT 'Pending',
    applied_date DATE NOT NULL,
    approved_date DATE,
    disbursed_date DATE,
    due_date DATE,
    purpose TEXT,
    collateral_security TEXT,
    guarantor_id INT,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (guarantor_id) REFERENCES members(id)
);

-- Loan repayments
CREATE TABLE IF NOT EXISTS loan_repayments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    principal_paid DECIMAL(15,2) NOT NULL,
    interest_paid DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    reference VARCHAR(50),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (loan_id) REFERENCES loans(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$6D2hkmc1IkwfSBG4A4XHzupfhSYimjm9G5Eopro4nM6kbx4arPhBm', 'System Administrator', 'admin'),
('officer1', '$2y$10$6D2hkmc1IkwfSBG4A4XHzupfhSYimjm9G5Eopro4nM6kbx4arPhBm', 'Jane Wanjiku', 'officer');

-- Sample members
INSERT INTO members (member_no, full_name, id_number, phone, email, gender, status, joined_date, share_capital) VALUES
('MEM001', 'John Kamau Njoroge', '12345678', '+254712345678', 'john.kamau@email.com', 'Male', 'Active', '2022-01-15', 5000.00),
('MEM002', 'Mary Wambui Muthoni', '23456789', '+254723456789', 'mary.wambui@email.com', 'Female', 'Active', '2022-03-20', 5000.00),
('MEM003', 'Peter Ochieng Otieno', '34567890', '+254734567890', 'peter.ochieng@email.com', 'Male', 'Active', '2022-06-10', 10000.00),
('MEM004', 'Grace Njeri Maina', '45678901', '+254745678901', 'grace.njeri@email.com', 'Female', 'Inactive', '2021-11-05', 5000.00),
('MEM005', 'Samuel Kipkoech Rono', '56789012', '+254756789012', 'samuel.kipkoech@email.com', 'Male', 'Active', '2023-02-28', 15000.00);

-- Sample savings accounts
INSERT INTO savings (member_id, account_no, account_type, balance, opened_date) VALUES
(1, 'SAV001001', 'Regular', 45000.00, '2022-01-15'),
(2, 'SAV001002', 'Regular', 78500.00, '2022-03-20'),
(3, 'SAV001003', 'Fixed', 200000.00, '2022-06-10'),
(4, 'SAV001004', 'Regular', 12000.00, '2021-11-05'),
(5, 'SAV001005', 'Regular', 95000.00, '2023-02-28');

-- Sample loans
INSERT INTO loans (member_id, loan_no, loan_type, principal, interest_rate, term_months, monthly_payment, total_payable, balance, status, applied_date, approved_date, disbursed_date, due_date, purpose) VALUES
(1, 'LN001001', 'Personal', 50000.00, 12.00, 12, 4442.44, 53309.28, 35000.00, 'Active', '2023-01-10', '2023-01-15', '2023-01-20', '2024-01-20', 'Home improvement'),
(2, 'LN001002', 'Business', 100000.00, 14.00, 24, 4801.09, 115226.16, 80000.00, 'Active', '2023-03-01', '2023-03-05', '2023-03-10', '2025-03-10', 'Business expansion'),
(3, 'LN001003', 'Development', 200000.00, 13.00, 36, 6735.98, 242495.28, 150000.00, 'Active', '2022-07-01', '2022-07-05', '2022-07-15', '2025-07-15', 'Land purchase'),
(5, 'LN001004', 'Emergency', 30000.00, 10.00, 6, 5127.62, 30765.72, 0.00, 'Completed', '2023-05-01', '2023-05-02', '2023-05-03', '2023-11-03', 'Medical emergency');