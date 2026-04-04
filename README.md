# VendorSphere

#### Video Demo: https://youtu.be/748FYe2QwDQ

#### GitHub: https://github.com/MuhammadYousaf-Dev/vendorsphere

## Description

VendorSphere is a production-ready vendor management system built with PHP and MySQL. It helps organizations manage vendors, contracts, purchase orders, departments, and performance evaluations with role-based access control.

## User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Full access (create, read, update, delete) |
| **Staff** | Create, read, update (no delete) |
| **User** | Read-only access |

## Features

- **Dashboard** - Central hub with statistics, tasks, reports, and compliance
- **Vendor Management** - Complete vendor database with categories and certifications
- **Contract Management** - Track agreements with start/end dates and status badges
- **Purchase Orders** - Order tracking with amounts and status (Pending/Approved/Delivered/Cancelled)
- **Departments** - Organization structure management
- **Performance Evaluation** - 5-star rating system with feedback
- **Task Management** - Assign and track tasks with priorities
- **Compliance Tracking** - Monitor vendor requirements
- **Report Generation** - Create various business reports

## Security Features

- Password hashing using bcrypt
- SQL injection prevention via prepared statements
- Role-based access control (RBAC)
- Session-based authentication
- XSS protection using htmlspecialchars
- Security headers (X-Frame-Options, X-Content-Type-Options)

## Technology Stack

- PHP 7.4+
- MySQL
- HTML5 / CSS3
- JavaScript
- Font Awesome Icons
- Google Fonts (Poppins)

## File Structure

| File | Purpose |
|------|---------|
| `index.php` | Login and signup portal |
| `dashboard.php` | Main control center |
| `vendors.php` | Vendor management |
| `contracts.php` | Contract management |
| `departments.php` | Department management |
| `purchase_orders.php` | Purchase order management |
| `evaluation.php` | Performance evaluation with star ratings |
| `auth.php` | Session authentication |
| `auth_admin.php` | Admin-only access verification |
| `config.php` | Role-based permissions configuration |
| `db.php` | Database connection |
| `logout.php` | Session destroy and redirect |

## Database Schema

The system uses a MySQL database named `vendorsphere` with these tables:

- `users` - Authentication and roles
- `vendor` - Vendor information
- `contract` - Contract agreements
- `departments` - Department list
- `purchase_order` - Order transactions
- `PerformanceEvaluation` - Vendor ratings and feedback
- `tasks` - Task management (auto-created)
- `reports` - Report tracking (auto-created)
- `compliance` - Compliance requirements (auto-created)

## Installation

1. Install XAMPP/WAMP/MAMP
2. Start Apache and MySQL services
3. Create database: `CREATE DATABASE vendorsphere;`
4. Copy all files to `htdocs/vendorsphere/`
5. Update database credentials in `db.php` if needed
6. Access at: `http://localhost/vendorsphere/`

## Test Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | Password123 |
| Staff | staff | Password123 |
| User | user | Password123 |

## Author

**M. Yousaf**
- GitHub: [MuhammadYousaf-Dev](https://github.com/MuhammadYousaf-Dev)
- edX: pro_Shift-Usu
- Location: Sahiwal, Pakistan
- Date: April 4, 2026

## License

This project was created as a final submission for CS50.
