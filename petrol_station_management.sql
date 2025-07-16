-- =====================================================
-- UGANDA FUEL STATION MANAGEMENT SYSTEM DATABASE
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS uganda_fuel_stations;
USE uganda_fuel_stations;

-- =====================================================
-- BUSINESS & ORGANIZATIONAL TABLES
-- =====================================================

-- Business/Company Table
CREATE TABLE businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name VARCHAR(255) NOT NULL,
    business_type ENUM('individual', 'partnership', 'limited_company', 'corporation') NOT NULL,
    registration_number VARCHAR(100) UNIQUE,
    tin_number VARCHAR(50) UNIQUE,
    vat_number VARCHAR(50),
    license_number VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    region VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Uganda',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    established_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Branch/Station Table
CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    branch_code VARCHAR(20) UNIQUE NOT NULL,
    branch_name VARCHAR(255) NOT NULL,
    branch_type ENUM('main', 'sub_branch') DEFAULT 'sub_branch',
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    region VARCHAR(100),
    postal_code VARCHAR(20),
    gps_coordinates VARCHAR(100),
    operational_hours TEXT,
    manager_name VARCHAR(255),
    manager_phone VARCHAR(20),
    status ENUM('active', 'inactive', 'under_maintenance') DEFAULT 'active',
    opening_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- =====================================================
-- USER MANAGEMENT & ROLES
-- =====================================================

-- Roles Table
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) UNIQUE NOT NULL,  -- Reduced from 100 to 191
    display_name VARCHAR(190) NOT NULL,
    description TEXT,
    level TINYINT UNSIGNED DEFAULT 1,
    is_system_role BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Permissions Table
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    module VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Role Permissions Junction Table
CREATE TABLE role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
);

-- Users Table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    employee_id VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,        -- Reduced from 100 to 50
    email VARCHAR(191) UNIQUE NOT NULL,          -- Reduced from 255 to 191
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    national_id VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    profile_photo VARCHAR(500),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    hired_date DATE,
    termination_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- User Roles Junction Table
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- =====================================================
-- FUEL & PRODUCT MANAGEMENT
-- =====================================================

-- Fuel Types Table
CREATE TABLE fuel_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    octane_rating INT,
    density DECIMAL(5,3),
    color VARCHAR(50),
    unit_of_measure VARCHAR(20) DEFAULT 'liters',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Suppliers Table
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_registration VARCHAR(100),
    tin_number VARCHAR(50),
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    region VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Uganda',
    payment_terms VARCHAR(255),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    rating TINYINT UNSIGNED DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Storage Tanks Table
CREATE TABLE storage_tanks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    tank_number VARCHAR(50) NOT NULL,
    fuel_type_id BIGINT UNSIGNED NOT NULL,
    capacity DECIMAL(10,2) NOT NULL,
    current_level DECIMAL(10,2) DEFAULT 0.00,
    minimum_level DECIMAL(10,2) DEFAULT 0.00,
    maximum_level DECIMAL(10,2),
    tank_type ENUM('underground', 'above_ground') DEFAULT 'underground',
    installation_date DATE,
    last_calibration_date DATE,
    next_calibration_date DATE,
    status ENUM('active', 'inactive', 'maintenance', 'decommissioned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_branch_tank (branch_id, tank_number)
);

-- Fuel Dispensers/Pumps Table
CREATE TABLE fuel_dispensers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    tank_id BIGINT UNSIGNED NOT NULL,
    dispenser_number VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100),
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    installation_date DATE,
    last_calibration_date DATE,
    next_calibration_date DATE,
    pump_price DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'maintenance', 'out_of_order') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (tank_id) REFERENCES storage_tanks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_branch_dispenser (branch_id, dispenser_number)
);

-- =====================================================
-- INVENTORY MANAGEMENT
-- =====================================================

-- Fuel Purchases/Deliveries Table
CREATE TABLE fuel_purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    fuel_type_id BIGINT UNSIGNED NOT NULL,
    purchase_order_number VARCHAR(100),
    delivery_note_number VARCHAR(100),
    invoice_number VARCHAR(100),
    quantity_ordered DECIMAL(10,2) NOT NULL,
    quantity_delivered DECIMAL(10,2) NOT NULL,
    quantity_accepted DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    delivery_date DATE NOT NULL,
    received_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    payment_status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    payment_method VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tank Readings Table
