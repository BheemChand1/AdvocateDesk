# Admin Panel Setup Instructions

## Database Setup

1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`

2. Click on "Import" tab

3. Choose the file: `admin/database.sql`

4. Click "Go" to execute the SQL

## Default Login Credentials

- **Username:** admin
- **Password:** admin123

⚠️ **IMPORTANT:** Change the default password after first login!

## Features

- ✅ Secure login system with password hashing
- ✅ User management (Add, Edit, Delete)
- ✅ Role-based access (Admin, Manager, User)
- ✅ Active/Inactive status for users
- ✅ Last login tracking
- ✅ Session management

## File Structure

```
admin/
├── login.php           - Login page
├── login-process.php   - Login authentication
├── dashboard.php       - Main admin dashboard
├── add-user.php        - Add new users
├── edit-user.php       - Edit existing users (to be created)
├── delete-user.php     - Delete users (to be created)
├── logout.php          - Logout functionality
└── database.sql        - Database schema and default user
```

## Access

Visit: `http://localhost/clients/admin/login.php`
