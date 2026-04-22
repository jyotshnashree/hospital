# 🏥 Hospital Management System

A comprehensive web-based hospital management system designed to streamline healthcare operations and improve patient care through integrated tools for appointment scheduling, medical records, prescriptions, billing, and more.

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation & Setup](#installation--setup)
- [Project Structure](#project-structure)
- [User Roles & Portals](#user-roles--portals)
- [Quick Start Guide](#quick-start-guide)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### 👨‍⚕️ Doctor Features
- **Dashboard** - Quick overview of daily appointments and statistics
- **Appointment Management** - View, manage, and schedule appointments with patients
- **Availability Management** - Set and manage weekly availability and time slots
- **Prescription Management** - Create and manage patient prescriptions
- **Messaging System** - Secure communication with patients
- **Patient Records** - Access and review patient medical histories

### 👤 Patient Features
- **Dashboard** - View personal health statistics and upcoming appointments
- **Appointment Booking** - Search for doctors by specialty and book appointments
- **View Appointments** - Track scheduled appointments and medical visits
- **Medical Records** - Access prescription history and medical reports
- **Bills & Payments** - View bills and make payments (UPI/Card integration)
- **Rating System** - Rate doctors and provide feedback
- **Messaging** - Chat with assigned doctors

### 🏢 Admin Features
- **Dashboard** - Hospital-wide statistics and analytics
- **Patient Management** - Add, edit, and manage patient accounts
- **Doctor Management** - Manage doctor profiles and specialties
- **Appointment Management** - Oversee all hospital appointments
- **Billing System** - Generate and manage patient bills with payment processing
- **Pharmacy Management** - Manage medicine inventory and stock
- **Payment Processing** - Handle online payments (UPI & Card)
- **Reports & Analytics** - Generate medical reports and performance analytics
- **Notifications** - Send alerts and notifications to users
- **Ratings Management** - Monitor patient feedback and ratings

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5.3 |
| **Backend** | PHP 7.4+ |
| **Database** | MySQL/MariaDB with PDO |
| **UI Framework** | Bootstrap 5.3 with Icons |
| **Authentication** | Session-based with role management |

## 💻 System Requirements

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **PHP Extensions**: 
  - `pdo`
  - `pdo_mysql`
  - `session`
  - `filter`
- **Browser**: Modern browser (Chrome, Firefox, Safari, Edge)
- **Memory**: Minimum 512MB RAM
- **Disk Space**: Minimum 100MB

## 📦 Installation & Setup

### 1. Prerequisites
Ensure you have XAMPP or similar LAMP/LEMP stack installed with:
- Apache/Nginx
- PHP 7.4+
- MySQL/MariaDB

### 2. Clone or Download Project
```bash
cd c:\xampp\htdocs\
git clone https://github.com/jyotshashree/hospital.git
# OR download and extract the project folder
```

### 3. Database Setup
```bash
# Navigate to the project directory
cd hospital

# Create database and run schema
mysql -u root -p < mysql/schema.sql
```

### 4. Configure Database Connection
Edit `mysql/config.php`:
```php
<?php
$host = 'localhost';
$db = 'hospital_db';
$user = 'root';
$password = ''; // Your MySQL password
$charset = 'utf8mb4';
?>
```

### 5. Access the Application
Open your browser and navigate to:
```
http://localhost/hospital/
```

### 6. Initial Login Credentials
**Admin Portal:**
- URL: `http://localhost/hospital/login.php`
- Username: `admin`
- Password: `admin123`

**Doctor Portal:**
- URL: `http://localhost/hospital/doctor_login.php`
- Username: `doctor1`
- Password: `doctor123`

**Patient Portal:**
- URL: `http://localhost/hospital/login.php`
- Username: `patient1`
- Password: `patient123`

> ⚠️ **Important**: Change all default passwords in production!

## 📁 Project Structure

```
hospital/
├── index.php                 # Main entry page
├── login.php                 # Login page
├── doctor_login.php          # Doctor login portal
├── register.php              # User registration
├── portals.php               # Portal selection page
├── logout.php                # Logout handler
├── dashboard.php             # Main dashboard
├── styles.css                # Global styles
├── scripts.js                # Global scripts
│
├── admin/                    # Admin portal
│   ├── dashboard.php         # Admin dashboard
│   ├── patients.php          # Manage patients
│   ├── doctors.php           # Manage doctors
│   ├── appointments.php      # Manage appointments
│   ├── bills.php             # Billing management
│   ├── pharmacy.php          # Pharmacy inventory
│   ├── prescriptions.php     # Prescription management
│   ├── payments.php          # Payment processing
│   ├── reports.php           # Medical reports
│   ├── analytics.php         # Analytics & statistics
│   ├── ratings.php           # Patient ratings
│   └── notifications.php     # Notification system
│
├── doctor/                   # Doctor portal
│   ├── dashboard.php         # Doctor dashboard
│   ├── appointments.php      # Manage appointments
│   ├── availability.php      # Set availability
│   ├── prescriptions.php     # Create prescriptions
│   └── messages.php          # Chat with patients
│
├── patient/                  # Patient portal
│   ├── dashboard.php         # Patient dashboard
│   ├── appointments.php      # View appointments
│   ├── book_appointment.php  # Book appointments
│   ├── doctors.php           # Browse doctors
│   ├── prescriptions.php     # View prescriptions
│   ├── bills.php             # View and pay bills
│   ├── messages.php          # Chat with doctors
│   ├── ratings.php           # Rate doctors
│   └── billing.php           # Billing details
│
├── mysql/                    # Database files
│   ├── config.php            # Database configuration
│   ├── functions.php         # Database functions
│   ├── schema.sql            # Main database schema
│   ├── extended_schema.sql   # Extended schema
│   ├── setup.php             # Setup script
│   └── sample_data.php       # Sample data insertion
│
├── inc/                      # Includes
│   ├── header.php            # Header template
│   └── footer.php            # Footer template
│
├── SECURITY.md               # Security documentation
└── README.md                 # This file
```

## 👥 User Roles & Portals

### 1. **Admin Portal**
- Full system access and control
- Manage all users, appointments, and records
- Financial and analytics overview
- Access: `http://localhost/hospital/login.php`

### 2. **Doctor Portal**
- View and manage patient appointments
- Create and manage prescriptions
- Set availability and schedule
- Communicate with patients
- Access: `http://localhost/hospital/doctor_login.php`

### 3. **Patient Portal**
- Book appointments with doctors
- View medical records and prescriptions
- Pay bills online
- Rate doctors and services
- Access: `http://localhost/hospital/login.php`

### 4. **Portal Selection**
- Central hub to access all portals
- Access: `http://localhost/hospital/portals.php`

## 🚀 Quick Start Guide

### For Administrators:
1. Login to admin portal
2. Go to "Manage Doctors" and add doctors
3. Go to "Manage Patients" and register patients
4. View dashboard for system overview
5. Process payments and manage billing

### For Doctors:
1. Login to doctor portal
2. Set availability in "Availability Management"
3. Review and manage appointments
4. Create prescriptions for patients
5. Communicate via messaging system

### For Patients:
1. Login to patient portal
2. Browse doctors by specialty
3. Book appointments
4. View and download prescriptions
5. Pay bills using integrated payment system

## 🗄️ Database Setup

### Initialize Database
```bash
# Run the main schema
mysql -u root -p hospital_db < mysql/schema.sql

# (Optional) Insert sample data for testing
mysql -u root -p hospital_db < mysql/insert_sample_data.sql
```

### Key Tables
- `users` - User accounts and authentication
- `doctors` - Doctor information and specialties
- `patients` - Patient demographics and records
- `appointments` - Appointment scheduling
- `prescriptions` - Prescription records
- `bills` - Billing information
- `pharmacy_inventory` - Medicine stock management
- `notifications` - System notifications
- `messages` - Doctor-patient communication
- `doctor_availability` - Doctor schedule and time slots

## ⚙️ Configuration

### Database Configuration
Edit `mysql/config.php` to set your database credentials:
```php
$host = 'localhost';
$db = 'hospital_db';
$user = 'root';
$password = 'your_password';
```

### Session Configuration
Session timeout and security settings in `mysql/config.php`:
- Default session timeout: 30 minutes
- Session security: Enabled

## 🔐 Security

This system implements several security measures:

- ✅ **Session-based Authentication** - Secure user authentication
- ✅ **Role-based Access Control** - Different permissions per user role
- ✅ **PDO Database Access** - Protection against SQL injection
- ✅ **Password Hashing** - Secure password storage
- ✅ **Input Validation** - Server-side validation
- ✅ **HTTPS Ready** - Compatible with SSL/TLS

For detailed security information, see [SECURITY.md](SECURITY.md)

### Important Security Notes:
1. **Change default credentials** before production deployment
2. **Enable HTTPS** for all communications
3. **Keep PHP updated** to latest stable version
4. **Use strong passwords** for database access
5. **Regular backups** of database and files
6. **Restrict file permissions** appropriately

## 📝 Features Roadmap

### Upcoming Features:
- [ ] Video consultation system (WebRTC)
- [ ] Email notifications and alerts
- [ ] SMS notifications
- [ ] Advanced analytics and reporting
- [ ] Appointment reminders
- [ ] Integration with external calendars
- [ ] Mobile app version
- [ ] API endpoints for third-party integration

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📧 Support & Contact

For issues, questions, or suggestions:
- Create an issue on the GitHub repository
- Contact the development team
- Check existing documentation in [SECURITY.md](SECURITY.md)

## 🎯 Project Status

**Current Version:** 1.0.0

- ✅ Core functionality complete
- ✅ Multi-role authentication
- ✅ Appointment management
- ✅ Prescription system
- ✅ Billing and payment integration
- ✅ Pharmacy management
- 🔄 In development: Advanced features

---

**Last Updated:** April 2026

**Maintainer:** Jyotshashree

Thank you for using the Hospital Management System! 🏥
