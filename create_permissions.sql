-- =====================================================
-- COMPREHENSIVE PERMISSIONS FOR FUEL STATION MANAGEMENT SYSTEM
-- Based on Sidebar Modules and Database Tables
-- =====================================================

-- Clear existing permissions (optional - uncomment if needed)
-- DELETE FROM role_permissions;
-- DELETE FROM permissions;

-- =====================================================
-- DASHBOARD PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
('dashboard.view', 'View Dashboard', 'Access to view dashboard and overview statistics', 'dashboard'),
('dashboard.analytics', 'Dashboard Analytics', 'View detailed analytics and charts on dashboard', 'dashboard'),
('dashboard.export', 'Export Dashboard Data', 'Export dashboard data and reports', 'dashboard');

-- =====================================================
-- USER MANAGEMENT PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Users
('users.view', 'View Users', 'View user information and list', 'user_management'),
('users.create', 'Create Users', 'Create new user accounts', 'user_management'),
('users.update', 'Update Users', 'Edit user information and profiles', 'user_management'),
('users.delete', 'Delete Users', 'Delete user accounts (soft delete)', 'user_management'),
('users.activate', 'Activate Users', 'Activate suspended user accounts', 'user_management'),
('users.suspend', 'Suspend Users', 'Suspend user accounts', 'user_management'),
('users.reset_password', 'Reset User Passwords', 'Reset user passwords', 'user_management'),
('users.view_profile', 'View User Profiles', 'View detailed user profiles', 'user_management'),

-- Roles
('roles.view', 'View Roles', 'View role information and list', 'user_management'),
('roles.create', 'Create Roles', 'Create new roles', 'user_management'),
('roles.update', 'Update Roles', 'Edit role information', 'user_management'),
('roles.delete', 'Delete Roles', 'Delete roles (soft delete)', 'user_management'),
('roles.assign', 'Assign Roles', 'Assign roles to users', 'user_management'),

-- Permissions
('permissions.view', 'View Permissions', 'View permission information and list', 'user_management'),
('permissions.create', 'Create Permissions', 'Create new permissions', 'user_management'),
('permissions.update', 'Update Permissions', 'Edit permission information', 'user_management'),
('permissions.delete', 'Delete Permissions', 'Delete permissions (soft delete)', 'user_management'),

-- Role Permissions
('role_permissions.view', 'View Role Permissions', 'View role-permission assignments', 'user_management'),
('role_permissions.assign', 'Assign Role Permissions', 'Assign permissions to roles', 'user_management'),
('role_permissions.revoke', 'Revoke Role Permissions', 'Remove permissions from roles', 'user_management');

-- =====================================================
-- BUSINESS & BRANCH MANAGEMENT PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Businesses
('businesses.view', 'View Businesses', 'View business information and list', 'business_management'),
('businesses.create', 'Create Businesses', 'Create new business entities', 'business_management'),
('businesses.update', 'Update Businesses', 'Edit business information', 'business_management'),
('businesses.delete', 'Delete Businesses', 'Delete businesses (soft delete)', 'business_management'),
('businesses.activate', 'Activate Businesses', 'Activate suspended businesses', 'business_management'),
('businesses.suspend', 'Suspend Businesses', 'Suspend business operations', 'business_management'),

-- Branches
('branches.view', 'View Branches', 'View branch information and list', 'business_management'),
('branches.create', 'Create Branches', 'Create new branch locations', 'business_management'),
('branches.update', 'Update Branches', 'Edit branch information', 'business_management'),
('branches.delete', 'Delete Branches', 'Delete branches (soft delete)', 'business_management'),
('branches.activate', 'Activate Branches', 'Activate suspended branches', 'business_management'),
('branches.suspend', 'Suspend Branches', 'Suspend branch operations', 'business_management'),
('branches.dashboard', 'Branch Dashboard', 'Access branch-specific dashboard', 'business_management'),
('branches.switch', 'Switch Branches', 'Switch between different branches', 'business_management');

-- =====================================================
-- INVENTORY & FUEL MANAGEMENT PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Fuel Types
('fuel_types.view', 'View Fuel Types', 'View fuel type information and list', 'inventory_management'),
('fuel_types.create', 'Create Fuel Types', 'Create new fuel types', 'inventory_management'),
('fuel_types.update', 'Update Fuel Types', 'Edit fuel type information', 'inventory_management'),
('fuel_types.delete', 'Delete Fuel Types', 'Delete fuel types (soft delete)', 'inventory_management'),
('fuel_types.activate', 'Activate Fuel Types', 'Activate fuel types', 'inventory_management'),
('fuel_types.deactivate', 'Deactivate Fuel Types', 'Deactivate fuel types', 'inventory_management'),