CREATE TABLE tank_readings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tank_id BIGINT UNSIGNED NOT NULL,
    reading_date DATE NOT NULL,
    reading_time TIME NOT NULL,
    fuel_level DECIMAL(10,2) NOT NULL,
    temperature DECIMAL(5,2),
    water_level DECIMAL(10,2) DEFAULT 0.00,
    reading_type ENUM('opening', 'closing', 'delivery', 'manual') NOT NULL,
    taken_by BIGINT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tank_id) REFERENCES storage_tanks(id) ON DELETE CASCADE,
    FOREIGN KEY (taken_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SALES & TRANSACTIONS
-- =====================================================

-- Sales Transactions Table
CREATE TABLE sales_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    dispenser_id BIGINT UNSIGNED NOT NULL,
    fuel_type_id BIGINT UNSIGNED NOT NULL,
    transaction_number VARCHAR(100) UNIQUE NOT NULL,
    receipt_number VARCHAR(100),
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    final_amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile_money', 'credit', 'voucher') NOT NULL,
    payment_reference VARCHAR(255),
    customer_name VARCHAR(255),
    customer_phone VARCHAR(20),
    vehicle_plate VARCHAR(50),
    attendant_id BIGINT UNSIGNED,
    transaction_date DATE NOT NULL,
    transaction_time TIME NOT NULL,
    shift_id BIGINT UNSIGNED,
    status ENUM('completed', 'pending', 'cancelled', 'refunded') DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (dispenser_id) REFERENCES fuel_dispensers(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (attendant_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Customer Accounts Table
CREATE TABLE customer_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_code VARCHAR(50) UNIQUE NOT NULL,
    company_name VARCHAR(255),
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    tin_number VARCHAR(50),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    payment_terms VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Credit Sales Table
CREATE TABLE credit_sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_account_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NOT NULL,
    credit_amount DECIMAL(15,2) NOT NULL,
    due_date DATE NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    paid_amount DECIMAL(15,2) DEFAULT 0.00,
    remaining_balance DECIMAL(15,2) NOT NULL,
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES sales_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- FINANCIAL MANAGEMENT
-- =====================================================

-- Daily Sales Summary Table
CREATE TABLE daily_sales_summary (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    business_date DATE NOT NULL,
    total_transactions INT DEFAULT 0,
    total_quantity DECIMAL(12,2) DEFAULT 0.00,
    total_sales DECIMAL(15,2) DEFAULT 0.00,
    cash_sales DECIMAL(15,2) DEFAULT 0.00,
    card_sales DECIMAL(15,2) DEFAULT 0.00,
    mobile_money_sales DECIMAL(15,2) DEFAULT 0.00,
    credit_sales DECIMAL(15,2) DEFAULT 0.00,
    total_discounts DECIMAL(15,2) DEFAULT 0.00,
    total_taxes DECIMAL(15,2) DEFAULT 0.00,
    prepared_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    status ENUM('draft', 'submitted', 'approved') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_branch_date (branch_id, business_date)
);

-- Expenses Table
CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    expense_category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'cheque', 'card') NOT NULL,
    reference_number VARCHAR(100),
    approved_by BIGINT UNSIGNED,
    receipt_number VARCHAR(100),
    vendor_name VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SHIFT MANAGEMENT
-- =====================================================

-- Shifts Table
CREATE TABLE shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    shift_name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Shift Assignments Table
CREATE TABLE shift_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assignment_date DATE NOT NULL,
    clock_in_time TIMESTAMP NULL,
    clock_out_time TIMESTAMP NULL,
    total_hours DECIMAL(4,2) DEFAULT 0.00,
    total_sales DECIMAL(15,2) DEFAULT 0.00,
    opening_cash DECIMAL(10,2) DEFAULT 0.00,
    closing_cash DECIMAL(10,2) DEFAULT 0.00,
    cash_difference DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    status ENUM('scheduled', 'active', 'completed', 'absent') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_shift_user_date (shift_id, user_id, assignment_date)
);

-- =====================================================
-- MAINTENANCE & COMPLIANCE
-- =====================================================

-- Equipment Maintenance Table
CREATE TABLE equipment_maintenance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    equipment_type ENUM('tank', 'dispenser', 'generator', 'safety_equipment', 'other') NOT NULL,
    equipment_id BIGINT UNSIGNED,
    maintenance_type ENUM('preventive', 'corrective', 'emergency', 'calibration') NOT NULL,
    description TEXT NOT NULL,
    maintenance_date DATE NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    service_provider VARCHAR(255),
    technician_name VARCHAR(255),
    next_maintenance_date DATE,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    performed_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Regulatory Compliance Table
CREATE TABLE regulatory_compliance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    compliance_type VARCHAR(100) NOT NULL,
    requirement_description TEXT NOT NULL,
    due_date DATE NOT NULL,
    completion_date DATE,
    compliance_status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    responsible_person BIGINT UNSIGNED,
    regulatory_body VARCHAR(255),
    certificate_number VARCHAR(100),
    renewal_date DATE,
    cost DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_person) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- REPORTING & ANALYTICS
