# HabeshaFood

HabeshaFood is a PHP-based food tracking and management web application. The system includes user authentication, password recovery through email, a dashboard, and food tracking features.

## Features

- User authentication system
- Forgot/reset password functionality
- Dashboard page
- Food tracking and management
- User profile management
- Email integration using PHPMailer
- Responsive frontend with CSS and JavaScript
- MySQL database integration

---

# Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- PHPMailer

---

# Project Structure

```bash
Habeshafood/
│
├── add_food.php
├── dashboard.php
├── landing.php
├── profile.php
├── tracker.php
├── forgot_password.php
├── reset_password.php
├── logout.php
├── setup.sql
│
├── config/
│   ├── database.php
│   └── email_config.php
│
├── css/
│   └── style.css
│
├── js/
│   └── script.js
│
└── PHPMailer/
    ├── PHPMailer.php
    ├── SMTP.php
    └── Exception.php
```

---

# Installation Guide

## 1. Clone the Repository

```bash
git clone https://github.com/your-username/Habeshafood.git
```

## 2. Move Project to Web Server

Place the project folder inside your:

- `htdocs` folder if using XAMPP
- `www` folder if using WAMP

Example:

```bash
C:/xampp/htdocs/Habeshafood
```

---

## 3. Create Database

1. Open phpMyAdmin
2. Create a new database
3. Import the `setup.sql` file

---

## 4. Configure Database Connection

Open:

```bash
config/database.php
```

Update the database credentials:

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "your_database_name";
```

---

## 5. Configure Email Settings

Open:

```bash
config/email_config.php
```

Add your SMTP email credentials:

```php
$mailUsername = "your-email@example.com";
$mailPassword = "your-password";
```

---

## 6. Run the Project

Start Apache and MySQL from XAMPP/WAMP and open:

```bash
http://localhost/Habeshafood
```

---

# Authentication Features

The system includes:

- Login/logout
- Forgot password
- Reset password through email verification

PHPMailer is used to send password reset emails.

---

# Screens Included

You can add screenshots here after uploading images.

Example:

```md
![Dashboard Screenshot](screenshots/dashboard.png)
```

---

# Future Improvements

- Admin panel
- Food analytics dashboard
- Search and filtering system
- Mobile responsiveness improvements
- API integration
- Better UI/UX enhancements

---

# Contributing

Contributions and improvements are welcome.

1. Fork the repository
2. Create a new branch
3. Commit your changes
4. Push to your branch
5. Open a Pull Request

---

# License

This project was developed for educational and learning purposes.

---

# Author

Developed by Esrom.

