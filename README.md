# Fuel Station Management System - Function Reference

## Overview
This document provides a comprehensive reference of all functions used in the Fuel Station Management System, including their purpose, parameters, return values, and file locations.

## Table of Contents
1. [Authentication & Authorization Functions](#authentication--authorization-functions)
2. [Email Functions](#email-functions)
3. [User Management Functions](#user-management-functions)
4. [Utility Functions](#utility-functions)
5. [JavaScript Functions](#javascript-functions)
6. [Database Functions](#database-functions)
7. [File Upload Functions](#file-upload-functions)

---

## Authentication & Authorization Functions

### `has_permission($permission_name)`
**File:** `includes/auth_helpers.php`  
**Purpose:** Checks if the current user has a specific permission  
**Parameters:** 
- `$permission_name` (string) - The permission name to check

**Returns:** `boolean` - True if user has permission, false otherwise

**Usage Examples:**
```php
// Check if user can view users
if (has_permission('users.read_all')) {
    // Show user management interface
}

// Check if user can manage roles
if (has_permission('users.manage_roles')) {
    // Show role management interface
}
```

**Used in:**
- `public/manage_users.php` - Line 8
- `public/shift_assignments.php` - Line 6
- `public/employee_management.php` - Throughout the file
- `public/expenses.php` - Throughout the file
- `public/daily_sales_summary.php` - Throughout the file

### `get_sidebar_modules()`
**File:** `includes/auth_helpers.php`  
**Purpose:** Returns the complete sidebar module configuration  
**Parameters:** None  
**Returns:** `array` - Array of sidebar modules with permissions

**Used in:**
- `includes/sidebar.php` - For rendering the navigation menu

### `get_accessible_sidebar_modules()`
**File:** `includes/auth_helpers.php`  
**Purpose:** Returns only the sidebar modules the current user has access to  
**Parameters:** None  
**Returns:** `array` - Filtered array of accessible sidebar modules

**Used in:**
- `includes/sidebar.php` - For rendering user-specific navigation

---

## Email Functions

### `send_email($to, $subject, $body, $altBody = '')`
**File:** `includes/email_helper.php`  
**Purpose:** Sends emails using PHPMailer SMTP  
**Parameters:**
- `$to` (string) - Recipient email address
- `$subject` (string) - Email subject
- `$body` (string) - Email body content
- `$altBody` (string, optional) - Alternative plain text body

**Returns:** `boolean` - True if email sent successfully, false otherwise

**Usage Examples:**
```php
// Send a simple email
$success = send_email('user@example.com', 'Welcome', 'Welcome to our system!');

// Send email with alternative body
$success = send_email(
    'user@example.com', 
    'Account Created', 
    '<h1>Welcome!</h1><p>Your account has been created.</p>',
    'Welcome! Your account has been created.'
);
```

**Used in:**
- `public/manage_users.php` - Line 223 (sendUserCredentials function)
- `includes/email_helper.php` - Line 35 (test_email_config function)

### `test_email_config()`
**File:** `includes/email_helper.php`  
**Purpose:** Tests the email configuration by sending a test email  
**Parameters:** None  
**Returns:** `boolean` - True if test email sent successfully, false otherwise

**Used in:**
- `public/test_email.php` - For testing email functionality

---

## User Management Functions

### `generateRandomPassword($length = 12)`
**File:** `public/manage_users.php`  
**Purpose:** Generates a secure random password for new users  
**Parameters:**
- `$length` (int, optional) - Password length (default: 12)

**Returns:** `string` - Generated password

**Usage Examples:**
```php
// Generate default 12-character password
$password = generateRandomPassword();

// Generate 16-character password
$password = generateRandomPassword(16);
```

**Used in:**
- `public/manage_users.php` - Line 144 (when creating new users)

### `sendUserCredentials($email, $username, $password)`
**File:** `public/manage_users.php`  
**Purpose:** Sends user credentials via email when a new user is created  
**Parameters:**
- `$email` (string) - User's email address
- `$username` (string) - User's username
- `$password` (string) - Generated password

**Returns:** `boolean` - True if email sent successfully, false otherwise

**Usage Examples:**
```php
// Send credentials to new user
$email_sent = sendUserCredentials(
    'user@example.com', 
    'john_doe', 
    'generated_password_123'
);
```

**Used in:**
- `public/manage_users.php` - Line 223 (when creating new users)

---

## Utility Functions

### `h($str)`
**File:** Multiple files  
**Purpose:** Safe HTML output helper function  
**Parameters:**
- `$str` (mixed) - String to escape

**Returns:** `string` - HTML-escaped string

**Usage Examples:**
```php
// Safe output of user data
echo h($user['username']);
echo h($user['email']);
```

**Used in:**
- `public/shift_assignments.php` - Line 27
- `public/shifts.php` - Line 79
- `public/search.php` - Line 142
- `public/reports.php` - Line 64
- `public/purchases.php` - Line 56
- `public/profile.php` - Line 22
- `public/overall_report.php` - Line 6
- `public/notifications.php` - Line 31
- `public/manage_businesses.php` - Line 82
- `public/fuel_type_info.php` - Line 46
- `public/expenses.php` - Line 68
- `public/daily_sales_summary.php` - Line 36

### `is_super_admin()`
**File:** `public/expenses.php`, `public/daily_sales_summary.php`  
**Purpose:** Checks if the current user is a super admin  
**Parameters:** None  
**Returns:** `boolean` - True if user is super admin, false otherwise

**Used in:**
- `public/expenses.php` - Line 47
- `public/daily_sales_summary.php` - Line 16

---

## JavaScript Functions

### `showEntries(entriesPerPage)`
**File:** `public/manage_users.php`  
**Purpose:** Shows specified number of entries in the users table  
**Parameters:**
- `entriesPerPage` (number) - Number of entries to show

**Returns:** `void`

**Used in:**
- `public/manage_users.php` - Line 1488 (pagination functionality)

### `updatePagination(entriesPerPage)`
**File:** `public/manage_users.php`  
**Purpose:** Updates pagination controls based on entries per page  
**Parameters:**
- `entriesPerPage` (number) - Number of entries per page

**Returns:** `void`

**Used in:**
- `public/manage_users.php` - Line 1504 (pagination functionality)

### `showFormError(form, message)`
**File:** `public/manage_users.php`  
**Purpose:** Displays error messages in forms  
**Parameters:**
- `form` (HTMLElement) - Form element
- `message` (string) - Error message to display

**Returns:** `void`

**Used in:**
- `public/manage_users.php` - Line 1617 (form validation)

### `isValidEmail(email)`
**File:** `public/manage_users.php`  
**Purpose:** Validates email format using regex  
**Parameters:**
- `email` (string) - Email address to validate

**Returns:** `boolean` - True if valid email, false otherwise

**Used in:**
- `public/manage_users.php` - Line 1629 (form validation)

### `updateTotalCost(prefix = '')`
**File:** `public/fuel_type_info.php`  
**Purpose:** Updates total cost calculations in forms  
**Parameters:**
- `prefix` (string, optional) - Form prefix for multiple forms

**Returns:** `void`

**Used in:**
- `public/fuel_type_info.php` - Line 1199 (cost calculations)

### `updateSaleFinalAmount(prefix = '')`
**File:** `public/fuel_type_info.php`  
**Purpose:** Updates final sale amount calculations  
**Parameters:**
- `prefix` (string, optional) - Form prefix for multiple forms

**Returns:** `void`

**Used in:**
- `public/fuel_type_info.php` - Line 1224 (sale calculations)

### `updateAddTotalCost()`
**File:** `public/purchases.php`  
**Purpose:** Updates total cost in add purchase form  
**Parameters:** None  
**Returns:** `void`

**Used in:**
- `public/purchases.php` - Line 315 (purchase calculations)

### `updateEditTotalCost(id)`
**File:** `public/purchases.php`  
**Purpose:** Updates total cost in edit purchase form  
**Parameters:**
- `id` (string) - Form ID

**Returns:** `void`

**Used in:**
- `public/purchases.php` - Line 321 (purchase calculations)

### `updateFinalAmount()`
**File:** `public/daily_sales_summary.php`  
**Purpose:** Updates final amount calculations in sales forms  
**Parameters:** None  
**Returns:** `void`

**Used in:**
- `public/daily_sales_summary.php` - Line 535 (sales calculations)

### `updateUrlAndReload()`
**File:** `public/expenses.php`, `public/daily_sales_summary.php`  
**Purpose:** Updates URL parameters and reloads the page  
**Parameters:** None  
**Returns:** `void`

**Used in:**
- `public/expenses.php` - Line 314
- `public/daily_sales_summary.php` - Line 416

### `updateResponsiveElements()`
**File:** `public/dashboard.php`  
**Purpose:** Updates responsive elements on dashboard  
**Parameters:** None  
**Returns:** `void`

**Used in:**
- `public/dashboard.php` - Line 645 (dashboard responsiveness)

### `updateTimes()`
**File:** `public/models/recurring_shift_modal.php`  
**Purpose:** Updates time fields in recurring shift modal  
**Parameters:** None  
**Returns:** `void`

**Used in:**
- `public/models/recurring_shift_modal.php` - Line 77 (shift management)

---

## Database Functions

### `createRecurringShifts($conn, $shift_id, $start_date, $end_date, $weekday_user_ids = [], $weekend_user_ids = [])`
**File:** `public/shifts.php`  
**Purpose:** Creates recurring shift assignments for a date range  
**Parameters:**
- `$conn` (mysqli) - Database connection
- `$shift_id` (int) - Shift ID
- `$start_date` (string) - Start date (Y-m-d)
- `$end_date` (string) - End date (Y-m-d)
- `$weekday_user_ids` (array, optional) - Array of weekday user IDs
- `$weekend_user_ids` (array, optional) - Array of weekend user IDs

**Returns:** `boolean` - True if successful, false otherwise

**Used in:**
- `public/shifts.php` - Line 5 (shift management)

---

## File Upload Functions

### File Upload Handling (Multiple Files)
**Files:** `public/manage_users.php`, `public/profile.php`  
**Purpose:** Handles profile photo uploads with validation  
**Features:**
- File type validation (JPG, JPEG, PNG, GIF)
- File size validation (800KB max)
- Unique filename generation
- Directory creation
- Error handling

**Usage Examples:**
```php
// Handle file upload
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/profile_photos/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_info = pathinfo($_FILES['profile_photo']['name']);
    $file_extension = strtolower($file_info['extension']);
    
    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    }
    
    // Validate file size (800KB max)
    if ($_FILES['profile_photo']['size'] > 800 * 1024) {
        $errors[] = "File size must be less than 800KB.";
    }
    
    if (empty($errors)) {
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $file_path)) {
            $profile_photo_path = 'uploads/profile_photos/' . $filename;
        } else {
            $errors[] = "Failed to upload file.";
        }
    }
}
```

**Used in:**
- `public/manage_users.php` - Lines 105-140 (Add User), Lines 307-344 (Edit User)
- `public/profile.php` - Lines 49+ (Profile photo upload)

---

## Notification Functions

### `getFilters()`
**File:** `public/notifications.php`  
**Purpose:** Gets notification filters  
**Parameters:** None  
**Returns:** `array` - Filter options

**Used in:**
- `public/notifications.php` - Line 178

### `fetchNotifications()`
**File:** `public/notifications.php`  
**Purpose:** Fetches notifications from database  
**Parameters:** None  
**Returns:** `array` - Array of notifications

**Used in:**
- `public/notifications.php` - Line 186

### `renderSummary(summary)`
**File:** `public/notifications.php`  
**Purpose:** Renders notification summary  
**Parameters:**
- `summary` (object) - Summary data

**Returns:** `string` - HTML summary

**Used in:**
- `public/notifications.php` - Line 197

### `renderTable(notifications)`
**File:** `public/notifications.php`  
**Purpose:** Renders notifications table  
**Parameters:**
- `notifications` (array) - Array of notifications

**Returns:** `string` - HTML table

**Used in:**
- `public/notifications.php` - Line 222

### `renderPagination(pagination)`
**File:** `public/notifications.php`  
**Purpose:** Renders pagination controls  
**Parameters:**
- `pagination` (object) - Pagination data

**Returns:** `string` - HTML pagination

**Used in:**
- `public/notifications.php` - Line 265

### `markAsRead(id)`
**File:** `public/notifications.php`  
**Purpose:** Marks notification as read  
**Parameters:**
- `id` (int) - Notification ID

**Returns:** `void`

**Used in:**
- `public/notifications.php` - Line 280

### `markAsUnread(id)`
**File:** `public/notifications.php`  
**Purpose:** Marks notification as unread  
**Parameters:**
- `id` (int) - Notification ID

**Returns:** `void`

**Used in:**
- `public/notifications.php` - Line 292

### `escapeHtml(text)`
**File:** `public/notifications.php`  
**Purpose:** Escapes HTML in text  
**Parameters:**
- `text` (string) - Text to escape

**Returns:** `string` - Escaped text

**Used in:**
- `public/notifications.php` - Line 309

### `capitalize(str)`
**File:** `public/notifications.php`  
**Purpose:** Capitalizes first letter of string  
**Parameters:**
- `str` (string) - String to capitalize

**Returns:** `string` - Capitalized string

**Used in:**
- `public/notifications.php` - Line 315

---

## Database Stored Procedures and Functions

### `UpdateTankLevel(tank_id, new_level, reading_type, taken_by, notes)`
**File:** `petrol_station_management.sql`  
**Purpose:** Updates tank level and creates reading record  
**Parameters:**
- `tank_id` (BIGINT) - Tank ID
- `new_level` (DECIMAL) - New fuel level
- `reading_type` (ENUM) - Type of reading
- `taken_by` (BIGINT) - User ID who took reading
- `notes` (TEXT) - Additional notes

**Used in:** Database operations for tank management

### `ProcessFuelSale(branch_id, dispenser_id, quantity, unit_price, payment_method, attendant_id, customer_name, vehicle_plate, OUT transaction_id, OUT transaction_number)`
**File:** `petrol_station_management.sql`  
**Purpose:** Processes a fuel sale transaction  
**Parameters:** Multiple input parameters for sale details  
**Output Parameters:**
- `transaction_id` (BIGINT) - Generated transaction ID
- `transaction_number` (VARCHAR) - Generated transaction number

**Used in:** Sales processing operations

### `CalculateTankFillPercentage(tank_id)`
**File:** `petrol_station_management.sql`  
**Purpose:** Calculates tank fill percentage  
**Parameters:**
- `tank_id` (BIGINT) - Tank ID

**Returns:** `DECIMAL(5,2)` - Fill percentage

**Used in:** Tank monitoring and reporting

### `CalculateMonthlySales(branch_id, month_year)`
**File:** `petrol_station_management.sql`  
**Purpose:** Calculates monthly sales for a branch  
**Parameters:**
- `branch_id` (BIGINT) - Branch ID
- `month_year` (DATE) - Month and year

**Returns:** `DECIMAL(15,2)` - Monthly sales amount

**Used in:** Financial reporting and analytics

---

## Usage Guidelines

### 1. Function Dependencies
- Most functions require database connection (`$conn`)
- Authentication functions require active session
- Email functions require PHPMailer configuration

### 2. Error Handling
- Functions return `false` or throw exceptions on failure
- Always check return values before proceeding
- Use try-catch blocks for critical operations

### 3. Security Considerations
- Always use `h()` function for output escaping
- Validate user permissions before sensitive operations
- Use prepared statements for database queries

### 4. Performance
- Cache frequently used data
- Use appropriate indexes on database tables
- Minimize database queries in loops

---

## File Structure

```
fuelstation/
├── includes/
│   ├── auth_helpers.php          # Authentication & authorization
│   ├── email_helper.php          # Email functionality
│   └── PHPMailer-master/         # Email library
├── public/
│   ├── manage_users.php          # User management
│   ├── shifts.php               # Shift management
│   ├── notifications.php        # Notification system
│   └── ...                      # Other public files
├── config/
│   └── db_connect.php           # Database connection
└── petrol_station_management.sql # Database schema & procedures
```

---

## Contributing

When adding new functions:
1. Document the function purpose, parameters, and return values
2. Add usage examples
3. Update this README with the new function
4. Follow existing coding standards
5. Add appropriate error handling
6. Test thoroughly before committing

---

## Support

For questions or issues regarding functions:
1. Check this README first
2. Review the function implementation
3. Check for similar functions in the codebase
4. Contact the development team

---

*Last updated: December 2024* 