-- Storage Tanks
('storage_tanks.view', 'View Storage Tanks', 'View tank information and levels', 'inventory_management'),
('storage_tanks.create', 'Create Storage Tanks', 'Create new storage tanks', 'inventory_management'),
('storage_tanks.update', 'Update Storage Tanks', 'Edit tank information', 'inventory_management'),
('storage_tanks.delete', 'Delete Storage Tanks', 'Delete storage tanks (soft delete)', 'inventory_management'),
('storage_tanks.readings', 'Tank Readings', 'Record and view tank readings', 'inventory_management'),
('storage_tanks.calibration', 'Tank Calibration', 'Manage tank calibration schedules', 'inventory_management'),

-- Fuel Dispensers
('fuel_dispensers.view', 'View Fuel Dispensers', 'View dispenser information and status', 'inventory_management'),
('fuel_dispensers.create', 'Create Fuel Dispensers', 'Create new fuel dispensers', 'inventory_management'),
('fuel_dispensers.update', 'Update Fuel Dispensers', 'Edit dispenser information', 'inventory_management'),
('fuel_dispensers.delete', 'Delete Fuel Dispensers', 'Delete fuel dispensers (soft delete)', 'inventory_management'),
('fuel_dispensers.pricing', 'Dispenser Pricing', 'Update fuel prices on dispensers', 'inventory_management'),
('fuel_dispensers.calibration', 'Dispenser Calibration', 'Manage dispenser calibration', 'inventory_management'),

-- Fuel Purchases/Deliveries
('fuel_purchases.view', 'View Fuel Purchases', 'View fuel purchase and delivery records', 'inventory_management'),
('fuel_purchases.create', 'Create Fuel Purchases', 'Record new fuel purchases/deliveries', 'inventory_management'),
('fuel_purchases.update', 'Update Fuel Purchases', 'Edit fuel purchase records', 'inventory_management'),
('fuel_purchases.delete', 'Delete Fuel Purchases', 'Delete fuel purchase records (soft delete)', 'inventory_management'),
('fuel_purchases.approve', 'Approve Fuel Purchases', 'Approve fuel purchase orders', 'inventory_management'),
('fuel_purchases.receive', 'Receive Fuel Deliveries', 'Process fuel deliveries', 'inventory_management'),

-- Suppliers
('suppliers.view', 'View Suppliers', 'View supplier information and list', 'inventory_management'),
('suppliers.create', 'Create Suppliers', 'Create new supplier records', 'inventory_management'),
('suppliers.update', 'Update Suppliers', 'Edit supplier information', 'inventory_management'),
('suppliers.delete', 'Delete Suppliers', 'Delete supplier records (soft delete)', 'inventory_management'),
('suppliers.rate', 'Rate Suppliers', 'Rate and review suppliers', 'inventory_management'),

-- Fuel Variances
('fuel_variances.view', 'View Fuel Variances', 'View fuel loss/gain reports', 'inventory_management'),
('fuel_variances.create', 'Create Fuel Variances', 'Record fuel variances', 'inventory_management'),
('fuel_variances.update', 'Update Fuel Variances', 'Edit fuel variance records', 'inventory_management'),
('fuel_variances.approve', 'Approve Fuel Variances', 'Approve fuel variance write-offs', 'inventory_management');

-- =====================================================
-- SALES & TRANSACTIONS PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Sales Transactions
('sales_transactions.view', 'View Sales Transactions', 'View sales transaction records', 'sales_management'),
('sales_transactions.create', 'Create Sales Transactions', 'Process new fuel sales', 'sales_management'),
('sales_transactions.update', 'Update Sales Transactions', 'Edit sales transaction records', 'sales_management'),
('sales_transactions.delete', 'Delete Sales Transactions', 'Delete sales records (soft delete)', 'sales_management'),
('sales_transactions.refund', 'Process Refunds', 'Process sales refunds', 'sales_management'),
('sales_transactions.void', 'Void Transactions', 'Void sales transactions', 'sales_management'),

-- Customer Accounts
('customer_accounts.view', 'View Customer Accounts', 'View customer account information', 'sales_management'),
('customer_accounts.create', 'Create Customer Accounts', 'Create new customer accounts', 'sales_management'),
('customer_accounts.update', 'Update Customer Accounts', 'Edit customer account information', 'sales_management'),
('customer_accounts.delete', 'Delete Customer Accounts', 'Delete customer accounts (soft delete)', 'sales_management'),
('customer_accounts.credit_limit', 'Manage Credit Limits', 'Set and update customer credit limits', 'sales_management'),