-- =====================================================

-- Reports Table
CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    branch_id BIGINT UNSIGNED,
    date_from DATE,
    date_to DATE,
    parameters JSON,
    file_path VARCHAR(500),
    file_size INT,
    generated_by BIGINT UNSIGNED,
    status ENUM('generating', 'completed', 'failed') DEFAULT 'generating',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- System Settings Table
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_editable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Business and Branch Indexes
CREATE INDEX idx_businesses_status ON businesses(status);
CREATE INDEX idx_businesses_tin ON businesses(tin_number);
CREATE INDEX idx_branches_business_id ON branches(business_id);
CREATE INDEX idx_branches_status ON branches(status);

-- User and Role Indexes
CREATE INDEX idx_users_business_branch ON users(business_id, branch_id);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_user_roles_user_id ON user_roles(user_id);

-- Fuel and Inventory Indexes
CREATE INDEX idx_storage_tanks_branch_fuel ON storage_tanks(branch_id, fuel_type_id);
CREATE INDEX idx_fuel_dispensers_branch_tank ON fuel_dispensers(branch_id, tank_id);
CREATE INDEX idx_fuel_purchases_branch_date ON fuel_purchases(branch_id, delivery_date);
CREATE INDEX idx_tank_readings_tank_date ON tank_readings(tank_id, reading_date);

-- Sales and Transaction Indexes
CREATE INDEX idx_sales_transactions_branch_date ON sales_transactions(branch_id, transaction_date);
CREATE INDEX idx_sales_transactions_dispenser ON sales_transactions(dispenser_id);
CREATE INDEX idx_sales_transactions_attendant ON sales_transactions(attendant_id);
CREATE INDEX idx_daily_sales_branch_date ON daily_sales_summary(branch_id, business_date);

-- Financial Indexes
CREATE INDEX idx_expenses_branch_date ON expenses(branch_id, expense_date);
CREATE INDEX idx_credit_sales_customer ON credit_sales(customer_account_id);
CREATE INDEX idx_credit_sales_status ON credit_sales(payment_status);

-- Shift Management Indexes
CREATE INDEX idx_shift_assignments_date ON shift_assignments(assignment_date);
CREATE INDEX idx_shift_assignments_user_date ON shift_assignments(user_id, assignment_date);

-- Maintenance and Compliance Indexes
CREATE INDEX idx_equipment_maintenance_branch_date ON equipment_maintenance(branch_id, maintenance_date);
CREATE INDEX idx_regulatory_compliance_branch_due ON regulatory_compliance(branch_id, due_date);

