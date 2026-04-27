# Bizentify - Client & Employee Management System

A comprehensive Laravel-based management system for handling clients, employees, invoices, expenses, bonuses, salary releases, and financial reporting with PDF export capabilities.

![Laravel](https://img.shields.io/badge/Laravel-12.x-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Key Features](#key-features)
- [Database Schema](#database-schema)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

---

## ✨ Features

### Core Modules
- **Client Management** - Full CRUD with image uploads, contact details, and invoice tracking
- **Employee Management** - Manage employees with roles, salaries, commission rates, and employment types
- **Invoice Management** - Create invoices with automatic commission calculation, status tracking, and PDF export
- **Expense Tracking** - Record and categorize business expenses with date filtering
- **Bonus System** - Award bonuses to employees with flexible release types
- **Salary Release** - Automated salary calculation with commissions, bonuses, and late/leave deductions
- **Attendance Tracking** - Daily check-in/out with geolocation, IP whitelisting, and auto-late detection
- **Flexible Scheduling** - Global office hours plus per-employee schedule overrides for specific days
- **Comprehensive Reports** - Detailed audit reports with paid/unpaid breakdowns and net income calculations

### Advanced Features
- ✅ **Commission Calculation** - Automatic calculation from paid invoices only (Payment Done status)
- ✅ **Live Preview** - Real-time salary calculation preview with AJAX
- ✅ **Partial Releases** - Support for partial salary payments with validation
- ✅ **Month Tracking** - Track salary releases by month for better organization
- ✅ **PDF Exports** - Professional PDF generation for invoices, salary slips, and audit reports
- ✅ **Multi-User Support** - Secure authentication with user-specific data isolation
- ✅ **Search & Filters** - Advanced filtering on all list pages
- ✅ **Pagination** - Efficient data handling with 10 items per page
- ✅ **Soft Deletes** - Safe deletion with recovery options
- ✅ **Authorization Policies** - Role-based access control
- ✅ **Moderators & Supervisors** - Admin can create delegated users with feature-level permissions (Read/Write)
- ✅ **Feature Registry** - Central list of permission-able features for consistent future expansion
- ✅ **Advanced Attendance** - Geolocation enforcement, IP whitelisting, and distance tracking
- ✅ **Automated Deductions** - Late-based and leave-based automated salary deductions
- ✅ **Employee Portal** - Dedicated dashboard for employees to check-in and view salary slips

---

## 🛠️ Tech Stack

- **Framework**: Laravel 12.x
- **Frontend**: Blade Templates, Tailwind CSS
- **Authentication**: Laravel Breeze
- **Database**: MySQL
- **PDF Generation**: barryvdh/laravel-dompdf
- **Asset Building**: Vite
- **PHP Version**: 8.2+

---

## 📦 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM

### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd bizentify
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Edit `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=envision_reporting
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Build assets**
   ```bash
   npm run build
   ```

9. **Start development server**
   ```bash
   php artisan serve
   ```

10. **Access the application**
    Open your browser and navigate to `http://127.0.0.1:8000`

---

## ⚙️ Configuration

### Default Login Credentials
After seeding the database:
- **Email**: test@example.com
- **Password**: password

### Currency Symbol
The application uses **Rs.** (Rupees) as the default currency. To change it, update the currency symbol in the view files.

### Logo
Place your company logo at `public/assets/logo.png` for it to appear in PDFs and the application header.

---

## 🚀 Usage

### Dashboard
Access the dashboard after login to view:
- Total clients, employees, pending invoices, and expenses
- Recent invoices and expenses
- Quick action buttons

### Creating an Invoice
1. Navigate to **Invoices** → **Create Invoice**
2. Select client and salesperson (self or employee)
3. Enter amount, tax, status, and due date
4. Save to automatically calculate commissions

### Releasing Salary
1. Go to **Salary Releases** → **Release Salary**
2. Select employee and month
3. View live preview with:
   - Base salary
   - Commissions from paid invoices
   - Unreleased bonuses
   - Late deductions (e.g., 3 lates = 1 day)
   - Leave deductions (extra leaves beyond monthly limit)
   - Manual deductions
4. Choose full or partial release
5. Submit to release salary and mark commissions/bonuses as paid

### Generating Reports
1. Navigate to **Reports**
2. Select date range
3. View detailed transaction table
4. Click **Generate PDF Report** for downloadable audit report

---

## 🎯 Key Features

### Commission System
- **Automatic Calculation**: Commission = (Invoice Amount - Tax) × (Employee Commission Rate / 100)
- **Paid Invoices Only**: Commissions calculated only from invoices with "Payment Done" status
- **Tracking**: Prevents duplicate commission payments

### Salary Release
- **Auto-Calculation**: Base + Commissions + Bonuses - Deductions (Late/Leave/Manual)
- **Late Rules**: Automated deduction based on "X lates = 1 day salary" configurable per office
- **Leave Limits**: Automated deduction for leaves exceeding the employee's monthly allowance
- **Month Tracking**: Associate each release with a specific month
- **Partial Releases**: Release partial amounts with validation
- **Live Preview**: See breakdown before submission

### Attendance & Scheduling
- **Check-in/out**: Simple interface for employees with status tracking
- **Geolocation**: Enforce check-ins only within a specific radius of the office
- **IP Whitelisting**: Allow office-only check-ins or provide whitelist overrides
- **Custom Schedules**: Define specific timings for individual employees (e.g., for students or partial shifts)
- **Grace Period**: Configurable grace time (in minutes) before a check-in is marked "Late"

### Net Income Calculation
```
Net Income = Total Invoices - Total Expenses - Total Salaries
```
**Note**: Bonuses are excluded as they are separate rewards

### PDF Exports
- **Invoice PDFs**: Professional invoices with logo and client details
- **Salary Slips**: Detailed breakdown with month and release date
- **Audit Reports**: Comprehensive reports with paid/unpaid sections

---

## 🗄️ Database Schema

### Main Tables
- **users** - System users with authentication
- **clients** - Client information with contact details
- **employees** - Employee records with salary and commission rates
- **invoices** - Invoice records with status and commission tracking
- **expenses** - Business expense records
- **bonuses** - Employee bonus records
- **salary_releases** - Salary payment records with month tracking

### Key Relationships
- User → hasMany → Clients, Employees, Invoices, Expenses, Bonuses, SalaryReleases
- Client → hasMany → Invoices
- Employee → hasMany → Invoices (as salesperson), Bonuses, SalaryReleases
- Invoice → belongsTo → Client, Employee (nullable)

---

## 📸 Screenshots

### Dashboard
Clean and intuitive dashboard with statistics and quick actions.

### Salary Release with Preview
Live calculation preview showing base salary, commissions, bonuses, and deductions.

### Audit Report
Comprehensive financial report with paid/unpaid invoice breakdown.

---

## 🎨 UI/UX

### Color Scheme
- **Primary**: Navy Blue (#001F3F)
- **Background**: White (#FFFFFF)
- **Text**: Black (#000000)
- **Accent**: Green (income), Red (expenses)

### Design Principles
- Clean and minimal interface
- Consistent navy-blue theme
- Professional typography
- Responsive layout
- Accessible forms with validation

---

## 📊 Reports

### Audit Report Includes
- **Executive Summary**: Totals for all categories
- **Paid Invoices**: Separate section with green indicator
- **Unpaid Invoices**: Separate section with red indicator
- **Expenses**: Detailed expense list
- **Salary Releases**: With month, base, commission, and deductions
- **Bonuses**: Separate tracking (excluded from net income)
- **Net Income**: Accurate calculation with formula explanation

---

## 🔒 Security

- CSRF protection on all forms
- Authorization policies for data access
- User-specific data isolation
- Feature-level access control (Read/Write) for moderators & supervisors
- Sidebar hides features the user cannot access (direct URL access still returns 403)
- Secure password hashing
- Rate limiting on login attempts
- Soft deletes for data recovery

---

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

---

## 📝 Recent Updates

### Version 2.0 (October 2025)
- ✅ Commission calculation refined to only include paid invoices
- ✅ Added month field to salary releases
- ✅ Implemented partial salary release functionality
- ✅ Added live preview with AJAX for salary calculations
- ✅ Enhanced reports page with detailed transaction table
- ✅ Updated audit PDF with paid/unpaid invoice sections
- ✅ Excluded bonuses from net income calculation
- ✅ Changed currency symbol to Rs. (Rupees)

### Version 3.0 (April 2026)
- ✅ Added moderator/supervisor roles
- ✅ Added per-feature permissions (Read/Write) with centralized registry in `config/features.php`
- ✅ Added tenant scoping via `users.admin_id` so delegated users see the admin’s data
- ✅ Hardened UI navigation to hide inaccessible features while preserving 403 protection for direct URLs

### Version 4.0 (April 2026 - Current)
- ✅ **Advanced Attendance**: Added geolocation enforcement, IP whitelisting, and distance tracking for check-ins
- ✅ **Flexible Scheduling**: Implemented global office hours and per-employee schedule overrides
- ✅ **Smart Deductions**: Automated salary deductions for lates (e.g., 3 lates = 1 day) and extra leaves
- ✅ **Deduction Settings**: Configurable grace time and late limits from the frontend
- ✅ **Employee Accounts**: Dedicated login for employees to manage attendance and view slips
- ✅ **Detailed Slips**: PDF and UI updates to show breakdown of automated deductions

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 👥 Support

For support, email [EMAIL_ADDRESS] or open an issue in the repository.

---

## 🙏 Acknowledgments

- Laravel Framework
- Tailwind CSS
- DomPDF Library
- All contributors and testers

---

## 📞 Contact

**Project Maintainer**: Your Name  
**Email**: your.email@example.com  
**Website**: https://bizentify.com

---

**Built with ❤️ using Laravel**