-- Credit Sales
('credit_sales.view', 'View Credit Sales', 'View credit sales records', 'sales_management'),
('credit_sales.create', 'Create Credit Sales', 'Process credit sales transactions', 'sales_management'),
('credit_sales.update', 'Update Credit Sales', 'Edit credit sales records', 'sales_management'),
('credit_sales.approve', 'Approve Credit Sales', 'Approve credit sales transactions', 'sales_management'),
('credit_sales.collect', 'Collect Credit Payments', 'Record credit payment collections', 'sales_management'),

-- Fuel Vouchers
('fuel_vouchers.view', 'View Fuel Vouchers', 'View fuel voucher records', 'sales_management'),
('fuel_vouchers.create', 'Create Fuel Vouchers', 'Issue new fuel vouchers', 'sales_management'),
('fuel_vouchers.update', 'Update Fuel Vouchers', 'Edit fuel voucher information', 'sales_management'),
('fuel_vouchers.delete', 'Delete Fuel Vouchers', 'Cancel fuel vouchers', 'sales_management'),
('fuel_vouchers.redeem', 'Redeem Fuel Vouchers', 'Process fuel voucher redemptions', 'sales_management'),

-- Loyalty Program
('loyalty_customers.view', 'View Loyalty Customers', 'View loyalty program customers', 'sales_management'),
('loyalty_customers.create', 'Create Loyalty Customers', 'Register new loyalty customers', 'sales_management'),
('loyalty_customers.update', 'Update Loyalty Customers', 'Edit loyalty customer information', 'sales_management'),
('loyalty_customers.points', 'Manage Loyalty Points', 'Adjust loyalty points balance', 'sales_management');

-- =====================================================
-- FINANCIAL MANAGEMENT PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Daily Sales Summary
('daily_sales_summary.view', 'View Daily Sales Summary', 'View daily sales summary reports', 'financial_management'),
('daily_sales_summary.create', 'Create Daily Sales Summary', 'Generate daily sales summaries', 'financial_management'),
('daily_sales_summary.update', 'Update Daily Sales Summary', 'Edit daily sales summary records', 'financial_management'),
('daily_sales_summary.approve', 'Approve Daily Sales Summary', 'Approve daily sales summaries', 'financial_management'),
('daily_sales_summary.export', 'Export Daily Sales Summary', 'Export daily sales data', 'financial_management'),

-- Expenses
('expenses.view', 'View Expenses', 'View expense records and reports', 'financial_management'),
('expenses.create', 'Create Expenses', 'Record new expenses', 'financial_management'),
('expenses.update', 'Update Expenses', 'Edit expense records', 'financial_management'),
('expenses.delete', 'Delete Expenses', 'Delete expense records (soft delete)', 'financial_management'),
('expenses.approve', 'Approve Expenses', 'Approve expense claims', 'financial_management'),
('expenses.categorize', 'Categorize Expenses', 'Assign expense categories', 'financial_management'),

-- Cash Float
('cash_float.view', 'View Cash Float', 'View cash float records', 'financial_management'),
('cash_float.create', 'Create Cash Float', 'Record opening cash float', 'financial_management'),
('cash_float.update', 'Update Cash Float', 'Update cash float records', 'financial_management'),
('cash_float.verify', 'Verify Cash Float', 'Verify cash float counts', 'financial_management'),
('cash_float.approve', 'Approve Cash Float', 'Approve cash float reports', 'financial_management'),

-- Bank Reconciliation
('bank_reconciliation.view', 'View Bank Reconciliation', 'View bank reconciliation reports', 'financial_management'),
('bank_reconciliation.create', 'Create Bank Reconciliation', 'Perform bank reconciliation', 'financial_management'),
('bank_reconciliation.update', 'Update Bank Reconciliation', 'Edit reconciliation records', 'financial_management'),
('bank_reconciliation.approve', 'Approve Bank Reconciliation', 'Approve reconciliation reports', 'financial_management');

-- =====================================================
-- SHIFT MANAGEMENT PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Shifts
('shifts.view', 'View Shifts', 'View shift schedules and information', 'shift_management'),
('shifts.create', 'Create Shifts', 'Create new shift schedules', 'shift_management'),
('shifts.update', 'Update Shifts', 'Edit shift information', 'shift_management'),
('shifts.delete', 'Delete Shifts', 'Delete shift schedules (soft delete)', 'shift_management'),
('shifts.activate', 'Activate Shifts', 'Activate shift schedules', 'shift_management'),
('shifts.deactivate', 'Deactivate Shifts', 'Deactivate shift schedules', 'shift_management'),