-- Audit and System Indexes
CREATE INDEX idx_audit_logs_user_action ON audit_logs(user_id, action);
CREATE INDEX idx_audit_logs_table_record ON audit_logs(table_name, record_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- =====================================================
-- INITIAL DATA SETUP
-- =====================================================

-- Insert Default Fuel Types
INSERT INTO fuel_types (name, code, description, octane_rating, unit_of_measure) VALUES
('Petrol', 'PET', 'Regular Petrol/Gasoline', 91, 'liters'),
('Super Petrol', 'SUP', 'Super Petrol/Premium Gasoline', 95, 'liters'),
('Diesel', 'DSL', 'Diesel Fuel', NULL, 'liters'),
('Kerosene', 'KER', 'Kerosene/Paraffin', NULL, 'liters');

-- Insert Default Roles
INSERT INTO roles (name, display_name, description, level, is_system_role) VALUES
('super_admin', 'Super Administrator', 'Full system access across all businesses', 10, TRUE),
('business_owner', 'Business Owner', 'Full access to own business and all branches', 9, TRUE),
('branch_manager', 'Branch Manager', 'Full access to assigned branch', 8, TRUE),
('shift_supervisor', 'Shift Supervisor', 'Supervise shifts and attendants', 6, TRUE),
('fuel_attendant', 'Fuel Attendant', 'Handle fuel sales and basic operations', 4, TRUE),
('accountant', 'Accountant', 'Financial management and reporting', 7, TRUE),
('maintenance_tech', 'Maintenance Technician', 'Equipment maintenance and repairs', 5, TRUE),
('viewer', 'Viewer', 'Read-only access to reports and data', 2, TRUE);

-- Insert Default Permissions
INSERT INTO permissions (name, display_name, description, module) VALUES
-- User Management
('users.view', 'View Users', 'View user information', 'user_management'),
('users.create', 'Create Users', 'Create new users', 'user_management'),
('users.update', 'Update Users', 'Update user information', 'user_management'),
('users.delete', 'Delete Users', 'Delete users', 'user_management'),

-- Branch Management
('branches.view', 'View Branches', 'View branch information', 'branch_management'),
('branches.create', 'Create Branches', 'Create new branches', 'branch_management'),
('branches.update', 'Update Branches', 'Update branch information', 'branch_management'),
('branches.delete', 'Delete Branches', 'Delete branches', 'branch_management'),

-- Sales Management
('sales.view', 'View Sales', 'View sales transactions', 'sales_management'),
('sales.create', 'Create Sales', 'Process sales transactions', 'sales_management'),
('sales.update', 'Update Sales', 'Update sales transactions', 'sales_management'),
('sales.refund', 'Refund Sales', 'Process refunds', 'sales_management'),

-- Inventory Management
('inventory.view', 'View Inventory', 'View inventory levels', 'inventory_management'),
('inventory.update', 'Update Inventory', 'Update inventory levels', 'inventory_management'),
('inventory.receive', 'Receive Inventory', 'Process fuel deliveries', 'inventory_management'),

-- Financial Management
('finance.view', 'View Financial Data', 'View financial reports', 'financial_management'),
('finance.manage', 'Manage Finances', 'Manage expenses and payments', 'financial_management'),

-- Reports
('reports.view', 'View Reports', 'View system reports', 'reporting'),
('reports.generate', 'Generate Reports', 'Generate custom reports', 'reporting'),

-- System Administration
('system.settings', 'System Settings', 'Manage system settings', 'system_administration'),
('system.audit', 'View Audit Logs', 'View system audit logs', 'system_administration');

-- Insert Default System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_editable) VALUES
('system_name', 'Uganda Fuel Station Management System', 'string', 'System display name', TRUE),
('default_currency', 'UGX', 'string', 'Default currency code', TRUE),
('tax_rate', '18.0', 'number', 'Default VAT rate percentage', TRUE),
('low_fuel_alert_threshold', '10.0', 'number', 'Low fuel alert threshold percentage', TRUE),
('backup_frequency', 'daily', 'string', 'Database backup frequency', TRUE),
('session_timeout', '30', 'number', 'Session timeout in minutes', TRUE),
('password_min_length', '8', 'number', 'Minimum password length', TRUE),
('max_credit_limit', '5000000', 'number', 'Maximum credit limit in UGX', TRUE),
('fuel_price_update_notification', 'true', 'boolean', 'Send notifications for fuel price updates', TRUE),
('auto_generate_reports', 'true', 'boolean', 'Automatically generate daily reports', TRUE);

-- =====================================================
-- ADDITIONAL TABLES FOR ENHANCED FUNCTIONALITY
-- =====================================================

-- Price History Table
CREATE TABLE fuel_price_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    fuel_type_id BIGINT UNSIGNED NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    changed_by BIGINT UNSIGNED,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications Table
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    notification_type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(500),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Fuel Vouchers Table
CREATE TABLE fuel_vouchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_code VARCHAR(100) UNIQUE NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    fuel_type_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_value DECIMAL(15,2) NOT NULL,
    issued_to VARCHAR(255),
    issued_by BIGINT UNSIGNED,
    issue_date DATE NOT NULL,
    expiry_date DATE,
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    used_by BIGINT UNSIGNED,
    transaction_id BIGINT UNSIGNED,
    status ENUM('active', 'used', 'expired', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (transaction_id) REFERENCES sales_transactions(id) ON DELETE SET NULL
);

