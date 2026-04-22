# MySQL Configuration for Hospital Management System

This folder contains all MySQL database configuration and utilities for the Hospital Management System.

## 📁 Files

### 1. **config.php**
MySQL database configuration file with connection settings.

**Settings:**
- Host: `localhost`
- Username: `root`
- Password: (empty)
- Database: `hospital`
- Port: `3306`
- Charset: `utf8mb4`

**Usage:**
```php
require_once __DIR__ . '/../mysql/config.php';
$pdo = getPDO(); // Get database connection
```

### 2. **schema.sql**
Complete MySQL database schema with all table structures.

**Tables Included:**
- `users` - Admin, Doctors, Patients
- `appointments` - Appointment records
- `prescriptions` - Medical prescriptions
- `bills` - Patient bills
- `payments` - Payment records
- `reports` - Medical reports

**How to Use:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create new database: `hospital`
3. Import `schema.sql` file
4. OR copy and paste the SQL content and execute

### 3. **setup.php**
Automated setup script that creates database, tables, and inserts sample data.

**Features:**
- Creates database automatically
- Creates all required tables
- Inserts sample data (admin, doctors, patients)
- Can be run from command line

**Usage (Command Line):**
```bash
php mysql/setup.php
```

**Output Example:**
```
========================================
🏥 Hospital Management System - MySQL Setup
========================================

✓ Database 'hospital' created successfully!
✓ Users table created!
✓ Appointments table created!
✓ Prescriptions table created!
✓ Bills table created!
✓ Payments table created!
✓ Reports table created!
✓ Admin user inserted!
✓ 5 doctors inserted!
✓ 3 patients inserted!

✅ Setup completed successfully!
```

### 4. **functions.php**
Helper functions for database operations.

**Available Functions:**

#### Query Functions
- `queryFetch($query, $params)` - Get multiple rows
- `queryFetchOne($query, $params)` - Get single row
- `queryExecute($query, $params)` - Execute INSERT/UPDATE/DELETE

#### CRUD Operations
- `insertRecord($table, $data)` - Insert new record
- `updateRecord($table, $data, $where)` - Update record
- `deleteRecord($table, $where)` - Delete record
- `getRecordCount($table, $where)` - Count records
- `recordExists($table, $where)` - Check if record exists

#### Utilities
- `getLastInsertId()` - Get last inserted ID
- `transaction($callback)` - Execute transaction
- `logError($message)` - Log errors
- `sanitizeInput($input)` - Sanitize user input
- `getDatabaseInfo()` - Get database info
- `getAllTables()` - Get all table names

**Usage Example:**
```php
require_once __DIR__ . '/../mysql/functions.php';

// Get all users
$users = queryFetch("SELECT * FROM users");

// Get single user
$user = queryFetchOne("SELECT * FROM users WHERE id = ?", [1]);

// Insert user
$id = insertRecord('users', [
    'full_name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('password', PASSWORD_BCRYPT),
    'role' => 'patient'
]);

// Update user
updateRecord('users', ['full_name' => 'Jane Doe'], ['id' => 1]);

// Delete user
deleteRecord('users', ['id' => 1]);

// Count records
$count = getRecordCount('users', ['role' => 'patient']);
```

## 🚀 Quick Start

### Step 1: Configure MySQL
Make sure XAMPP MySQL service is running.

### Step 2: Create Database
Option A - Using setup script:
```bash
php mysql/setup.php
```

Option B - Using phpMyAdmin:
1. Go to `http://localhost/phpmyadmin`
2. Create database `hospital`
3. Import `schema.sql`

### Step 3: Test Connection
Access the application at `http://localhost/Hospital/`

## 📋 Test Credentials

**Admin:**
- Email: `admin@hospital.com`
- Password: `admin123`

**Doctor:**
- Email: `sarah@hospital.com`
- Password: `doctor123`

**Patient:**
- Email: `john@email.com`
- Password: `patient123`

## 🔧 Configuration Changes

To change MySQL settings, edit `config.php`:

```php
define('DB_HOST', 'localhost');    // MySQL host
define('DB_PORT', 3306);           // MySQL port
define('DB_USER', 'root');         // MySQL username
define('DB_PASS', '');             // MySQL password
define('DB_NAME', 'hospital');     // Database name
define('DB_CHARSET', 'utf8mb4');   // Character set
```

## 📝 Database Schema Overview

### Users Table
```sql
- id (INT, Primary Key)
- full_name (VARCHAR 255)
- email (VARCHAR 255, Unique)
- password (VARCHAR 255)
- role (ENUM: admin, doctor, patient)
- phone (VARCHAR 20)
- specialty (VARCHAR 100)
- age (INT)
- gender (ENUM: Male, Female, Other)
- medical_history (TEXT)
- dob (DATE)
- address (TEXT)
- is_active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Appointments Table
```sql
- id (INT, Primary Key)
- patient_id (INT, Foreign Key)
- doctor_id (INT, Foreign Key)
- appointment_date (DATE)
- appointment_time (TIME)
- reason (TEXT)
- status (ENUM: Pending, Approved, Completed, Cancelled)
- notes (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## 🛠️ Troubleshooting

**Issue:** Connection refused
- Ensure MySQL service is running in XAMPP

**Issue:** Database not found
- Run `php mysql/setup.php` to create database

**Issue:** Permission denied
- Check MySQL username and password in `config.php`

**Issue:** Character encoding issues
- Database uses utf8mb4 for full Unicode support
- Ensure PHP connection uses same charset

## 📞 Support

For issues or questions, check:
1. Error logs: `logs/mysql_errors.log`
2. Database exists: `http://localhost/phpmyadmin`
3. MySQL is running: XAMPP Control Panel

---

**Hospital Management System** | MySQL Database Setup