-- Shift Assignments
('shift_assignments.view', 'View Shift Assignments', 'View shift assignments and schedules', 'shift_management'),
('shift_assignments.create', 'Create Shift Assignments', 'Assign employees to shifts', 'shift_management'),
('shift_assignments.update', 'Update Shift Assignments', 'Edit shift assignments', 'shift_management'),
('shift_assignments.delete', 'Delete Shift Assignments', 'Remove shift assignments', 'shift_management'),
('shift_assignments.clock_in', 'Clock In', 'Record employee clock-in times', 'shift_management'),
('shift_assignments.clock_out', 'Clock Out', 'Record employee clock-out times', 'shift_management'),
('shift_assignments.approve', 'Approve Shift Reports', 'Approve shift completion reports', 'shift_management');

-- =====================================================
-- MAINTENANCE & COMPLIANCE PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Equipment Maintenance
('equipment_maintenance.view', 'View Equipment Maintenance', 'View maintenance schedules and records', 'maintenance_management'),
('equipment_maintenance.create', 'Create Equipment Maintenance', 'Schedule new maintenance tasks', 'maintenance_management'),
('equipment_maintenance.update', 'Update Equipment Maintenance', 'Edit maintenance records', 'maintenance_management'),
('equipment_maintenance.delete', 'Delete Equipment Maintenance', 'Delete maintenance records (soft delete)', 'maintenance_management'),
('equipment_maintenance.perform', 'Perform Maintenance', 'Record maintenance completion', 'maintenance_management'),
('equipment_maintenance.approve', 'Approve Maintenance', 'Approve maintenance work', 'maintenance_management'),

-- Regulatory Compliance
('regulatory_compliance.view', 'View Regulatory Compliance', 'View compliance requirements and status', 'maintenance_management'),
('regulatory_compliance.create', 'Create Regulatory Compliance', 'Add new compliance requirements', 'maintenance_management'),
('regulatory_compliance.update', 'Update Regulatory Compliance', 'Edit compliance records', 'maintenance_management'),
('regulatory_compliance.delete', 'Delete Regulatory Compliance', 'Delete compliance records (soft delete)', 'maintenance_management'),
('regulatory_compliance.complete', 'Complete Compliance', 'Mark compliance requirements as completed', 'maintenance_management'),
('regulatory_compliance.report', 'Report Compliance', 'Generate compliance reports', 'maintenance_management'),

-- Fuel Quality Tests
('fuel_quality_tests.view', 'View Fuel Quality Tests', 'View fuel quality test results', 'maintenance_management'),
('fuel_quality_tests.create', 'Create Fuel Quality Tests', 'Schedule new fuel quality tests', 'maintenance_management'),
('fuel_quality_tests.update', 'Update Fuel Quality Tests', 'Edit test results', 'maintenance_management'),
('fuel_quality_tests.delete', 'Delete Fuel Quality Tests', 'Delete test records (soft delete)', 'maintenance_management'),
('fuel_quality_tests.perform', 'Perform Quality Tests', 'Record test results', 'maintenance_management'),
('fuel_quality_tests.approve', 'Approve Test Results', 'Approve quality test results', 'maintenance_management'),

-- Safety Incidents
('safety_incidents.view', 'View Safety Incidents', 'View safety incident reports', 'maintenance_management'),
('safety_incidents.create', 'Create Safety Incidents', 'Report new safety incidents', 'maintenance_management'),
('safety_incidents.update', 'Update Safety Incidents', 'Edit incident reports', 'maintenance_management'),
('safety_incidents.delete', 'Delete Safety Incidents', 'Delete incident reports (soft delete)', 'maintenance_management'),
('safety_incidents.investigate', 'Investigate Incidents', 'Conduct incident investigations', 'maintenance_management'),
('safety_incidents.resolve', 'Resolve Incidents', 'Mark incidents as resolved', 'maintenance_management'),
('safety_incidents.report', 'Report to Authorities', 'Report incidents to regulatory authorities', 'maintenance_management');

-- =====================================================
-- REPORTING & ANALYTICS PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Reports
('reports.view', 'View Reports', 'Access to view system reports', 'reporting'),
('reports.generate', 'Generate Reports', 'Generate custom reports', 'reporting'),
('reports.export', 'Export Reports', 'Export reports in various formats', 'reporting'),
('reports.schedule', 'Schedule Reports', 'Schedule automated report generation', 'reporting'),
('reports.delete', 'Delete Reports', 'Delete generated reports', 'reporting'),