-- Loyalty Program Table
CREATE TABLE loyalty_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(50) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    date_of_birth DATE,
    registration_branch_id BIGINT UNSIGNED NOT NULL,
    total_points INT DEFAULT 0,
    total_spent DECIMAL(15,2) DEFAULT 0.00,
    last_transaction_date DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (registration_branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Loyalty Points History Table
CREATE TABLE loyalty_points_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED,
    points_earned INT DEFAULT 0,
    points_redeemed INT DEFAULT 0,
    points_balance INT NOT NULL,
    transaction_type ENUM('earned', 'redeemed', 'expired', 'adjusted') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES loyalty_customers(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES sales_transactions(id) ON DELETE SET NULL
);

-- Fuel Quality Tests Table
CREATE TABLE fuel_quality_tests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    fuel_type_id BIGINT UNSIGNED NOT NULL,
    tank_id BIGINT UNSIGNED,
    test_date DATE NOT NULL,
    test_type ENUM('routine', 'delivery', 'complaint', 'regulatory') NOT NULL,
    density DECIMAL(6,4),
    octane_rating INT,
    water_content DECIMAL(5,3),
    contamination_level ENUM('clean', 'slight', 'moderate', 'heavy'),
    color_grade VARCHAR(50),
    test_result ENUM('pass', 'fail', 'marginal') NOT NULL,
    tested_by VARCHAR(255),
    lab_reference VARCHAR(100),
    notes TEXT,
    corrective_action TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE CASCADE,
    FOREIGN KEY (tank_id) REFERENCES storage_tanks(id) ON DELETE SET NULL
);

