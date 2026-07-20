-- =========================================================
-- 1. LOANS TABLE (1:M Relationship with Students)
-- =========================================================
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    loan_type VARCHAR(50) NOT NULL, -- Allowed: 'Tuition', 'Books', or 'Living Expenses'
    status VARCHAR(50) NOT NULL DEFAULT 'Pending', -- Allowed: 'Pending', 'Approved', or 'Disbursed'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key referencing the existing students table
    CONSTRAINT fk_student_loan 
        FOREIGN KEY (student_id) 
        REFERENCES students(id) 
        ON DELETE CASCADE
);

-- =========================================================
-- 2. PAYMENTS TABLE (1:M Relationship with Loans)
-- =========================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- Allowed: 'Cash', 'Bank Transfer', or 'Online Payment'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key referencing the loans table
    CONSTRAINT fk_loan_payment 
        FOREIGN KEY (loan_id) 
        REFERENCES loans(id) 
        ON DELETE CASCADE
);