-- Outstanding Credit
('outstanding_credit.view', 'View Outstanding Credit', 'View outstanding credit reports', 'reporting'),
('outstanding_credit.manage', 'Manage Outstanding Credit', 'Manage credit collection activities', 'reporting'),
('outstanding_credit.export', 'Export Credit Reports', 'Export outstanding credit data', 'reporting'),

-- Analytics
('analytics.view', 'View Analytics', 'Access to analytics dashboard', 'reporting'),
('analytics.sales', 'Sales Analytics', 'View sales performance analytics', 'reporting'),
('analytics.inventory', 'Inventory Analytics', 'View inventory performance analytics', 'reporting'),
('analytics.financial', 'Financial Analytics', 'View financial performance analytics', 'reporting'),
('analytics.export', 'Export Analytics', 'Export analytics data', 'reporting');

-- =====================================================
-- SYSTEM ADMINISTRATION PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- System Settings
('system_settings.view', 'View System Settings', 'View system configuration settings', 'system_administration'),
('system_settings.update', 'Update System Settings', 'Modify system configuration', 'system_administration'),
('system_settings.backup', 'System Backup', 'Perform system backups', 'system_administration'),
('system_settings.restore', 'System Restore', 'Restore system from backup', 'system_administration'),

-- Audit Logs
('audit_logs.view', 'View Audit Logs', 'View system audit trail', 'system_administration'),
('audit_logs.export', 'Export Audit Logs', 'Export audit log data', 'system_administration'),
('audit_logs.purge', 'Purge Audit Logs', 'Clean up old audit log entries', 'system_administration'),

-- Notifications
('notifications.view', 'View Notifications', 'View system notifications', 'system_administration'),
('notifications.create', 'Create Notifications', 'Send system notifications', 'system_administration'),
('notifications.update', 'Update Notifications', 'Edit notification settings', 'system_administration'),
('notifications.delete', 'Delete Notifications', 'Delete notification records', 'system_administration'),
('notifications.settings', 'Notification Settings', 'Configure notification preferences', 'system_administration');

-- =====================================================
-- ADDITIONAL SPECIALIZED PERMISSIONS
-- =====================================================
INSERT INTO permissions (name, display_name, description, module) VALUES
-- Price Management
('fuel_prices.view', 'View Fuel Prices', 'View current fuel prices', 'inventory_management'),
('fuel_prices.update', 'Update Fuel Prices', 'Update fuel prices across branches', 'inventory_management'),
('fuel_prices.history', 'View Price History', 'View fuel price change history', 'inventory_management'),

-- Customer Management
('customers.view', 'View Customers', 'View customer information', 'sales_management'),
('customers.create', 'Create Customers', 'Create new customer records', 'sales_management'),
('customers.update', 'Update Customers', 'Edit customer information', 'sales_management'),
('customers.delete', 'Delete Customers', 'Delete customer records', 'sales_management'),

-- Vehicle Management
('vehicles.view', 'View Vehicles', 'View vehicle information', 'sales_management'),
('vehicles.create', 'Create Vehicles', 'Create new vehicle records', 'sales_management'),
('vehicles.update', 'Update Vehicles', 'Edit vehicle information', 'sales_management'),
('vehicles.delete', 'Delete Vehicles', 'Delete vehicle records', 'sales_management'),

-- Data Import/Export
('data.import', 'Import Data', 'Import data from external sources', 'system_administration'),
('data.export', 'Export Data', 'Export data to external systems', 'system_administration'),
('data.migrate', 'Data Migration', 'Perform data migration tasks', 'system_administration'),

-- API Access
('api.access', 'API Access', 'Access to system APIs', 'system_administration'),
('api.manage', 'Manage API Keys', 'Manage API access keys', 'system_administration'),

-- User Sessions
('sessions.view', 'View User Sessions', 'View active user sessions', 'system_administration'),
('sessions.terminate', 'Terminate Sessions', 'Terminate user sessions', 'system_administration');

-- =====================================================
-- VERIFICATION QUERY
-- =====================================================
-- Uncomment to verify all permissions were created successfully
-- SELECT module, COUNT(*) as permission_count 
-- FROM permissions 
-- WHERE deleted_at IS NULL 
-- GROUP BY module 
-- ORDER BY module;

-- SELECT 'Total Permissions Created: ' || COUNT(*) as summary 
-- FROM permissions 
-- WHERE deleted_at IS NULL;