-- Incidents/Safety Reports Table
CREATE TABLE safety_incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    incident_type ENUM('spill', 'fire', 'injury', 'equipment_failure', 'security', 'other') NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME NOT NULL,
    location_description TEXT,
    severity ENUM('minor', 'major', 'critical') NOT NULL,
    description TEXT NOT NULL,
    immediate_action_taken TEXT,
    reported_by BIGINT UNSIGNED,
    investigated_by BIGINT UNSIGNED,
    investigation_notes TEXT,
    corrective_measures TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    status ENUM('reported', 'investigating', 'closed') DEFAULT 'reported',
    regulatory_reported BOOLEAN DEFAULT FALSE,
    regulatory_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (investigated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Cash Float Management Table
CREATE TABLE cash_float (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    float_date DATE NOT NULL,
    opening_float DECIMAL(10,2) DEFAULT 0.00,
    cash_sales DECIMAL(10,2) DEFAULT 0.00,
    cash_expenses DECIMAL(10,2) DEFAULT 0.00,
    expected_closing_float DECIMAL(10,2) DEFAULT 0.00,
    actual_closing_float DECIMAL(10,2) DEFAULT 0.00,
    variance DECIMAL(10,2) DEFAULT 0.00,
    denomination_1000 INT DEFAULT 0,
    denomination_5000 INT DEFAULT 0,
    denomination_10000 INT DEFAULT 0,
    denomination_20000 INT DEFAULT 0,
    denomination_50000 INT DEFAULT 0,
    denomination_coins DECIMAL(8,2) DEFAULT 0.00,
    prepared_by BIGINT UNSIGNED,
    verified_by BIGINT UNSIGNED,
    status ENUM('draft', 'submitted', 'approved', 'discrepancy') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_branch_date (branch_id, float_date)
);

-- Bank Reconciliation Table
CREATE TABLE bank_reconciliation (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    statement_date DATE NOT NULL,
    book_balance DECIMAL(15,2) NOT NULL,
    bank_balance DECIMAL(15,2) NOT NULL,
    reconciled_balance DECIMAL(15,2) NOT NULL,
    total_deposits_in_transit DECIMAL(15,2) DEFAULT 0.00,
    total_outstanding_checks DECIMAL(15,2) DEFAULT 0.00,
    total_bank_charges DECIMAL(15,2) DEFAULT 0.00,
    total_interest_earned DECIMAL(15,2) DEFAULT 0.00,
    reconciled_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    status ENUM('draft', 'reconciled', 'approved') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (reconciled_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Fuel Losses/Variances Table
CREATE TABLE fuel_variances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    tank_id BIGINT UNSIGNED NOT NULL,
    variance_date DATE NOT NULL,
    expected_quantity DECIMAL(10,2) NOT NULL,
    actual_quantity DECIMAL(10,2) NOT NULL,
    variance_quantity DECIMAL(10,2) NOT NULL,
    variance_type ENUM('gain', 'loss') NOT NULL,
    variance_reason ENUM('evaporation', 'theft', 'measurement_error', 'spillage', 'other') NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    variance_value DECIMAL(15,2) NOT NULL,
    investigation_notes TEXT,
    corrective_action TEXT,
    approved_by BIGINT UNSIGNED,
    status ENUM('pending', 'approved', 'written_off') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (tank_id) REFERENCES storage_tanks(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- ADDITIONAL INDEXES FOR NEW TABLES
-- =====================================================

-- Price History Indexes
CREATE INDEX idx_fuel_price_history_branch_fuel ON fuel_price_history(branch_id, fuel_type_id);
CREATE INDEX idx_fuel_price_history_date ON fuel_price_history(effective_date);

-- Notifications Indexes
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_branch ON notifications(branch_id);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);

-- Voucher Indexes
CREATE INDEX idx_fuel_vouchers_branch ON fuel_vouchers(branch_id);
CREATE INDEX idx_fuel_vouchers_status ON fuel_vouchers(status);
CREATE INDEX idx_fuel_vouchers_expiry ON fuel_vouchers(expiry_date);

-- Loyalty Program Indexes
CREATE INDEX idx_loyalty_customers_phone ON loyalty_customers(phone);
CREATE INDEX idx_loyalty_customers_branch ON loyalty_customers(registration_branch_id);
CREATE INDEX idx_loyalty_points_customer ON loyalty_points_history(customer_id);

-- Quality Tests Indexes
CREATE INDEX idx_fuel_quality_tests_branch_date ON fuel_quality_tests(branch_id, test_date);
CREATE INDEX idx_fuel_quality_tests_tank ON fuel_quality_tests(tank_id);

-- Safety Incidents Indexes
CREATE INDEX idx_safety_incidents_branch_date ON safety_incidents(branch_id, incident_date);
CREATE INDEX idx_safety_incidents_status ON safety_incidents(status);
CREATE INDEX idx_safety_incidents_severity ON safety_incidents(severity);

-- Cash Float Indexes
CREATE INDEX idx_cash_float_branch_date ON cash_float(branch_id, float_date);
CREATE INDEX idx_cash_float_status ON cash_float(status);

-- Bank Reconciliation Indexes
CREATE INDEX idx_bank_reconciliation_branch_date ON bank_reconciliation(branch_id, statement_date);
CREATE INDEX idx_bank_reconciliation_status ON bank_reconciliation(status);

-- Fuel Variances Indexes
CREATE INDEX idx_fuel_variances_branch_date ON fuel_variances(branch_id, variance_date);
CREATE INDEX idx_fuel_variances_tank ON fuel_variances(tank_id);
CREATE INDEX idx_fuel_variances_status ON fuel_variances(status);

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- Current Fuel Prices View
CREATE VIEW current_fuel_prices AS
SELECT 
    b.id as branch_id,
    b.branch_name,
    ft.id as fuel_type_id,
    ft.name as fuel_name,
    ft.code as fuel_code,
    fd.pump_price as current_price,
    fd.status as dispenser_status
FROM branches b
JOIN fuel_dispensers fd ON b.id = fd.branch_id
JOIN storage_tanks st ON fd.tank_id = st.id
JOIN fuel_types ft ON st.fuel_type_id = ft.id
WHERE b.deleted_at IS NULL 
AND fd.deleted_at IS NULL 
AND st.deleted_at IS NULL 
AND ft.deleted_at IS NULL;

-- Current Stock Levels View
CREATE VIEW current_stock_levels AS
SELECT 
    b.id as branch_id,
    b.branch_name,
    st.id as tank_id,
    st.tank_number,
    ft.name as fuel_type,
    st.capacity,
    st.current_level,
    st.minimum_level,
    (st.current_level / st.capacity * 100) as fill_percentage,
    CASE 
        WHEN st.current_level <= st.minimum_level THEN 'Low'
        WHEN st.current_level / st.capacity < 0.25 THEN 'Quarter'
        WHEN st.current_level / st.capacity < 0.5 THEN 'Half'
        WHEN st.current_level / st.capacity < 0.75 THEN 'Three Quarter'
        ELSE 'Full'
    END as stock_status
FROM branches b
JOIN storage_tanks st ON b.id = st.branch_id
JOIN fuel_types ft ON st.fuel_type_id = ft.id
WHERE b.deleted_at IS NULL 
AND st.deleted_at IS NULL 
AND ft.deleted_at IS NULL;

-- Daily Sales Summary View
CREATE VIEW daily_sales_view AS
SELECT 
    st.branch_id,
    b.branch_name,
    st.transaction_date,
    COUNT(*) as total_transactions,
    SUM(st.quantity) as total_quantity,
    SUM(st.final_amount) as total_sales,
    SUM(CASE WHEN st.payment_method = 'cash' THEN st.final_amount ELSE 0 END) as cash_sales,
    SUM(CASE WHEN st.payment_method = 'card' THEN st.final_amount ELSE 0 END) as card_sales,
    SUM(CASE WHEN st.payment_method = 'mobile_money' THEN st.final_amount ELSE 0 END) as mobile_money_sales,
    SUM(CASE WHEN st.payment_method = 'credit' THEN st.final_amount ELSE 0 END) as credit_sales
FROM sales_transactions st
JOIN branches b ON st.branch_id = b.id
WHERE st.deleted_at IS NULL 
AND b.deleted_at IS NULL
GROUP BY st.branch_id, st.transaction_date;

-- Outstanding Credit View
CREATE VIEW outstanding_credit_view AS
SELECT 
    ca.id as customer_id,
    ca.customer_code,
    ca.company_name,
    ca.contact_person,
    ca.phone,
    ca.credit_limit,
    ca.current_balance,
    COUNT(cs.id) as outstanding_invoices,
    SUM(cs.remaining_balance) as total_outstanding,
    MIN(cs.due_date) as oldest_due_date,
    MAX(cs.due_date) as newest_due_date
FROM customer_accounts ca
LEFT JOIN credit_sales cs ON ca.id = cs.customer_account_id
WHERE ca.deleted_at IS NULL 
AND cs.deleted_at IS NULL
AND cs.payment_status IN ('pending', 'partial', 'overdue')
GROUP BY ca.id;

-- =====================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- =====================================================

-- Update Tank Level Procedure
DELIMITER //
CREATE PROCEDURE UpdateTankLevel(
    IN tank_id BIGINT,
    IN new_level DECIMAL(10,2),
    IN reading_type ENUM('opening', 'closing', 'delivery', 'manual'),
    IN taken_by BIGINT,
    IN notes TEXT
)
BEGIN
    DECLARE current_date DATE DEFAULT CURDATE();
    DECLARE current_time TIME DEFAULT CURTIME();
    
    -- Update tank current level
    UPDATE storage_tanks 
    SET current_level = new_level,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = tank_id AND deleted_at IS NULL;
    
    -- Insert tank reading record
    INSERT INTO tank_readings (
        tank_id, reading_date, reading_time, fuel_level, 
        reading_type, taken_by, notes
    ) VALUES (
        tank_id, current_date, current_time, new_level, 
        reading_type, taken_by, notes
    );
END //
DELIMITER ;

-- Process Fuel Sale Procedure
DELIMITER //
CREATE PROCEDURE ProcessFuelSale(
    IN p_branch_id BIGINT,
    IN p_dispenser_id BIGINT,
    IN p_quantity DECIMAL(10,2),
    IN p_unit_price DECIMAL(10,2),
    IN p_payment_method VARCHAR(50),
    IN p_attendant_id BIGINT,
    IN p_customer_name VARCHAR(255),
    IN p_vehicle_plate VARCHAR(50),
    OUT p_transaction_id BIGINT,
    OUT p_transaction_number VARCHAR(100)
)
BEGIN
    DECLARE v_fuel_type_id BIGINT;
    DECLARE v_tank_id BIGINT;
    DECLARE v_total_amount DECIMAL(15,2);
    DECLARE v_tax_amount DECIMAL(10,2);
    DECLARE v_final_amount DECIMAL(15,2);
    DECLARE v_current_level DECIMAL(10,2);
    DECLARE v_new_level DECIMAL(10,2);
    DECLARE v_tax_rate DECIMAL(5,2) DEFAULT 18.0;
    
    -- Get fuel type and tank information
    SELECT st.fuel_type_id, fd.tank_id, st.current_level
    INTO v_fuel_type_id, v_tank_id, v_current_level
    FROM fuel_dispensers fd
    JOIN storage_tanks st ON fd.tank_id = st.id
    WHERE fd.id = p_dispenser_id;
    
    -- Calculate amounts
    SET v_total_amount = p_quantity * p_unit_price;
    SET v_tax_amount = v_total_amount * (v_tax_rate / 100);
    SET v_final_amount = v_total_amount + v_tax_amount;
    
    -- Generate transaction number
    SET p_transaction_number = CONCAT('TXN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Insert sales transaction
    INSERT INTO sales_transactions (
        branch_id, dispenser_id, fuel_type_id, transaction_number,
        quantity, unit_price, total_amount, tax_amount, final_amount,
        payment_method, customer_name, vehicle_plate, attendant_id,
        transaction_date, transaction_time
    ) VALUES (
        p_branch_id, p_dispenser_id, v_fuel_type_id, p_transaction_number,
        p_quantity, p_unit_price, v_total_amount, v_tax_amount, v_final_amount,
        p_payment_method, p_customer_name, p_vehicle_plate, p_attendant_id,
        CURDATE(), CURTIME()
    );
    
    SET p_transaction_id = LAST_INSERT_ID();
    
    -- Update tank level
    SET v_new_level = v_current_level - p_quantity;
    CALL UpdateTankLevel(v_tank_id, v_new_level, 'manual', p_attendant_id, 'Fuel sale transaction');
    
END //
DELIMITER ;

-- =====================================================
-- TRIGGERS FOR AUDIT LOGGING
-- =====================================================

-- Sales Transaction Audit Trigger
DELIMITER //
CREATE TRIGGER sales_transactions_audit_insert
AFTER INSERT ON sales_transactions
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values)
    VALUES (NEW.attendant_id, 'INSERT', 'sales_transactions', NEW.id, JSON_OBJECT(
        'transaction_number', NEW.transaction_number,
        'quantity', NEW.quantity,
        'final_amount', NEW.final_amount,
        'payment_method', NEW.payment_method
    ));
END //
DELIMITER ;

-- User Update Audit Trigger
DELIMITER //
CREATE TRIGGER users_audit_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values)
    VALUES (NEW.id, 'UPDATE', 'users', NEW.id, 
        JSON_OBJECT('username', OLD.username, 'email', OLD.email, 'status', OLD.status),
        JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'status', NEW.status)
    );
END //
DELIMITER ;

-- =====================================================
-- FUNCTIONS FOR CALCULATIONS
-- =====================================================

-- Calculate Tank Fill Percentage Function
DELIMITER //
CREATE FUNCTION CalculateTankFillPercentage(tank_id BIGINT) 
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE fill_percentage DECIMAL(5,2);
    
    SELECT (current_level / capacity * 100) INTO fill_percentage
    FROM storage_tanks 
    WHERE id = tank_id AND deleted_at IS NULL;
    
    RETURN IFNULL(fill_percentage, 0.00);
END //
DELIMITER ;

-- Calculate Monthly Sales Function
DELIMITER //
CREATE FUNCTION CalculateMonthlySales(branch_id BIGINT, month_year DATE) 
RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE monthly_sales DECIMAL(15,2);
    
    SELECT IFNULL(SUM(final_amount), 0.00) INTO monthly_sales
    FROM sales_transactions 
    WHERE branch_id = branch_id 
    AND DATE_FORMAT(transaction_date, '%Y-%m') = DATE_FORMAT(month_year, '%Y-%m')
    AND deleted_at IS NULL;
    
    RETURN monthly_sales;
END //
DELIMITER ;

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert Sample Business
INSERT INTO businesses (business_name, business_type, tin_number, phone, email, address, city, district, region) 
VALUES ('Uganda Fuel Solutions Ltd', 'limited_company', 'TIN123456789', '+256701234567', 'info@ugandafuel.com', 'Plot 123 Industrial Area', 'Kampala', 'Kampala', 'Central');

-- Insert Sample Branches
INSERT INTO branches (business_id, branch_code, branch_name, address, city, district, region, manager_name, manager_phone) 
VALUES 
(1, 'UFS001', 'Main Branch - Kampala', 'Plot 123 Industrial Area', 'Kampala', 'Kampala', 'Central', 'John Mukasa', '+256701234567'),
(1, 'UFS002', 'Entebbe Branch', 'Entebbe Road', 'Entebbe', 'Wakiso', 'Central', 'Mary Namuli', '+256701234568');

-- Insert Sample Admin User
INSERT INTO users (business_id, username, email, password, first_name, last_name, phone, status) 
VALUES (1, 'admin', 'admin@ugandafuel.com', '$2y$10$hash_password_here', 'System', 'Administrator', '+256701234567', 'active');

-- Assign Super Admin Role
INSERT INTO user_roles (user_id, role_id, assigned_by) 
VALUES (1, 1, 1);

-- =====================================================
-- DATABASE SETUP COMPLETE
-- =====================================================

-- Grant permissions (adjust as needed for your environment)
-- GRANT ALL PRIVILEGES ON uganda_fuel_stations.* TO 'fuel_admin'@'localhost';
-- FLUSH PRIVILEGES;

-- Create backup user (optional)
-- CREATE USER 'fuel_backup'@'localhost' IDENTIFIED BY 'backup_password';
-- GRANT SELECT, LOCK TABLES ON uganda_fuel_stations.* TO 'fuel_backup'@'localhost';

SELECT 'Uganda Fuel Station Management System Database Setup Complete!' as Status;

-- Employees Table
CREATE TABLE employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    employee_code VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    national_id VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(191),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    position VARCHAR(100),
    hire_date DATE,
    termination_date DATE,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    user_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);